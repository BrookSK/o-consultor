<?php
/**
 * Model Diagnostico — Diagnósticos empresariais
 */

class Diagnostico
{
    /**
     * Buscar por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM diagnosticos WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Buscar por empresa
     */
    public static function buscarPorEmpresa(int $empresaId): array
    {
        return Database::query(
            "SELECT * FROM diagnosticos WHERE empresa_id = :empresa_id ORDER BY criado_em DESC",
            ['empresa_id' => $empresaId]
        );
    }

    /**
     * Buscar último diagnóstico concluído por empresa
     */
    public static function buscarUltimoPorEmpresa(int $empresaId): ?array
    {
        return Database::queryOne(
            "SELECT * FROM diagnosticos WHERE empresa_id = :empresa_id AND status = 'concluido' ORDER BY criado_em DESC LIMIT 1",
            ['empresa_id' => $empresaId]
        );
    }

    /**
     * Criar novo diagnóstico
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO diagnosticos (empresa_id, usuario_id, respostas, pontuacao, status, criado_em) 
             VALUES (:empresa_id, :usuario_id, :respostas, :pontuacao, :status, NOW())",
            [
                'empresa_id' => $dados['empresa_id'],
                'usuario_id' => $dados['usuario_id'],
                'respostas'  => json_encode($dados['respostas'] ?? []),
                'pontuacao'  => $dados['pontuacao'] ?? 0,
                'status'     => $dados['status'] ?? 'em_andamento',
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Criar rascunho ou buscar existente em andamento
     */
    public static function buscarOuCriarRascunho(int $usuarioId): array
    {
        // Buscar rascunho existente em andamento
        $rascunho = Database::queryOne(
            "SELECT * FROM diagnosticos_rascunho WHERE usuario_id = :usuario_id AND status = 'em_andamento' ORDER BY criado_em DESC LIMIT 1",
            ['usuario_id' => $usuarioId]
        );
        
        if ($rascunho) {
            return $rascunho;
        }
        
        // Criar novo rascunho
        $sucesso = Database::execute(
            "INSERT INTO diagnosticos_rascunho (usuario_id, bloco_atual, status, criado_em) VALUES (:usuario_id, 1, 'em_andamento', NOW())",
            ['usuario_id' => $usuarioId]
        );
        
        if ($sucesso) {
            $id = Database::lastInsertId();
            return Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id", ['id' => $id]);
        }
        
        return [];
    }

