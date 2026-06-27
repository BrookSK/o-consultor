<?php
/**
 * Model SOP — Standard Operating Procedures (Manual Operacional)
 * O Consultor — Sistema Operacional Empresarial
 * 
 * CADA SOP É GERADO INDIVIDUALMENTE via IA com 13 componentes completos
 */

class Sop
{
    /**
     * Buscar por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM sops WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Buscar por código SOP (ex: SOP-TI-ONB-001)
     */
    public static function buscarPorCodigo(string $sopCodigo): ?array
    {
        return Database::queryOne(
            "SELECT * FROM sops WHERE sop_codigo = :sop_codigo LIMIT 1",
            ['sop_codigo' => $sopCodigo]
        );
    }

    /**
     * Buscar por empresa
     */
    public static function buscarPorEmpresa(int $empresaId): array
    {
        return Database::query(
            "SELECT * FROM sops WHERE empresa_id = :empresa_id ORDER BY criado_em DESC",
            ['empresa_id' => $empresaId]
        );
    }

    /**
     * Criar novo SOP completo (F-05 implementation)
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO sops (empresa_id, diagnostico_id, sop_codigo, titulo, departamento, conteudo, conteudo_completo, versao, status, gerado_por_ia, criado_em) 
             VALUES (:empresa_id, :diagnostico_id, :sop_codigo, :titulo, :departamento, :conteudo, :conteudo_completo, :versao, :status, :gerado_por_ia, NOW())",
            [
                'empresa_id'        => $dados['empresa_id'],
                'diagnostico_id'    => $dados['diagnostico_id'] ?? null,
                'sop_codigo'        => $dados['sop_codigo'],
                'titulo'            => $dados['titulo'],
                'departamento'      => $dados['departamento'] ?? null,
                'conteudo'          => $dados['conteudo'] ?? 'Conteúdo gerado via IA',
                'conteudo_completo' => json_encode($dados['conteudo_completo']),
                'versao'            => $dados['versao'] ?? '1.0',
                'status'            => $dados['status'] ?? 'rascunho',
                'gerado_por_ia'     => $dados['gerado_por_ia'] ?? 1,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Atualizar SOP existente
     */
    public static function atualizar(int $id, array $dados): bool
    {
        $campos = [];
        $valores = ['id' => $id];

        foreach ($dados as $campo => $valor) {
            if (in_array($campo, ['titulo', 'departamento', 'conteudo', 'conteudo_completo', 'versao', 'status', 'motivo_alteracao'])) {
                $campos[] = "{$campo} = :{$campo}";
                $valores[$campo] = $campo === 'conteudo_completo' ? json_encode($valor) : $valor;
            }
        }

        if (empty($campos)) return false;

        $sql = "UPDATE sops SET " . implode(', ', $campos) . ", atualizado_em = NOW() WHERE id = :id";
        return Database::execute($sql, $valores);
    }

    /**
     * Aprovar SOP (muda status e salva KPIs + contingência)
     */
    public static function aprovar(int $sopId, int $usuarioId): bool
    {
        // Buscar SOP
        $sop = self::buscarPorId($sopId);
        if (!$sop) return false;

        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) return false;

        Database::beginTransaction();

