<?php
/**
 * ApiController — Endpoints de API Interna
 * O Consultor — Sistema Operacional Empresarial
 */

class ApiController
{
    /**
     * Transcreve áudio para texto usando OpenAI Whisper
     */
    /**
     * Retorna o token CSRF atual da sessão (para clientes que precisam de um token fresco
     * antes de enviar uploads, evitando 403 por token rotacionado).
     */
    public function csrfToken(): void
    {
        header('Content-Type: application/json');
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
            exit;
        }
        echo json_encode(['sucesso' => true, 'token' => Csrf::token()]);
        exit;
    }

    public function transcricao(): void
    {
        header('Content-Type: application/json');
        
        try {
            // Exige apenas autenticação. A transcrição NÃO altera estado (só recebe áudio
            // e devolve texto), então não aplicamos CSRF aqui — evita 403 por token rotacionado.
            if (!Auth::check()) {
                http_response_code(401);
                echo json_encode(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página e faça login novamente.']);
                exit;
            }

            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Arquivo de áudio não recebido');
            }
            
            $audioFile = $_FILES['audio'];

            // Aceitar variações de mime que o navegador envia (ex.: audio/webm;codecs=opus, audio/mp4).
            $tipoBase = strtolower(trim(explode(';', (string) $audioFile['type'])[0]));
            $ext = strtolower(pathinfo($audioFile['name'] ?? '', PATHINFO_EXTENSION));
            $tiposOk = ['audio/webm', 'audio/wav', 'audio/x-wav', 'audio/mp3', 'audio/mpeg', 'audio/ogg', 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'video/webm'];
            $extsOk = ['webm', 'wav', 'mp3', 'mpeg', 'mpga', 'ogg', 'oga', 'mp4', 'm4a'];
            if (!in_array($tipoBase, $tiposOk, true) && !in_array($ext, $extsOk, true)) {
                throw new Exception('Tipo de áudio não suportado (' . ($audioFile['type'] ?: 'desconhecido') . ')');
            }
            
            // Verificar tamanho (máximo 25MB)
            if ($audioFile['size'] > 25 * 1024 * 1024) {
                throw new Exception('Arquivo muito grande (máximo 25MB)');
            }
            
            // Processar transcrição
            $transcricao = $this->processarTranscricao($audioFile['tmp_name'], $audioFile['name']);
            
            Logger::acao('Transcrição de áudio realizada', [
                'usuario_id' => Auth::id(),
                'tamanho_arquivo' => $audioFile['size'],
                'tipo' => $audioFile['type']
            ]);
            
            echo json_encode([
                'sucesso' => true,
                'transcricao' => $transcricao
            ]);
            
        } catch (Exception $e) {
            Logger::erro('Erro na transcrição: ' . $e->getMessage());
            echo json_encode([
                'sucesso' => false,
                'erro' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Processa transcrição usando OpenAI Whisper ou fallback
     */
    private function processarTranscricao(string $audioPath, string $fileName): string
    {
        // Verificar se OpenAI está configurada
        if (Configuracao::apiAtiva('openai')) {
            return $this->transcricaoOpenAI($audioPath, $fileName);
        }
        
        // Fallback para Web Speech API ou mock
        return $this->transcricaoFallback();
    }
    
    /**
     * Transcrição via OpenAI Whisper
     */
    private function transcricaoOpenAI(string $audioPath, string $fileName): string
    {
        $apiKey = Configuracao::get('openai_key');
        if (empty($apiKey)) {
            throw new Exception('API Key OpenAI não configurada');
        }
        
        // Converter para formato suportado se necessário
        $audioProcessado = $this->processarAudio($audioPath, $fileName);

        // O Whisper exige um nome de arquivo com extensão válida e mime coerente.
        $extProc = strtolower(pathinfo($audioProcessado, PATHINFO_EXTENSION));
        $extEnvio = $extProc ?: (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) ?: 'webm');
        $mimeMap = [
            'webm' => 'audio/webm', 'wav' => 'audio/wav', 'mp3' => 'audio/mpeg', 'mpeg' => 'audio/mpeg',
            'mpga' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg', 'mp4' => 'audio/mp4', 'm4a' => 'audio/mp4'
        ];
        $mimeEnvio = $mimeMap[$extEnvio] ?? 'audio/webm';
        $nomeEnvio = 'audio.' . $extEnvio;

        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($audioProcessado, $mimeEnvio, $nomeEnvio),
                'model' => 'whisper-1',
                'language' => 'pt',
                'response_format' => 'json'
            ],
            CURLOPT_TIMEOUT => 60
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        // Limpar arquivo temporário
        if (file_exists($audioProcessado)) {
            unlink($audioProcessado);
        }
        
        if ($httpCode !== 200) {
            $err = json_decode((string) $response, true);
            $msg = $err['error']['message'] ?? ('HTTP ' . $httpCode);
            Logger::error('Whisper API erro', ['http' => $httpCode, 'body' => substr((string) $response, 0, 500)]);
            throw new Exception('Erro na transcrição (OpenAI): ' . $msg);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['text'])) {
            throw new Exception('Resposta inválida da OpenAI');
        }
        
        return trim($data['text']);
    }
    
    /**
     * Processa arquivo de áudio para formato compatível
     */
    private function processarAudio(string $audioPath, string $fileName): string
    {
        // Se é webm, tentar converter para formato mais compatível
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['webm', 'ogg'])) {
            // Criar arquivo temporário com extensão wav
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.wav';
            
            // Se ffmpeg estiver disponível, converter
            if ($this->isFFmpegAvailable()) {
                $command = "ffmpeg -i " . escapeshellarg($audioPath) . " -ar 16000 -ac 1 " . escapeshellarg($tempFile) . " 2>/dev/null";
                exec($command, $output, $returnVar);
                
                if ($returnVar === 0 && file_exists($tempFile)) {
                    return $tempFile;
                }
            }
            
            // Se conversão falhou, usar arquivo original (remove o temp se existir)
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
        
        return $audioPath;
    }
    
    /**
     * Verifica se FFmpeg está disponível
     */
    private function isFFmpegAvailable(): bool
    {
        exec('ffmpeg -version 2>/dev/null', $output, $returnVar);
        return $returnVar === 0;
    }
    
    /**
     * Transcrição fallback quando OpenAI não está disponível
     */
    private function transcricaoFallback(): string
    {
        // Quando OpenAI não está configurada, retornar erro explicativo
        throw new Exception('Transcrição não disponível. Configure a API Key da OpenAI nas configurações do sistema.');
    }
}