    /**
     * Salvar bloco do rascunho
     */
    public static function salvarBlocoRascunho(int $rascunhoId, int $bloco, array $dados): bool
    {
        $campos = [];
        $params = ['id' => $rascunhoId, 'bloco_atual' => $bloco];
        
        // Log de entrada para debug
        error_log("DiagnosticoBlocoRascunho: Salvando Bloco $bloco, ID: $rascunhoId");
        error_log("DiagnosticoBlocoRascunho: Dados recebidos: " . json_encode($dados));
        
        // Campos por bloco
        switch ($bloco) {
            case 1: // Identificação
                $camposBloco1 = ['empresa_nome', 'setor', 'descricao', 'tempo_existencia', 'estrutura_societaria', 'unidades_filiais', 'lingua_principal'];
                foreach ($camposBloco1 as $campo) {
                    if (isset($dados[$campo]) && $dados[$campo] !== null && $dados[$campo] !== '') {
                        $campos[] = "$campo = :$campo";
                        $params[$campo] = $dados[$campo];
                    }
                }
                break;
                
            case 2: // Estrutura Operacional
                $camposBloco2 = ['colaboradores_internos', 'colaboradores_externos', 'clientes_ativos', 'produtos_servicos', 'faturamento_mensal', 'ticket_medio', 'sites_referencia'];
                foreach ($camposBloco2 as $campo) {
                    if (isset($dados[$campo]) && $dados[$campo] !== null && $dados[$campo] !== '') {
                        $campos[] = "$campo = :$campo";
                        $params[$campo] = $dados[$campo];
                    }
                }
                // Arrays JSON
                if (isset($dados['departamentos']) && is_array($dados['departamentos'])) {
                    $campos[] = "departamentos = :departamentos";
                    $params['departamentos'] = json_encode($dados['departamentos']);
                }
                break;
                
            case 3: // Operação Atual
                $camposBloco3 = ['processo_entrega', 'ferramentas_softwares', 'fornecedores_criticos', 'dependencia_pessoa', 'integracoes', 'processos_documentados'];
                foreach ($camposBloco3 as $campo) {
                    if (isset($dados[$campo]) && $dados[$campo] !== null && $dados[$campo] !== '') {
                        $campos[] = "$campo = :$campo";
                        $params[$campo] = $dados[$campo];
                    }
                }
                if (isset($dados['ferramentas_gestao']) && is_array($dados['ferramentas_gestao'])) {
                    $campos[] = "ferramentas_gestao = :ferramentas_gestao";
                    $params['ferramentas_gestao'] = json_encode($dados['ferramentas_gestao']);
                }
                break;
                
            case 4: // Problemas e Riscos
                $camposBloco4 = ['problemas_operacionais', 'riscos_identificados', 'incidentes_tipo', 'incidentes_descricao', 'cliente_concentrado', 'fornecedor_insubstituivel', 'processos_sem_backup'];
                foreach ($camposBloco4 as $campo) {
                    if (isset($dados[$campo]) && $dados[$campo] !== null && $dados[$campo] !== '') {
                        $campos[] = "$campo = :$campo";
                        $params[$campo] = $dados[$campo];
                    }
                }
                if (isset($dados['areas_vulneraveis']) && is_array($dados['areas_vulneraveis'])) {
                    $campos[] = "areas_vulneraveis = :areas_vulneraveis";
                    $params['areas_vulneraveis'] = json_encode($dados['areas_vulneraveis']);
                }
                break;
                
            case 5: // Contexto Estratégico
                $camposBloco5 = ['pontos_fortes', 'pontos_melhoria', 'objetivo_12_meses', 'maturidade_percebida', 'planejamento_documentado', 'frequencia_reunioes', 'meta_faturamento'];
                foreach ($camposBloco5 as $campo) {
                    if (isset($dados[$campo]) && $dados[$campo] !== null && $dados[$campo] !== '') {
                        $campos[] = "$campo = :$campo";
                        $params[$campo] = $dados[$campo];
                    }
                }
                break;
        }
        
        // Sempre atualizar bloco_atual e data de atualização
        $campos[] = "bloco_atual = :bloco_atual";
        $campos[] = "atualizado_em = NOW()";
        
        if (empty($campos)) {
            error_log("DiagnosticoBlocoRascunho: Nenhum campo para salvar - Bloco: $bloco");
            error_log("DiagnosticoBlocoRascunho: Dados originais: " . json_encode($dados));
            // Mesmo assim, salvamos bloco_atual
            $campos = ["bloco_atual = :bloco_atual", "atualizado_em = NOW()"];
        }
        
        $sql = "UPDATE diagnosticos_rascunho SET " . implode(', ', $campos) . " WHERE id = :id";
        
        error_log("DiagnosticoBlocoRascunho: SQL: $sql");
        error_log("DiagnosticoBlocoRascunho: Params: " . json_encode($params));
        
        $resultado = Database::execute($sql, $params);
        
        if (!$resultado) {
            error_log("DiagnosticoBlocoRascunho: ERRO ao executar SQL");
            error_log("DiagnosticoBlocoRascunho: SQL: $sql");
            error_log("DiagnosticoBlocoRascunho: Params: " . json_encode($params));
            
            // Verificar se o rascunho existe
            $rascunhoExiste = Database::queryOne("SELECT id FROM diagnosticos_rascunho WHERE id = :id", ['id' => $rascunhoId]);
            if (!$rascunhoExiste) {
                error_log("DiagnosticoBlocoRascunho: ERRO - Rascunho ID $rascunhoId não existe");
            }
        } else {
            error_log("DiagnosticoBlocoRascunho: Sucesso - Bloco $bloco salvo");
        }
        
        return $resultado;
    }