        try {
            // 1. Atualizar status do SOP
            Database::execute(
                "UPDATE sops SET status = 'ativo', aprovado_em = NOW(), aprovado_por = :usuario_id WHERE id = :id",
                ['id' => $sopId, 'usuario_id' => $usuarioId]
            );

            // 2. Salvar KPIs nativos
            if (isset($conteudoCompleto['kpis']) && is_array($conteudoCompleto['kpis'])) {
                foreach ($conteudoCompleto['kpis'] as $kpi) {
                    Database::execute(
                        "INSERT INTO sop_kpis (empresa_id, sop_id, nome, meta_verde, meta_amarela, meta_vermelha, acao_vermelha) 
                         VALUES (:empresa_id, :sop_id, :nome, :meta_verde, :meta_amarela, :meta_vermelha, :acao_vermelha)",
                        [
                            'empresa_id'     => $sop['empresa_id'],
                            'sop_id'         => $sopId,
                            'nome'           => $kpi['kpi'],
                            'meta_verde'     => $kpi['verde'],
                            'meta_amarela'   => $kpi['amarela'],
                            'meta_vermelha'  => $kpi['vermelha'],
                            'acao_vermelha'  => $kpi['acao_vermelha'],
                        ]
                    );
                }
            }

            // 3. Salvar planos de contingência N1/N2/N3
            if (isset($conteudoCompleto['contencao'])) {
                $contencao = $conteudoCompleto['contencao'];
                
                foreach (['N1', 'N2', 'N3'] as $nivel) {
                    $nivelKey = strtolower($nivel);
                    if (isset($contencao[$nivelKey])) {
                        $plano = $contencao[$nivelKey];
                        Database::execute(
                            "INSERT INTO sop_contencoes (empresa_id, sop_id, nivel, situacao, acao, responsavel, prazo_resposta, escalar_se, comunicacao, documentacao_obrigatoria) 
                             VALUES (:empresa_id, :sop_id, :nivel, :situacao, :acao, :responsavel, :prazo_resposta, :escalar_se, :comunicacao, :documentacao_obrigatoria)",
                            [
                                'empresa_id'               => $sop['empresa_id'],
                                'sop_id'                   => $sopId,
                                'nivel'                    => $nivel,
                                'situacao'                 => $plano['situacao'] ?? '',
                                'acao'                     => $plano['acao'] ?? '',
                                'responsavel'              => $plano['quem'] ?? '',
                                'prazo_resposta'           => $plano['prazo'] ?? null,
                                'escalar_se'               => $plano['escalar'] ?? null,
                                'comunicacao'              => $plano['comunicacao'] ?? null,
                                'documentacao_obrigatoria' => $plano['documentacao'] ?? null,
                            ]
                        );
                    }
                }
            }

            Database::commit();
            return true;

        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao aprovar SOP', ['sop_id' => $sopId, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Incrementar versão (1.0 → 1.1)
     */
    public static function incrementarVersao(string $versaoAtual): string
    {
        $partes = explode('.', $versaoAtual);
        $maior = (int) ($partes[0] ?? 1);
        $menor = (int) ($partes[1] ?? 0);
        
        return $maior . '.' . ($menor + 1);
    }

    /**
     * Salvar histórico de versão
     */
    public static function salvarHistorico(int $sopId, string $versaoAnterior, string $versaoNova, array $conteudoAnterior, string $motivo, int $usuarioId): bool
    {
        return Database::execute(
            "INSERT INTO sop_historico_versoes (sop_id, versao_anterior, versao_nova, conteudo_anterior, motivo_alteracao, usuario_alteracao) 
             VALUES (:sop_id, :versao_anterior, :versao_nova, :conteudo_anterior, :motivo_alteracao, :usuario_alteracao)",
            [
                'sop_id'             => $sopId,
                'versao_anterior'    => $versaoAnterior,
                'versao_nova'        => $versaoNova,
                'conteudo_anterior'  => json_encode($conteudoAnterior),
                'motivo_alteracao'   => $motivo,
                'usuario_alteracao'  => $usuarioId,
            ]
        );
    }

    /**
     * Buscar KPIs de um SOP
     */
    public static function buscarKpis(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM sop_kpis WHERE sop_id = :sop_id AND ativo = 1 ORDER BY nome",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Buscar planos de contingência de um SOP
     */
    public static function buscarContencoes(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id ORDER BY nivel",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Atualizar valor atual de um KPI
     */
    public static function atualizarKpi(int $kpiId, string $valorAtual, string $zonaAtual): bool
    {
        return Database::execute(
            "UPDATE sop_kpis SET valor_atual = :valor_atual, zona_atual = :zona_atual, ultima_medicao = NOW() WHERE id = :id",
            [
                'id'           => $kpiId,
                'valor_atual'  => $valorAtual,
                'zona_atual'   => $zonaAtual,
            ]
        );
    }

    /**
     * Estatísticas dos SOPs por empresa
     */
    public static function estatisticas(int $empresaId): array
    {
        $stats = Database::queryOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN status = 'rascunho' THEN 1 ELSE 0 END) as rascunhos,
                SUM(CASE WHEN gerado_por_ia = 1 THEN 1 ELSE 0 END) as gerados_ia
             FROM sops WHERE empresa_id = :empresa_id",
            ['empresa_id' => $empresaId]
        );

        return [
            'total'       => (int) ($stats['total'] ?? 0),
            'aprovados'   => (int) ($stats['aprovados'] ?? 0),
            'rascunhos'   => (int) ($stats['rascunhos'] ?? 0),
            'gerados_ia'  => (int) ($stats['gerados_ia'] ?? 0),
        ];
    }
}
