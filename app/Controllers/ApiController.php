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
    public function transcricao(): void
    {
        header('Content-Type: application/json');
        
        try {
            Auth::proteger();
            Csrf::verificar();
            
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Arquivo de áudio não recebido');
            }
            
            $audioFile = $_FILES['audio'];
            $allowedTypes = ['audio/webm', 'audio/wav', 'audio/mp3', 'audio/ogg'];
            
            if (!in_array($audioFile['type'], $allowedTypes)) {
                throw new Exception('Tipo de áudio não suportado');
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
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($audioProcessado, 'audio/webm', basename($audioProcessado)),
                'model' => 'whisper-1',
                'language' => 'pt',
                'response_format' => 'json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        // Limpar arquivo temporário
        if (file_exists($audioProcessado)) {
            unlink($audioProcessado);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Erro na API OpenAI: HTTP ' . $httpCode);
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
            
            // Se conversão falhou, usar arquivo original
            unlink($tempFile);
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