    /**
     * Gerar diagnóstico completo do rascunho
     */
    public static function gerarDoRascunho(int $rascunhoId): int|false
    {
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id", ['id' => $rascunhoId]);
        
        if (!$rascunho || $rascunho['status'] !== 'em_andamento') {
            return false;
        }
        
        // Converter rascunho para dados completos
        $respostasCompletas = [];
        
        // Copiar todos os campos do rascunho (exceto metadados)
        $camposExcluir = ['id', 'empresa_id', 'usuario_id', 'bloco_atual', 'status', 'criado_em', 'atualizado_em'];
        foreach ($rascunho as $campo => $valor) {
            if (!in_array($campo, $camposExcluir) && $valor !== null) {
                // Decodificar JSON se necessário
                if (in_array($campo, ['departamentos', 'ferramentas_gestao', 'areas_vulneraveis'])) {
                    $respostasCompletas[$campo] = json_decode($valor, true) ?? [];
                } else {
                    $respostasCompletas[$campo] = $valor;
                }
            }
        }
        
        // Criar empresa se não existir
        $empresaId = $rascunho['empresa_id'];
        if (!$empresaId && !empty($rascunho['empresa_nome'])) {
            $empresaId = Empresa::criar([
                'nome' => $rascunho['empresa_nome'],
                'segmento' => $rascunho['setor'],
                'responsavel_id' => $rascunho['usuario_id']
            ]);
            
            // Atualizar empresa_id no rascunho
            Database::execute("UPDATE diagnosticos_rascunho SET empresa_id = :empresa_id WHERE id = :id", 
                             ['empresa_id' => $empresaId, 'id' => $rascunhoId]);
        }
        
        if (!$empresaId) {
            return false;
        }
        
        // Criar diagnóstico finalizado
        $diagnosticoId = self::criar([
            'empresa_id' => $empresaId,
            'usuario_id' => $rascunho['usuario_id'],
            'respostas' => $respostasCompletas,
            'pontuacao' => 0, // Será calculado depois
            'status' => 'concluido'
        ]);
        
        if ($diagnosticoId) {
            // Marcar rascunho como concluído
            Database::execute("UPDATE diagnosticos_rascunho SET status = 'concluido' WHERE id = :id", ['id' => $rascunhoId]);
        }
        
        return $diagnosticoId;
    }

    /**
     * Listar diagnósticos por usuário ou empresa
     */
    public static function listarPorUsuario(int $usuarioId): array
    {
        return Database::query(
            "SELECT d.*, e.nome as empresa_nome 
             FROM diagnosticos d 
             LEFT JOIN empresas e ON d.empresa_id = e.id 
             WHERE d.usuario_id = :usuario_id 
             ORDER BY d.criado_em DESC",
            ['usuario_id' => $usuarioId]
        );
    }

    /**
     * Salvar sites de referência sugeridos pela IA
     */
    public static function salvarSitesReferencia(int $empresaId, array $sites): bool
    {
        if (empty($sites)) {
            return true;
        }
        
        // Limpar sites anteriores sugeridos por IA
        Database::execute("DELETE FROM empresa_perfil_busca WHERE empresa_id = :empresa_id AND sugerido_por_ia = 1", 
                         ['empresa_id' => $empresaId]);
        
        // Inserir novos sites
        foreach ($sites as $site) {
            if (!empty($site['url'])) {
                Database::execute(
                    "INSERT INTO empresa_perfil_busca (empresa_id, site_url, categoria, sugerido_por_ia, criado_em) 
                     VALUES (:empresa_id, :site_url, :categoria, 1, NOW())",
                    [
                        'empresa_id' => $empresaId,
                        'site_url' => $site['url'],
                        'categoria' => $site['categoria'] ?? 'Geral'
                    ]
                );
            }
        }
        
        return true;
    }
}
