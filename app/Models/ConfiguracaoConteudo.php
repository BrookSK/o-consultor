<?php
/**
 * Model ConfiguracaoConteudo — Configurações de Conteúdo por empresa
 *
 * Guarda o padrão da empresa para a Central e a Máquina de Conteúdo:
 * frequência, redes/formatos, região/fuso, fontes permitidas, geração de
 * imagens padrão e regras anti-repetição de temas.
 *
 * Tabela: configuracoes_conteudo (migration 054). Uma linha por empresa.
 * Tolerante à ausência da tabela (retorna defaults) para não quebrar o
 * fluxo atual caso a migration ainda não tenha rodado.
 */

class ConfiguracaoConteudo
{
    /** Colunas JSON que devem ser decodificadas na leitura. */
    private const CAMPOS_JSON = ['redes_sociais', 'formatos_preferidos'];

    /**
     * Valores padrão de uma empresa que ainda não configurou nada.
     * Servem também como fallback quando a tabela não existe.
     */
    public static function padroes(): array
    {
        return [
            'frequencia_padrao'          => 'semanal',
            'redes_sociais'              => ['instagram'],
            'formatos_preferidos'        => ['carrossel', 'post'],
            'idioma'                     => 'Português',
            'pais'                       => 'Brasil',
            'estado'                     => null,
            'cidade'                     => null,
            'regiao'                     => null,
            'fuso_horario'               => 'America/Sao_Paulo',
            'antecedencia_datas_dias'    => 7,
            'qtd_sugestoes_semanais'     => 3,
            'permitir_noticias'          => 1,
            'permitir_concorrencia'      => 1,
            'permitir_datas_comemorativas' => 1,
            'gerar_imagens_padrao'       => 1,
            'evitar_repeticao_temas'     => 1,
            'periodo_repeticao_dias'     => 30,
        ];
    }

    /**
     * Obtém as configurações de uma empresa, sempre mescladas com os padrões
     * (garante que todas as chaves existam). Campos JSON já vêm decodificados.
     */
    public static function obter(int $empresaId): array
    {
        $padroes = self::padroes();

        try {
            $row = Database::queryOne(
                "SELECT * FROM configuracoes_conteudo WHERE empresa_id = :empresa_id LIMIT 1",
                ['empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            // Tabela ausente (migration 054 não rodada): usa padrões.
            return $padroes;
        }

        if (!$row) {
            return $padroes;
        }

        // Decodifica os campos JSON.
        foreach (self::CAMPOS_JSON as $campo) {
            if (isset($row[$campo]) && is_string($row[$campo])) {
                $decodificado = json_decode($row[$campo], true);
                $row[$campo] = is_array($decodificado) ? $decodificado : $padroes[$campo];
            }
        }

        // Mescla com padrões para preencher chaves ausentes.
        return array_merge($padroes, array_filter($row, fn($v) => $v !== null));
    }

    /**
     * Retorna se a empresa gera imagens automaticamente por padrão.
     */
    public static function gerarImagensPadrao(int $empresaId): bool
    {
        $config = self::obter($empresaId);
        return (int) ($config['gerar_imagens_padrao'] ?? 1) === 1;
    }

    /**
     * Salva (insert/update) as configurações de conteúdo de uma empresa.
     * Aceita apenas as chaves conhecidas; campos JSON são serializados.
     *
     * @param array $dados Pares chave=>valor (subconjunto das colunas)
     */
    public static function salvar(int $empresaId, array $dados): bool
    {
        $permitidas = array_keys(self::padroes());

        $colunas = [];
        $params = ['empresa_id' => $empresaId];

        foreach ($permitidas as $chave) {
            if (!array_key_exists($chave, $dados)) {
                continue;
            }
            $valor = $dados[$chave];

            if (in_array($chave, self::CAMPOS_JSON, true)) {
                $valor = json_encode(array_values((array) $valor), JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($valor)) {
                $valor = $valor ? 1 : 0;
            }

            $colunas[$chave] = $valor;
            $params[$chave] = $valor;
        }

        if (empty($colunas)) {
            return false;
        }

        try {
            $existe = Database::queryOne(
                "SELECT id FROM configuracoes_conteudo WHERE empresa_id = :empresa_id LIMIT 1",
                ['empresa_id' => $empresaId]
            );

            if ($existe) {
                $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($colunas)));
                return Database::execute(
                    "UPDATE configuracoes_conteudo SET {$sets}, atualizado_em = NOW() WHERE empresa_id = :empresa_id",
                    $params
                );
            }

            $cols = implode(', ', array_keys($colunas));
            $placeholders = implode(', ', array_map(fn($c) => ":{$c}", array_keys($colunas)));
            return Database::execute(
                "INSERT INTO configuracoes_conteudo (empresa_id, {$cols}, criado_em) VALUES (:empresa_id, {$placeholders}, NOW())",
                $params
            );
        } catch (\Throwable $e) {
            Logger::error('Erro ao salvar configuracoes_conteudo', ['empresa_id' => $empresaId, 'erro' => $e->getMessage()]);
            return false;
        }
    }
}
