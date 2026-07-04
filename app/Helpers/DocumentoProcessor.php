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