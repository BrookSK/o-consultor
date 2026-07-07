<?php
/**
 * DocumentoProcessor — Processamento de documentos da empresa para IA
 * O Consultor — Sistema Operacional Empresarial
 */

class DocumentoProcessor
{
    private const UPLOAD_DIR = 'documentos';
    private const MAX_FILE_SIZE = 5242880; // 5MB (reduced from 10MB)
    private const ALLOWED_TYPES = ['pdf', 'doc', 'docx', 'txt', 'md', 'rtf'];
    
    /**
     * Verificar se o sistema de documentos está disponível
     */
    public static function isAvailable(): bool
    {
        try {
            // Verificar se as constantes necessárias estão definidas
            if (!defined('UPLOAD_PATH')) {
                return false;
            }
            
            // Verificar se o diretório base existe
            if (!is_dir(UPLOAD_PATH)) {
                return false;
            }
            
            // Verificar se a tabela existe (teste simples)
            Database::queryOne("SELECT 1 FROM documentos_empresa LIMIT 1");
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Processar upload de múltiplos documentos
     */
    public static function processarUploads(array $arquivos, int $empresaId, int $usuarioId): array
    {
        $resultados = [];
        
        // Verificar se é array no formato esperado pelo HTML form upload
        if (!isset($arquivos['tmp_name']) || !is_array($arquivos['tmp_name'])) {
            return [['sucesso' => false, 'arquivo' => 'desconhecido', 'erro' => 'Formato de upload inválido']];
        }
        
        $totalFiles = count($arquivos['tmp_name']);
        
        for ($index = 0; $index < $totalFiles; $index++) {
            if ($arquivos['error'][$index] !== UPLOAD_ERR_OK) {
                $resultados[] = [
                    'sucesso' => false,
                    'arquivo' => $arquivos['name'][$index] ?? 'arquivo_' . $index,
                    'erro' => self::getUploadErrorMessage($arquivos['error'][$index])
                ];
                continue;
            }
            
            $resultado = self::processarArquivoUnico([
                'name' => $arquivos['name'][$index],
                'tmp_name' => $arquivos['tmp_name'][$index],
                'size' => $arquivos['size'][$index],
                'type' => $arquivos['type'][$index]
            ], $empresaId, $usuarioId);
            
            $resultados[] = $resultado;
        }
        
        return $resultados;
    }
    
    /**
     * Processar um único arquivo
     */
    private static function processarArquivoUnico(array $arquivo, int $empresaId, int $usuarioId): array
    {
        try {
            // Validações básicas
            $validacao = self::validarArquivo($arquivo);
            if (!$validacao['valido']) {
                return [
                    'sucesso' => false,
                    'arquivo' => $arquivo['name'],
                    'erro' => $validacao['erro']
                ];
            }
            
            // Gerar hash para verificar duplicatas
            $hashArquivo = hash_file('sha256', $arquivo['tmp_name']);
            
            // Verificar se já existe
            $existente = Database::queryOne(
                "SELECT id, nome_original FROM documentos_empresa WHERE empresa_id = :empresa_id AND hash_arquivo = :hash",
                ['empresa_id' => $empresaId, 'hash' => $hashArquivo]
            );
            
            if ($existente) {
                return [
                    'sucesso' => false,
                    'arquivo' => $arquivo['name'],
                    'erro' => 'Documento já existe: ' . $existente['nome_original']
                ];
            }
            
            // Salvar arquivo
            $resultadoSalvar = self::salvarArquivo($arquivo, $empresaId);
            if (!$resultadoSalvar['sucesso']) {
                return [
                    'sucesso' => false,
                    'arquivo' => $arquivo['name'],
                    'erro' => $resultadoSalvar['erro']
                ];
            }
            
            $caminhoArquivo = $resultadoSalvar['caminho_relativo'];
            $caminhoCompleto = $resultadoSalvar['caminho_completo'];
            
            // Detectar tipo de documento
            $tipoDocumento = self::detectarTipoDocumento($arquivo['name']);
            
            // Inserir no banco
            $documentoId = Database::execute(
                "INSERT INTO documentos_empresa (empresa_id, usuario_id, nome_arquivo, nome_original, caminho_arquivo, tipo_documento, tipo_mime, tamanho_bytes, hash_arquivo) 
                 VALUES (:empresa_id, :usuario_id, :nome_arquivo, :nome_original, :caminho, :tipo_doc, :tipo_mime, :tamanho, :hash)",
                [
                    'empresa_id' => $empresaId,
                    'usuario_id' => $usuarioId,
                    'nome_arquivo' => basename($caminhoArquivo),
                    'nome_original' => $arquivo['name'],
                    'caminho' => $caminhoArquivo,
                    'tipo_doc' => $tipoDocumento,
                    'tipo_mime' => $arquivo['type'],
                    'tamanho' => $arquivo['size'],
                    'hash' => $hashArquivo
                ]
            );
            
            if (!$documentoId) {
                // Remover arquivo se falhou inserção
                if (file_exists($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }
                return [
                    'sucesso' => false,
                    'arquivo' => $arquivo['name'],
                    'erro' => 'Falha ao registrar documento no banco'
                ];
            }
            
            $documentoId = Database::lastInsertId();
            
            // Processar com IA se configurado
            if (Configuracao::get('docs_ia_auto_process', '1') === '1') {
                self::processarComIA($documentoId, $caminhoArquivo);
            }
            
            Logger::acao('Documento uploaded', [
                'documento_id' => $documentoId,
                'empresa_id' => $empresaId,
                'arquivo' => $arquivo['name'],
                'tamanho' => $arquivo['size']
            ]);
            
            return [
                'sucesso' => true,
                'arquivo' => $arquivo['name'],
                'documento_id' => $documentoId,
                'tipo_detectado' => $tipoDocumento,
                'tamanho' => self::formatarTamanho($arquivo['size'])
            ];
            
        } catch (Exception $e) {
            Logger::error('Erro no processamento de documento', [
                'arquivo' => $arquivo['name'],
                'erro' => $e->getMessage()
            ]);
            
            return [
                'sucesso' => false,
                'arquivo' => $arquivo['name'],
                'erro' => 'Erro interno no processamento'
            ];
        }
    }
    
    /**
     * Validar arquivo antes do processamento
     */
    private static function validarArquivo(array $arquivo): array
    {
        if ($arquivo['size'] > self::MAX_FILE_SIZE) {
            return [
                'valido' => false,
                'erro' => 'Arquivo muito grande. Máximo: 5MB'
            ];
        }
        
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, self::ALLOWED_TYPES)) {
            return [
                'valido' => false,
                'erro' => 'Tipo não permitido. Permitidos: ' . implode(', ', self::ALLOWED_TYPES)
            ];
        }
        
        return ['valido' => true];
    }
    
    /**
     * Salvar arquivo no servidor
     */
    private static function salvarArquivo(array $arquivo, int $empresaId): array
    {
        $dir = UPLOAD_PATH . self::UPLOAD_DIR . '/' . $empresaId;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return ['sucesso' => false, 'erro' => 'Falha ao criar diretório'];
            }
        }
        
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nomeSeguro = 'doc_' . uniqid() . '_' . time() . '.' . $extensao;
        $caminhoCompleto = $dir . '/' . $nomeSeguro;
        $caminhoRelativo = '/uploads/' . self::UPLOAD_DIR . '/' . $empresaId . '/' . $nomeSeguro;
        
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            return [
                'sucesso' => true,
                'caminho_relativo' => $caminhoRelativo,
                'caminho_completo' => $caminhoCompleto
            ];
        }
        
        return ['sucesso' => false, 'erro' => 'Falha no upload do arquivo'];
    }
    
    /**
     * Detectar tipo de documento baseado no nome/conteúdo
     */
    private static function detectarTipoDocumento(string $nomeArquivo): string
    {
        $nome = strtolower($nomeArquivo);
        
        if (strpos($nome, 'manual') !== false) return 'manual';
        if (strpos($nome, 'procedimento') !== false) return 'procedimento';
        if (strpos($nome, 'politica') !== false) return 'politica';
        if (strpos($nome, 'fluxo') !== false) return 'fluxograma';
        if (strpos($nome, 'checklist') !== false) return 'checklist';
        if (strpos($nome, 'template') !== false) return 'template';
        if (strpos($nome, 'organograma') !== false) return 'organograma';
        
        return 'outro';
    }
    
    /**
     * Processar documento com IA
     */
    private static function processarComIA(int $documentoId, string $caminhoArquivo): void
    {
        try {
            // Extrair texto do documento
            $textoExtraido = self::extrairTexto($caminhoArquivo);
            if (!$textoExtraido) {
                return;
            }
            
            // Chamar IA para análise
            $prompt = self::buildPromptAnaliseDocumento($textoExtraido);
            $analiseIA = ApiHelper::chamarAnalise($prompt, true);
            
            if ($analiseIA['sucesso'] && is_array($analiseIA['conteudo'])) {
                $insights = $analiseIA['conteudo'];
                
                // Salvar resultado da análise
                Database::execute(
                    "UPDATE documentos_empresa 
                     SET processado_ia = 1, conteudo_extraido = :conteudo, insights_ia = :insights, 
                         areas_relacionadas = :areas, processos_identificados = :processos, data_processamento = NOW()
                     WHERE id = :id",
                    [
                        'id' => $documentoId,
                        'conteudo' => $textoExtraido,
                        'insights' => json_encode($insights['insights'] ?? []),
                        'areas' => json_encode($insights['areas'] ?? []),
                        'processos' => json_encode($insights['processos'] ?? [])
                    ]
                );
                
                // Gerar tags automaticamente
                if (!empty($insights['tags'])) {
                    foreach ($insights['tags'] as $tag) {
                        Database::execute(
                            "INSERT IGNORE INTO documento_tags (documento_id, tag, relevancia, origem) 
                             VALUES (:doc_id, :tag, :relevancia, 'ia_automatica')",
                            [
                                'doc_id' => $documentoId,
                                'tag' => $tag['nome'],
                                'relevancia' => $tag['relevancia'] ?? 1.0
                            ]
                        );
                    }
                }
            }
            
        } catch (Exception $e) {
            Logger::error('Erro no processamento IA de documento', [
                'documento_id' => $documentoId,
                'erro' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Extrai texto de um arquivo pelo CAMINHO ABSOLUTO no disco.
     * Suporta txt, md, rtf, csv, html, docx e pdf (best-effort, sem dependências externas).
     * Usado pela personalização de serviço (upload direto para geração de SOP).
     */
    public static function extrairTextoDeArquivoAbsoluto(string $caminhoCompleto, string $nomeOriginal = ''): string
    {
        if (!file_exists($caminhoCompleto)) {
            return '';
        }

        $nome = $nomeOriginal !== '' ? $nomeOriginal : $caminhoCompleto;
        $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        switch ($extensao) {
            case 'txt':
            case 'md':
            case 'csv':
            case 'log':
                return self::limparTexto((string) file_get_contents($caminhoCompleto));

            case 'rtf':
                return self::limparTexto(self::rtfParaTexto((string) file_get_contents($caminhoCompleto)));

            case 'html':
            case 'htm':
                return self::limparTexto(strip_tags((string) file_get_contents($caminhoCompleto)));

            case 'docx':
                return self::limparTexto(self::extrairTextoDocx($caminhoCompleto));

            case 'pdf':
                return self::limparTexto(self::extrairTextoPdf($caminhoCompleto));

            case 'doc':
                // .doc binário antigo: tentativa best-effort de extrair strings legíveis
                return self::limparTexto(self::extrairStringsLegiveis((string) file_get_contents($caminhoCompleto)));

            default:
                return '';
        }
    }

    /**
     * Extrai texto de DOCX (é um ZIP com word/document.xml).
     */
    private static function extrairTextoDocx(string $caminho): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }
        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            return '';
        }
        // Preservar quebras de parágrafo/linha antes de remover tags
        $xml = str_replace(['</w:p>', '<w:br/>', '<w:br />'], "\n", $xml);
        return strip_tags($xml);
    }

    /**
     * Extração best-effort de texto de PDF SEM dependências externas.
     * 1) Tenta 'pdftotext' (poppler) se disponível no servidor (melhor resultado).
     * 2) Caso contrário, faz parsing dos streams do PDF descomprimindo com gzuncompress.
     */
    private static function extrairTextoPdf(string $caminho): string
    {
        // 1) pdftotext (se instalado)
        if (function_exists('exec')) {
            $disabled = explode(',', str_replace(' ', '', (string) ini_get('disable_functions')));
            if (!in_array('exec', $disabled, true)) {
                $saidaTmp = tempnam(sys_get_temp_dir(), 'pdftxt_') . '.txt';
                $cmd = 'pdftotext -q -enc UTF-8 ' . escapeshellarg($caminho) . ' ' . escapeshellarg($saidaTmp) . ' 2>/dev/null';
                @exec($cmd, $out, $code);
                if ($code === 0 && file_exists($saidaTmp)) {
                    $texto = (string) file_get_contents($saidaTmp);
                    @unlink($saidaTmp);
                    if (trim($texto) !== '') {
                        return $texto;
                    }
                }
                if (file_exists($saidaTmp)) {
                    @unlink($saidaTmp);
                }
            }
        }

        // 2) Fallback: parser nativo dos streams do PDF (sem dependências)
        $conteudo = (string) file_get_contents($caminho);
        $texto = '';

        // Descomprimir todos os streams (FlateDecode) e concatenar o conteúdo
        // descomprimido + o conteúdo bruto (alguns PDFs têm texto não comprimido).
        $blocos = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $conteudo, $streams)) {
            foreach ($streams[1] as $stream) {
                $decodificado = @gzuncompress($stream);
                if ($decodificado === false) $decodificado = @gzinflate($stream);
                if ($decodificado === false) $decodificado = @gzdecode($stream);
                if ($decodificado !== false && $decodificado !== '') {
                    $blocos[] = $decodificado;
                }
            }
        }
        // Sempre analisar também o conteúdo bruto (cobre PDFs sem compressão)
        $blocos[] = $conteudo;

        foreach ($blocos as $alvo) {
            $texto .= self::extrairOperadoresTextoPdf($alvo);
        }

        // Se ainda não veio nada útil, tentar extrair strings legíveis do arquivo todo
        if (trim($texto) === '') {
            $texto = self::extrairStringsLegiveis($conteudo);
        }

        return $texto;
    }

    /**
     * Extrai texto dos operadores de texto de um bloco PDF (Tj, TJ, ', "),
     * suportando strings entre parênteses (…) e strings hexadecimais <…>.
     */
    private static function extrairOperadoresTextoPdf(string $alvo): string
    {
        $texto = '';

        // 1) Strings entre parênteses seguidas de Tj / TJ / ' / "
        if (preg_match_all('/\(((?:\\\\.|[^\\\\()])*)\)\s*(?:T[jJ]|\'|")/', $alvo, $m)) {
            foreach ($m[1] as $trecho) {
                $texto .= self::decodePdfString($trecho) . ' ';
            }
        }

        // 2) Arrays TJ: [(a) -10 (b) 5 (c)] TJ  — inclusive com strings hex
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $alvo, $arr)) {
            foreach ($arr[1] as $bloco) {
                // parênteses dentro do array
                if (preg_match_all('/\(((?:\\\\.|[^\\\\()])*)\)/', $bloco, $mm)) {
                    foreach ($mm[1] as $trecho) {
                        $texto .= self::decodePdfString($trecho);
                    }
                }
                // strings hex dentro do array
                if (preg_match_all('/<([0-9A-Fa-f\s]+)>/', $bloco, $hh)) {
                    foreach ($hh[1] as $hex) {
                        $texto .= self::hexPdfParaTexto($hex);
                    }
                }
                $texto .= ' ';
            }
        }

        // 3) Strings hexadecimais seguidas de Tj/TJ: <0048...> Tj
        if (preg_match_all('/<([0-9A-Fa-f\s]+)>\s*T[jJ]/', $alvo, $hx)) {
            foreach ($hx[1] as $hex) {
                $texto .= self::hexPdfParaTexto($hex) . ' ';
            }
        }

        return $texto;
    }

    /**
     * Converte uma string hexadecimal de PDF em texto (UTF-16BE ou Latin/ASCII).
     */
    private static function hexPdfParaTexto(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex);
        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }
        $bin = @hex2bin($hex);
        if ($bin === false) {
            return '';
        }
        // Heurística: se parecer UTF-16BE (bytes altos zero alternados), converter
        if (strlen($bin) >= 2 && $bin[0] === "\x00") {
            $conv = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE');
            if ($conv !== false && trim($conv) !== '') {
                return $conv;
            }
        }
        return $bin;
    }

    /**
     * Decodifica sequências de escape de string PDF ( \( \) \\ \n etc ).
     */
    private static function decodePdfString(string $s): string
    {
        $map = ['\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\(' => '(', '\\)' => ')', '\\\\' => '\\'];
        return strtr($s, $map);
    }

    /**
     * Converte RTF para texto simples (remoção básica de grupos de controle).
     */
    private static function rtfParaTexto(string $rtf): string
    {
        $texto = preg_replace('/\\\\[a-z]+-?\d* ?/i', ' ', $rtf);
        $texto = str_replace(['{', '}'], '', (string) $texto);
        return (string) $texto;
    }

    /**
     * Extrai apenas sequências legíveis de um blob binário (fallback p/ .doc).
     */
    private static function extrairStringsLegiveis(string $bin): string
    {
        if (preg_match_all('/[\x20-\x7E\xC0-\xFF][\x20-\x7E\xC0-\xFF\s]{4,}/', $bin, $m)) {
            return implode(' ', $m[0]);
        }
        return '';
    }

    /**
     * Normaliza texto extraído: colapsa espaços, remove caracteres de controle.
     */
    private static function limparTexto(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $texto);
        $texto = preg_replace('/[ \t]+/', ' ', (string) $texto);
        $texto = preg_replace('/\n{3,}/', "\n\n", (string) $texto);
        return trim((string) $texto);
    }

    /**
     * Extrair texto de diferentes tipos de documento
     */
    private static function extrairTexto(string $caminhoArquivo): ?string
    {
        $extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));
        $caminhoCompleto = PUBLIC_PATH . $caminhoArquivo;
        
        if (!file_exists($caminhoCompleto)) {
            return null;
        }
        
        switch ($extensao) {
            case 'txt':
            case 'md':
                return file_get_contents($caminhoCompleto);
                
            case 'pdf':
                // Para PDF, seria necessário uma biblioteca como pdftotext
                // Por simplicidade, retornar placeholder
                return "PDF: " . basename($caminhoArquivo) . " (extração de texto PDF requer biblioteca adicional)";
                
            case 'doc':
            case 'docx':
                // Para DOC/DOCX, seria necessário uma biblioteca como PhpSpreadsheet
                return "DOC: " . basename($caminhoArquivo) . " (extração de texto DOC requer biblioteca adicional)";
                
            default:
                return null;
        }
    }
    
    /**
     * Montar prompt para análise do documento
     */
    private static function buildPromptAnaliseDocumento(string $textoDocumento): string
    {
        return "Analise o seguinte documento interno da empresa e extraia informações estruturadas:

DOCUMENTO:
{$textoDocumento}

Extraia e retorne em JSON:
1. insights: Array com principais insights do documento
2. areas: Array com áreas/departamentos relacionados
3. processos: Array com processos identificados
4. tags: Array com tags relevantes (formato: {\"nome\": \"tag\", \"relevancia\": 0.8})

Exemplo de resposta:
{
  \"insights\": [\"Processo de vendas bem estruturado\", \"Falta documentação de aprovações\"],
  \"areas\": [\"Comercial\", \"Financeiro\"],
  \"processos\": [\"Prospecção\", \"Aprovação de crédito\", \"Faturamento\"],
  \"tags\": [{\"nome\": \"vendas\", \"relevancia\": 0.9}, {\"nome\": \"aprovacao\", \"relevancia\": 0.7}]
}";
    }
    
    /**
     * Buscar documentos relevantes para contexto
     */
    public static function buscarDocumentosRelevantes(int $empresaId, array $areas = [], array $tags = []): array
    {
        $whereConditions = ['d.empresa_id = :empresa_id', 'd.ativo = 1', 'd.processado_ia = 1'];
        $params = ['empresa_id' => $empresaId];
        
        if (!empty($areas)) {
            $placeholders = [];
            foreach ($areas as $index => $area) {
                $placeholder = "area_{$index}";
                $placeholders[] = ":{$placeholder}";
                $params[$placeholder] = '%' . $area . '%';
            }
            $whereConditions[] = "(JSON_SEARCH(d.areas_relacionadas, 'one', " . implode(") IS NOT NULL OR JSON_SEARCH(d.areas_relacionadas, 'one', ", $placeholders) . ") IS NOT NULL)";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        return Database::query(
            "SELECT d.*, GROUP_CONCAT(t.tag) as tags_string
             FROM documentos_empresa d
             LEFT JOIN documento_tags t ON d.id = t.documento_id
             WHERE {$whereClause}
             GROUP BY d.id
             ORDER BY d.atualizado_em DESC
             LIMIT 10",
            $params
        );
    }
    
    /**
     * Construir contexto de documentos para prompt da IA
     */
    public static function construirContextoDocumentos(array $documentos): string
    {
        if (empty($documentos)) {
            return '';
        }
        
        $contexto = "\n\n=== DOCUMENTOS INTERNOS DA EMPRESA ===\n";
        
        foreach ($documentos as $doc) {
            $contexto .= "\n[{$doc['tipo_documento']}] {$doc['nome_original']}:\n";
            
            if (!empty($doc['insights_ia'])) {
                $insights = json_decode($doc['insights_ia'], true);
                if (is_array($insights)) {
                    $contexto .= "Insights: " . implode(', ', $insights) . "\n";
                }
            }
            
            if (!empty($doc['processos_identificados'])) {
                $processos = json_decode($doc['processos_identificados'], true);
                if (is_array($processos)) {
                    $contexto .= "Processos: " . implode(', ', $processos) . "\n";
                }
            }
            
            // Incluir parte do conteúdo (limitado)
            if (!empty($doc['conteudo_extraido'])) {
                $preview = substr($doc['conteudo_extraido'], 0, 500);
                $contexto .= "Conteúdo: {$preview}...\n";
            }
            
            $contexto .= "---\n";
        }
        
        return $contexto;
    }
    
    /**
     * Registrar uso de documentos
     */
    public static function registrarUso(int $documentoId, int $empresaId, int $usuarioId, string $contexto, ?int $referenciaId = null): void
    {
        try {
            Database::execute(
                "INSERT INTO log_uso_documentos (documento_id, empresa_id, usuario_id, contexto_uso, referencia_id) 
                 VALUES (:doc_id, :empresa_id, :usuario_id, :contexto, :ref_id)",
                [
                    'doc_id' => $documentoId,
                    'empresa_id' => $empresaId,
                    'usuario_id' => $usuarioId,
                    'contexto' => $contexto,
                    'ref_id' => $referenciaId
                ]
            );
        } catch (Exception $e) {
            Logger::error('Erro ao registrar uso de documento', ['erro' => $e->getMessage()]);
        }
    }
    
    /**
     * Obter mensagens de erro de upload
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo muito grande';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload incompleto';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever arquivo';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bloqueado por extensão';
            default:
                return 'Erro desconhecido no upload';
        }
    }
    
    /**
     * Formatar tamanho de arquivo
     */
    private static function formatarTamanho(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes) / log(1024));
        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}