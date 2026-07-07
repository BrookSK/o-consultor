/**
 * Microfone de Transcrição — O Consultor
 * Adiciona funcionalidade de gravação de voz e transcrição via GPT para campos textarea
 */

class MicrofoneTranscricao {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.currentTextarea = null;
        
        this.initMicrophones();
    }

    /**
     * Inicializa microfones em todos os textareas da página
     */
    initMicrophones() {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => this.addMicrophoneToTextarea(textarea));
    }

    /**
     * Adiciona botão de microfone a um textarea específico
     */
    addMicrophoneToTextarea(textarea) {
        // Verificar se já tem microfone
        if (textarea.parentElement.querySelector('.microfone-btn')) {
            return;
        }

        // Criar container se não existir
        let container = textarea.parentElement;
        if (!container.classList.contains('textarea-container')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'textarea-container';
            wrapper.style.cssText = 'position: relative; display: inline-block; width: 100%;';
            
            textarea.parentNode.insertBefore(wrapper, textarea);
            wrapper.appendChild(textarea);
            container = wrapper;
        }

        // Criar botão do microfone
        const micBtn = document.createElement('button');
        micBtn.type = 'button';
        micBtn.className = 'microfone-btn';
        micBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 15c1.66 0 2.99-1.34 2.99-3L15 6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 15 6.7 12H5c0 3.42 2.72 6.23 6 6.72V22h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/>
            </svg>
        `;
        
        // Estilos do botão
        micBtn.style.cssText = `
            position: absolute;
            right: 8px;
            bottom: 8px;
            background: #1E3A5F;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            z-index: 10;
        `;

        // Estados hover e ativo
        micBtn.addEventListener('mouseenter', () => {
            if (!this.isRecording) {
                micBtn.style.background = '#2d5a87';
                micBtn.style.transform = 'scale(1.05)';
            }
        });

        micBtn.addEventListener('mouseleave', () => {
            if (!this.isRecording) {
                micBtn.style.background = '#1E3A5F';
                micBtn.style.transform = 'scale(1)';
            }
        });

        // Evento de clique
        micBtn.addEventListener('click', () => {
            if (this.isRecording) {
                this.stopRecording();
            } else {
                this.startRecording(textarea, micBtn);
            }
        });

        // Adicionar tooltip
        micBtn.title = 'Clique para gravar áudio e transcrever';

        container.appendChild(micBtn);

        // Ajustar padding do textarea para não sobrepor o botão
        const currentPadding = window.getComputedStyle(textarea).paddingRight;
        const paddingValue = parseInt(currentPadding) || 12;
        textarea.style.paddingRight = Math.max(paddingValue, 45) + 'px';
    }

    /**
     * Inicia gravação de áudio
     */
    async startRecording(textarea, button) {
        try {
            // Solicitar permissão do microfone
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    sampleRate: 16000,
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true
                } 
            });

            this.currentTextarea = textarea;
            this.audioChunks = [];
            
            // Configurar MediaRecorder
            this.mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.processAudio();
                stream.getTracks().forEach(track => track.stop());
            };

            // Iniciar gravação
            this.mediaRecorder.start();
            this.isRecording = true;

            // Atualizar UI do botão
            this.updateButtonRecordingState(button, true);

            // Auto-stop após 30 segundos
            setTimeout(() => {
                if (this.isRecording) {
                    this.stopRecording();
                }
            }, 30000);

        } catch (error) {
            console.error('Erro ao acessar microfone:', error);
            this.showNotification('Erro ao acessar microfone. Verifique as permissões.', 'erro');
        }
    }

    /**
     * Para gravação de áudio
     */
    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            
            // Atualizar todos os botões
            document.querySelectorAll('.microfone-btn').forEach(btn => {
                this.updateButtonRecordingState(btn, false);
            });
        }
    }

    /**
     * Atualiza estado visual do botão durante gravação
     */
    updateButtonRecordingState(button, isRecording) {
        if (isRecording) {
            button.style.background = '#dc2626';
            button.style.animation = 'pulse 1s infinite';
            button.title = 'Gravando... Clique para parar';
            
            // Adicionar CSS de pulse se não existir
            if (!document.getElementById('pulse-animation')) {
                const style = document.createElement('style');
                style.id = 'pulse-animation';
                style.textContent = `
                    @keyframes pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.1); }
                        100% { transform: scale(1); }
                    }
                `;
                document.head.appendChild(style);
            }
        } else {
            button.style.background = '#1E3A5F';
            button.style.animation = '';
            button.style.transform = '';
            button.title = 'Clique para gravar áudio e transcrever';
        }
    }

    /**
     * Processa áudio gravado e envia para transcrição
     */
    async processAudio() {
        if (this.audioChunks.length === 0) {
            this.showNotification('Nenhum áudio gravado', 'aviso');
            return;
        }

        try {
            // Criar blob do áudio
            const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
            
            // Mostrar indicador de processamento
            this.showProcessingIndicator();
            
            // Enviar para transcrição
            const transcricao = await this.enviarParaTranscricao(audioBlob);
            
            if (transcricao && transcricao.trim()) {
                // Adicionar transcrição ao textarea
                this.adicionarTextoNoTextarea(transcricao);
                this.showNotification('Transcrição concluída!', 'sucesso');
            } else {
                this.showNotification('Não foi possível transcrever o áudio', 'erro');
            }
            
        } catch (error) {
            console.error('Erro no processamento:', error);
            this.showNotification('Erro ao processar áudio', 'erro');
        } finally {
            this.hideProcessingIndicator();
        }
    }

    /**
     * Envia áudio para API de transcrição
     */
    async enviarParaTranscricao(audioBlob) {
        // Buscar um token CSRF fresco (evita 403 quando o token da página rotacionou)
        let token = '';
        try {
            const tr = await fetch('/api/csrf-token', { headers: { 'Accept': 'application/json' } });
            if (tr.ok) { const td = await tr.json(); token = td.token || ''; }
        } catch (e) { /* segue com fallback abaixo */ }
        if (!token) {
            token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="csrf_token"]')?.value || '';
        }

        const formData = new FormData();
        formData.append('audio', audioBlob, 'gravacao.webm');
        formData.append('csrf_token', token);

        const response = await fetch('/api/transcricao', {
            method: 'POST',
            headers: { 'X-CSRF-Token': token },
            body: formData
        });

        const result = await response.json().catch(() => ({ sucesso: false, erro: 'Resposta inválida do servidor' }));
        if (!response.ok || !result.sucesso) {
            throw new Error(result.erro || ('Erro na API de transcrição (HTTP ' + response.status + ')'));
        }
        return result.transcricao;
    }

    /**
     * Adiciona texto transcrito ao textarea
     */
    adicionarTextoNoTextarea(transcricao) {
        if (!this.currentTextarea) return;

        const textarea = this.currentTextarea;
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);

        // Adicionar espaço antes se necessário
        const spaceBefore = textBefore && !textBefore.endsWith(' ') ? ' ' : '';
        const spaceAfter = textAfter && !textAfter.startsWith(' ') ? ' ' : '';

        const newText = textBefore + spaceBefore + transcricao + spaceAfter + textAfter;
        textarea.value = newText;

        // Posicionar cursor após o texto inserido
        const newCursorPos = cursorPos + spaceBefore.length + transcricao.length + spaceAfter.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();

        // Disparar evento de input para frameworks que escutam
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /**
     * Mostra indicador de processamento
     */
    showProcessingIndicator() {
        // Criar overlay se não existir
        let overlay = document.getElementById('transcricao-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'transcricao-overlay';
            overlay.innerHTML = `
                <div class="processing-content">
                    <div class="spinner"></div>
                    <p>Transcrevendo áudio...</p>
                </div>
            `;
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                font-family: Arial, sans-serif;
            `;

            // Adicionar CSS do spinner
            const style = document.createElement('style');
            style.textContent = `
                .processing-content {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                }
                .spinner {
                    width: 40px;
                    height: 40px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #1E3A5F;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 15px;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    /**
     * Esconde indicador de processamento
     */
    hideProcessingIndicator() {
        const overlay = document.getElementById('transcricao-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Mostra notificação para o usuário
     */
    showNotification(message, type = 'info') {
        // Criar container de notificações se não existir
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10001;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        // Criar notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        const colors = {
            sucesso: '#10b981',
            erro: '#ef4444',
            aviso: '#f59e0b',
            info: '#3b82f6'
        };

        notification.style.cssText = `
            background: ${colors[type] || colors.info};
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            pointer-events: auto;
            font-size: 14px;
            max-width: 300px;
        `;

        container.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Remover após 4 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    /**
     * Reinicializa microfones (útil para conteúdo dinâmico)
     */
    reinit() {
        this.initMicrophones();
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.microfoneTranscricao = new MicrofoneTranscricao();
});

// Reinicializar em mudanças dinâmicas de conteúdo
document.addEventListener('DOMNodeInserted', () => {
    if (window.microfoneTranscricao) {
        setTimeout(() => window.microfoneTranscricao.reinit(), 100);
    }
});