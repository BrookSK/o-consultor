<?php
/**
 * ConcorrenteController — Scrap da Concorrência (Central de Conteúdo)
 *
 * Cadastro e monitoramento de perfis públicos de concorrentes. Coleta via
 * ScrapingBee (HTML público), armazena snapshots por publicação (histórico,
 * sem sobrescrever) e gera análise automática por IA. Os dados alimentam a
 * Máquina de Conteúdo (fonte "concorrência"), sem copiar textos/identidade.
 *
 * Segurança (spec §18): todas as operações são isoladas por empresa. Apenas
 * conteúdo publicamente acessível; URLs sanitizadas; sem burlar autenticação.
 */

class ConcorrenteController
{
    /**
     * Exige empresa selecionada; retorna o ID ou encerra a request.
     * $json define se a resposta de erro é JSON (AJAX) ou redirect.
     */
    private function exigirEmpresa(bool $json = false): int
    {
        Auth::proteger();
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            if ($json) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Selecione uma empresa específica no menu do topo.']);
            } else {
                Flash::set('erro', 'Selecione uma empresa específica (menu no topo) para gerenciar a concorrência.');
                header('Location: ' . APP_URL . '/admin/clientes');
            }
            exit;
        }
        return (int) $empresaId;
    }

    // ===== CRUD =====

    /**
     * Cadastra um novo concorrente.
     */
    public function salvar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $url = ScrapingBee::sanitizarUrl((string) ($_POST['url_publica'] ?? ''));
        $nome = trim((string) ($_POST['nome'] ?? ''));

        if ($nome === '' || $url === null) {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe um nome e uma URL pública válida.']);
            exit;
        }

        $id = Concorrente::criar($empresaId, [
            'nome'                => $nome,
            'nome_perfil'         => trim((string) ($_POST['nome_perfil'] ?? '')) ?: null,
            'url_publica'         => $url,
            'plataforma'          => (string) ($_POST['plataforma'] ?? 'instagram'),
            'descricao'           => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'categoria'           => trim((string) ($_POST['categoria'] ?? '')) ?: null,
            'frequencia_coleta'   => (string) ($_POST['frequencia_coleta'] ?? 'manual'),
            'max_posts_por_coleta'=> (int) ($_POST['max_posts_por_coleta'] ?? 12),
            'principal'           => !empty($_POST['principal']),
            'seguidores'          => $_POST['seguidores'] ?? '',
            'observacoes'         => trim((string) ($_POST['observacoes'] ?? '')) ?: null,
        ]);

        if ($id) {
            Logger::acao('Concorrente cadastrado', ['empresa_id' => $empresaId, 'concorrente_id' => $id]);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Concorrente adicionado!', 'id' => $id]);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível salvar. Verifique se a migration 054 foi aplicada.']);
        }
        exit;
    }

    /**
     * Atualiza um concorrente existente.
     */
    public function atualizar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0 || !Concorrente::buscar($id, $empresaId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Concorrente não encontrado.']);
            exit;
        }

        $dados = [];
        foreach (['nome', 'nome_perfil', 'descricao', 'categoria', 'frequencia_coleta', 'plataforma', 'observacoes'] as $c) {
            if (isset($_POST[$c])) $dados[$c] = trim((string) $_POST[$c]);
        }
        if (isset($_POST['url_publica'])) {
            $u = ScrapingBee::sanitizarUrl((string) $_POST['url_publica']);
            if ($u === null) { echo json_encode(['sucesso' => false, 'erro' => 'URL pública inválida.']); exit; }
            $dados['url_publica'] = $u;
        }
        if (isset($_POST['max_posts_por_coleta'])) $dados['max_posts_por_coleta'] = max(1, min(50, (int) $_POST['max_posts_por_coleta']));
        if (isset($_POST['seguidores'])) $dados['seguidores'] = $_POST['seguidores'] !== '' ? (int) $_POST['seguidores'] : null;
        if (isset($_POST['principal'])) $dados['principal'] = !empty($_POST['principal']);
        if (isset($_POST['status']) && in_array($_POST['status'], ['ativo', 'pausado'], true)) $dados['status'] = $_POST['status'];

        $ok = Concorrente::atualizar($id, $empresaId, $dados);
        echo json_encode($ok
            ? ['sucesso' => true, 'mensagem' => 'Concorrente atualizado!']
            : ['sucesso' => false, 'erro' => 'Não foi possível atualizar.']);
        exit;
    }

    /**
     * Alterna o status (pausar/ativar).
     */
    public function pausar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        $c = Concorrente::buscar($id, $empresaId);
        if (!$c) { echo json_encode(['sucesso' => false, 'erro' => 'Concorrente não encontrado.']); exit; }

        $novo = ($c['status'] === 'ativo') ? 'pausado' : 'ativo';
        Concorrente::atualizar($id, $empresaId, ['status' => $novo]);
        echo json_encode(['sucesso' => true, 'status' => $novo]);
        exit;
    }

    /**
     * Exclui um concorrente e todos os seus dados coletados (spec §18).
     */
    public function excluir(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        if (!Concorrente::buscar($id, $empresaId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Concorrente não encontrado.']);
            exit;
        }

        $ok = Concorrente::excluir($id, $empresaId);
        if ($ok) Logger::acao('Concorrente excluído', ['empresa_id' => $empresaId, 'concorrente_id' => $id]);
        echo json_encode($ok
            ? ['sucesso' => true, 'mensagem' => 'Concorrente excluído.']
            : ['sucesso' => false, 'erro' => 'Não foi possível excluir.']);
        exit;
    }

    // ===== COLETA E ANÁLISE =====

    /**
     * Dispara uma coleta manual ("Analisar agora") e, em seguida, a análise.
     */
    public function coletar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        if (!ScrapingBee::configurada()) {
            echo json_encode(['sucesso' => false, 'erro' => 'Configure a integração de coleta em Admin > Configurações.']);
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!Concorrente::buscar($id, $empresaId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Concorrente não encontrado.']);
            exit;
        }

        $coleta = ConcorrenteColeta::executar($id, $empresaId, 'manual');
        if (!$coleta['sucesso']) {
            echo json_encode(['sucesso' => false, 'erro' => $coleta['erro'] ?? 'Falha na coleta.']);
            exit;
        }

        // Análise automática após a coleta (spec §8.9). Best-effort: se falhar,
        // a coleta continua válida.
        $analise = ConcorrenteAnalise::gerar($id, $empresaId, $coleta['coleta_id']);

        Logger::acao('Coleta de concorrente executada', ['empresa_id' => $empresaId, 'concorrente_id' => $id, 'posts' => $coleta['posts']]);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Coleta concluída: {$coleta['posts']} publicação(ões).",
            'posts' => $coleta['posts'],
            'analise_ok' => (bool) ($analise['sucesso'] ?? false),
        ]);
        exit;
    }

    /**
     * Gera apenas a análise a partir dos dados já coletados.
     */
    public function analisar(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        Csrf::verificar();
        header('Content-Type: application/json');

        $id = (int) ($_POST['id'] ?? 0);
        if (!Concorrente::buscar($id, $empresaId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Concorrente não encontrado.']);
            exit;
        }

        $analise = ConcorrenteAnalise::gerar($id, $empresaId);
        echo json_encode($analise['sucesso']
            ? ['sucesso' => true, 'mensagem' => 'Análise atualizada!']
            : ['sucesso' => false, 'erro' => $analise['erro'] ?? 'Falha na análise.']);
        exit;
    }

    /**
     * Tela individual de análise do concorrente (spec §8.12).
     */
    public function ver(): void
    {
        $empresaId = $this->exigirEmpresa(false);

        $id = (int) ($_GET['id'] ?? 0);
        $concorrente = Concorrente::buscar($id, $empresaId);
        if (!$concorrente) {
            Flash::set('erro', 'Concorrente não encontrado.');
            header('Location: ' . APP_URL . '/central-de-conteudo#concorrencia');
            exit;
        }

        $analise = Database::queryOne(
            "SELECT * FROM concorrente_analises WHERE concorrente_id = :id AND empresa_id = :eid ORDER BY criado_em DESC LIMIT 1",
            ['id' => $id, 'eid' => $empresaId]
        );
        if ($analise) {
            $analise['dados'] = json_decode($analise['dados'] ?? '[]', true) ?: [];
            $analise['oportunidades'] = json_decode($analise['oportunidades'] ?? '[]', true) ?: [];
        }

        $posts = Database::query(
            "SELECT * FROM concorrente_posts WHERE concorrente_id = :id AND empresa_id = :eid
             ORDER BY engajamento_absoluto IS NULL, engajamento_absoluto DESC, coletado_em DESC LIMIT 30",
            ['id' => $id, 'eid' => $empresaId]
        );

        $coletas = Database::query(
            "SELECT * FROM concorrente_coletas WHERE concorrente_id = :id AND empresa_id = :eid
             ORDER BY criado_em DESC LIMIT 10",
            ['id' => $id, 'eid' => $empresaId]
        );

        $dados = [
            'concorrente' => $concorrente,
            'analise' => $analise,
            'posts' => $posts,
            'coletas' => $coletas,
            'resumo' => Concorrente::resumo($id, $empresaId),
        ];
        require VIEW_PATH . '/conteudo/concorrente-ver.php';
    }

    /**
     * Lista os concorrentes (JSON) com resumo, para a aba na Central.
     */
    public function listarJson(): void
    {
        $empresaId = $this->exigirEmpresa(true);
        header('Content-Type: application/json');

        $lista = array_map(function ($c) use ($empresaId) {
            $resumo = Concorrente::resumo((int) $c['id'], $empresaId);
            return [
                'id' => (int) $c['id'],
                'nome' => $c['nome'],
                'nome_perfil' => $c['nome_perfil'],
                'url_publica' => $c['url_publica'],
                'plataforma' => $c['plataforma'],
                'status' => $c['status'],
                'principal' => (bool) $c['principal'],
                'ultima_coleta_em' => $c['ultima_coleta_em'],
                'proxima_coleta_em' => $c['proxima_coleta_em'],
                'posts' => $resumo['posts'],
                'engajamento_medio' => $resumo['engajamento_medio'],
                'melhor_post' => $resumo['melhor_post'],
            ];
        }, Concorrente::listar($empresaId));

        echo json_encode(['sucesso' => true, 'concorrentes' => $lista, 'scrapingbee_ok' => ScrapingBee::configurada()]);
        exit;
    }
}
