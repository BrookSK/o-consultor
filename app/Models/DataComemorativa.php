<?php
/**
 * Model DataComemorativa — Base normalizada de datas (spec §6.3)
 *
 * Datas globais (empresa_id NULL) formam a base compartilhada; datas
 * específicas de uma empresa têm empresa_id preenchido. A relevância é
 * classificada pela IA por empresa (spec §6.4). Isolamento: as consultas de
 * uma empresa retornam suas datas específicas + as globais.
 *
 * Tolerante à ausência da tabela (migration 054): leituras retornam vazio.
 */

class DataComemorativa
{
    public const TIPOS = ['nacional','internacional','regional','estadual','municipal',
                          'profissional','comercial','sazonal','setorial','institucional'];
    public const RELEVANCIAS = ['alta','media','baixa','nao_recomendada'];

    /**
     * Lista datas visíveis para a empresa (específicas + globais).
     * Por padrão traz apenas alta/média relevância (spec §6.4), a menos que
     * $incluirBaixa seja true.
     *
     * @param int  $empresaId
     * @param bool $incluirBaixa  inclui baixa relevância e não classificadas
     */
    public static function listarParaEmpresa(int $empresaId, bool $incluirBaixa = false): array
    {
        try {
            $sql = "SELECT * FROM datas_comemorativas
                    WHERE ativo = 1 AND (empresa_id = :empresa_id OR empresa_id IS NULL)";
            if (!$incluirBaixa) {
                $sql .= " AND (relevancia IN ('alta','media'))";
            }
            $sql .= " ORDER BY FIELD(relevancia,'alta','media','baixa','nao_recomendada'), mes, dia";
            return Database::query($sql, ['empresa_id' => $empresaId]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Datas próximas dentro de uma janela de dias a partir de hoje (para o
     * calendário e a Visão Geral). Considera recorrência anual (mês/dia) e
     * datas únicas.
     */
    public static function proximas(int $empresaId, int $janelaDias = 60, bool $incluirBaixa = false): array
    {
        $datas = self::listarParaEmpresa($empresaId, $incluirBaixa);
        $hoje = new DateTimeImmutable('today');
        $limite = $hoje->modify("+{$janelaDias} days");
        $resultado = [];

        foreach ($datas as $d) {
            $ocorrencia = self::proximaOcorrencia($d, $hoje);
            if ($ocorrencia === null) continue;
            if ($ocorrencia <= $limite) {
                $d['proxima_ocorrencia'] = $ocorrencia->format('Y-m-d');
                $d['dias_ate'] = (int) $hoje->diff($ocorrencia)->days;
                $resultado[] = $d;
            }
        }

        usort($resultado, fn($a, $b) => strcmp($a['proxima_ocorrencia'], $b['proxima_ocorrencia']));
        return $resultado;
    }

    /**
     * Calcula a próxima data de ocorrência de uma data comemorativa a partir
     * de uma data de referência. Retorna DateTimeImmutable ou null.
     */
    public static function proximaOcorrencia(array $d, ?DateTimeImmutable $ref = null): ?DateTimeImmutable
    {
        $ref = $ref ?? new DateTimeImmutable('today');

        // Data única (não recorrente).
        if (($d['recorrencia'] ?? 'anual') === 'unica' && !empty($d['data_unica'])) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', substr((string) $d['data_unica'], 0, 10));
            if (!$dt) return null;
            return $dt >= $ref ? $dt : null;
        }

        // Recorrência anual (mês/dia).
        $mes = (int) ($d['mes'] ?? 0);
        $dia = (int) ($d['dia'] ?? 0);
        if ($mes < 1 || $mes > 12 || $dia < 1 || $dia > 31) return null;

        $ano = (int) $ref->format('Y');
        $tentativa = self::montarData($ano, $mes, $dia);
        if ($tentativa !== null && $tentativa < $ref) {
            $tentativa = self::montarData($ano + 1, $mes, $dia);
        }
        return $tentativa;
    }

    private static function montarData(int $ano, int $mes, int $dia): ?DateTimeImmutable
    {
        if (!checkdate($mes, $dia, $ano)) return null;
        return DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $ano, $mes, $dia)) ?: null;
    }

