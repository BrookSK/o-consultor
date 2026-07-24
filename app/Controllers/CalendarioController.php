<?php
/**
 * CalendarioController — Calendário de Conteúdo (Central de Conteúdo)
 *
 * Lista/gera o calendário editorial da empresa, identifica datas relevantes
 * ao nicho (via IA) e permite editar/ignorar/adicionar itens. Cada item pode
 * ser enviado para a Máquina de Conteúdo (origem = data_comemorativa/tema).
 *
 * Isolado por empresa; mesmo padrão de segurança das demais actions.
 */

class CalendarioController
{
    private function exigirEmpresa(bool $json = false): int
    {
        Auth::proteger();
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            if ($json) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Selecione uma empresa específica no menu do topo.']);
            } else {
                Flash::set('erro', 'Selecione uma empresa específica (menu no topo) para ver o calendário.');
                header('Location: ' . APP_URL . '/admin/clientes');
            }
            exit;
        }
        return (int) $empresaId;
    }

    /**
     * Lista os itens do calendário (JSON) para a aba na Central.
     */
    public function listar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        header('Content-Type: application/json');

        $de = $_GET['de'] ?? null;
        $ate = $_GET['ate'] ?? null;
        $itens = CalendarioConteudo::listar($empresaId, $de, $ate);
        $proximasDatas = DataComemorativa::proximas($empresaId, 60, false);

        echo json_encode([
            'sucesso' => true,
            'itens' => $itens,
            'proximas_datas' => $proximasDatas,
        ]);
        exit;
    }

    /**
     * Identifica datas relevantes ao nicho (IA) e popula o calendário.
     */
    public function gerar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $datas = CalendarioGerador::sugerirDatas($empresaId);
        $calendario = CalendarioGerador::popularCalendario($empresaId, 90);

        if (!$datas['sucesso'] && ($calendario['criados'] ?? 0) === 0) {
            echo json_encode(['sucesso' => false, 'erro' => $datas['erro'] ?? 'Não foi possível gerar o calendário.']);
            exit;
        }

        Logger::acao('Calendário gerado', ['empresa_id' => $empresaId, 'datas' => $datas['criadas'] ?? 0, 'itens' => $calendario['criados'] ?? 0]);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => sprintf('%d data(s) identificada(s), %d item(ns) adicionado(s) ao calendário.', $datas['criadas'] ?? 0, $calendario['criados'] ?? 0),
        ]);
        exit;
    }

    /**
     * Gera sugestões livres/semanais de conteúdo (spec §6 "conteúdo semanal").
     */
    public function gerarSemanal(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $res = CalendarioGerador::gerarSemanal($empresaId);
        echo json_encode($res['sucesso']
            ? ['sucesso' => true, 'mensagem' => sprintf('%d sugestão(ões) semanal(is) adicionada(s).', $res['criados'] ?? 0)]
            : ['sucesso' => false, 'erro' => $res['erro'] ?? 'Falha ao gerar sugestões.']);
        exit;
    }

    /**
     * Adiciona um item manual ao calendário (tema informado pelo usuário).
     */
    public function adicionar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $tema = trim((string) ($_POST['tema'] ?? ''));
        if ($tema === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o tema.']);
            exit;
        }

        $id = CalendarioConteudo::criar($empresaId, [
            'tema'                     => $tema,
            'origem'                   => 'tema_manual',
            'data_publicacao_sugerida' => $_POST['data_publicacao_sugerida'] ?? null,
            'formato_recomendado'      => trim((string) ($_POST['formato_recomendado'] ?? '')) ?: null,
            'objetivo'                 => trim((string) ($_POST['objetivo'] ?? '')) ?: null,
            'responsavel'              => trim((string) ($_POST['responsavel'] ?? '')) ?: null,
            'gerar_imagem'             => isset($_POST['gerar_imagem']) ? 1 : 0,
            'status'                   => 'planejado',
        ]);

        echo json_encode($id
            ? ['sucesso' => true, 'mensagem' => 'Item adicionado ao calendário!', 'id' => $id]
            : ['sucesso' => false, 'erro' => 'Não foi possível adicionar. Verifique se a migration 054 foi aplicada.']);
        exit;
    }

    /**
     * Edita um item do calendário (editar sugestão).
     */
    public function atualizar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        if (!CalendarioConteudo::buscar($id, $empresaId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Item não encontrado.']);
            exit;
        }

        $dados = [];
        foreach (['tema', 'formato_recomendado', 'objetivo', 'responsavel', 'data_publicacao_sugerida', 'data_evento', 'observacoes'] as $c) {
            if (isset($_POST[$c])) $dados[$c] = trim((string) $_POST[$c]);
        }
        if (isset($_POST['gerar_imagem'])) $dados['gerar_imagem'] = !empty($_POST['gerar_imagem']);

        $ok = CalendarioConteudo::atualizar($id, $empresaId, $dados);
        echo json_encode($ok
            ? ['sucesso' => true, 'mensagem' => 'Item atualizado!']
            : ['sucesso' => false, 'erro' => 'Não foi possível atualizar.']);
        exit;
    }

    /**
     * Ignora um item (não aparece mais no calendário).
     */
    public function ignorar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $ok = CalendarioConteudo::atualizarStatus($id, $empresaId, 'ignorado');
        echo json_encode($ok
            ? ['sucesso' => true, 'mensagem' => 'Item removido do calendário.']
            : ['sucesso' => false, 'erro' => 'Não foi possível remover.']);
        exit;
    }

    /**
     * Classifica manualmente a relevância de uma data comemorativa.
     */
    public function classificarData(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $dataId = (int) ($_POST['data_id'] ?? 0);
        $relevancia = (string) ($_POST['relevancia'] ?? '');
        $ok = DataComemorativa::definirRelevancia($dataId, $empresaId, $relevancia);
        echo json_encode($ok
            ? ['sucesso' => true, 'mensagem' => 'Relevância atualizada!']
            : ['sucesso' => false, 'erro' => 'Não foi possível atualizar a relevância.']);
        exit;
    }
}