    /**
     * Cria uma data comemorativa (global se $empresaId for null).
     */
    public static function criar(?int $empresaId, array $dados): int|false
    {
        try {
            $ok = Database::execute(
                "INSERT INTO datas_comemorativas
                    (empresa_id, nome, tipo, mes, dia, data_unica, recorrencia, pais, estado,
                     municipio, regiao, nichos, subnichos, relevancia, antecedencia_dias, fonte, ativo, criado_em)
                 VALUES
                    (:empresa_id, :nome, :tipo, :mes, :dia, :data_unica, :recorrencia, :pais, :estado,
                     :municipio, :regiao, :nichos, :subnichos, :relevancia, :antecedencia, :fonte, 1, NOW())",
                [
                    'empresa_id'  => $empresaId,
                    'nome'        => $dados['nome'],
                    'tipo'        => in_array($dados['tipo'] ?? '', self::TIPOS, true) ? $dados['tipo'] : 'nacional',
                    'mes'         => isset($dados['mes']) && $dados['mes'] !== '' ? (int) $dados['mes'] : null,
                    'dia'         => isset($dados['dia']) && $dados['dia'] !== '' ? (int) $dados['dia'] : null,
                    'data_unica'  => $dados['data_unica'] ?? null,
                    'recorrencia' => in_array($dados['recorrencia'] ?? 'anual', ['anual','unica'], true) ? $dados['recorrencia'] : 'anual',
                    'pais'        => $dados['pais'] ?? 'Brasil',
                    'estado'      => $dados['estado'] ?? null,
                    'municipio'   => $dados['municipio'] ?? null,
                    'regiao'      => $dados['regiao'] ?? null,
                    'nichos'      => isset($dados['nichos']) ? json_encode(array_values((array) $dados['nichos']), JSON_UNESCAPED_UNICODE) : null,
                    'subnichos'   => isset($dados['subnichos']) ? json_encode(array_values((array) $dados['subnichos']), JSON_UNESCAPED_UNICODE) : null,
                    'relevancia'  => in_array($dados['relevancia'] ?? '', self::RELEVANCIAS, true) ? $dados['relevancia'] : null,
                    'antecedencia'=> (int) ($dados['antecedencia_dias'] ?? 7),
                    'fonte'       => $dados['fonte'] ?? null,
                ]
            );
            return $ok ? (int) Database::lastInsertId() : false;
        } catch (\Throwable $e) {
            Logger::error('Erro ao criar data comemorativa', ['erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualiza a relevância de uma data para uma empresa. Se a data for global,
     * cria uma cópia específica da empresa com a relevância classificada, para
     * não afetar outras empresas (spec: revisão/reutilização por empresa).
     */
    public static function definirRelevancia(int $dataId, int $empresaId, string $relevancia): bool
    {
        if (!in_array($relevancia, self::RELEVANCIAS, true)) return false;
        try {
            $data = Database::queryOne("SELECT * FROM datas_comemorativas WHERE id = :id LIMIT 1", ['id' => $dataId]);
            if (!$data) return false;

            // Data já específica da empresa: atualiza direto.
            if ((int) ($data['empresa_id'] ?? 0) === $empresaId) {
                return Database::execute(
                    "UPDATE datas_comemorativas SET relevancia = :r, atualizado_em = NOW() WHERE id = :id AND empresa_id = :eid",
                    ['r' => $relevancia, 'id' => $dataId, 'eid' => $empresaId]
                );
            }

            // Data global: cria cópia específica classificada para a empresa.
            $copia = $data;
            unset($copia['id']);
            $copia['empresa_id'] = $empresaId;
            $copia['relevancia'] = $relevancia;
            $copia['fonte'] = ($data['fonte'] ?? '') . ' (classificada p/ empresa)';
            $copia['nichos'] = isset($data['nichos']) ? json_decode((string) $data['nichos'], true) : null;
            $copia['subnichos'] = isset($data['subnichos']) ? json_decode((string) $data['subnichos'], true) : null;
            return (bool) self::criar($empresaId, $copia);
        } catch (\Throwable $e) {
            Logger::error('Erro ao definir relevância de data', ['erro' => $e->getMessage()]);
            return false;
        }
    }
}
