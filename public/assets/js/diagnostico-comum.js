/**
 * Funções JavaScript comuns para o módulo de diagnóstico
 */

// Detectar APP_URL do contexto ou usar padrão
const APP_URL = window.APP_URL || '';

// Função para limpar rascunho (comum a todos os blocos)
async function limparRascunho() {
    if (!confirm('Tem certeza que deseja limpar todo o rascunho? Todos os dados preenchidos serão perdidos.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        const response = await fetch(APP_URL + '/diagnostico/limpar-rascunho', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.sucesso) {
            showToast(result.mensagem, 'success');
            setTimeout(() => {
                window.location.href = APP_URL + '/diagnostico/novo';
            }, 1500);
        } else {
            showToast(result.mensagem || 'Erro ao limpar rascunho', 'error');
        }
    } catch (error) {
        showToast('Erro na conexão. Tente novamente.', 'error');
    }
}

// Função comum para salvar bloco
async function salvarBlocoComum(bloco, rascunhoId, dados, redirectCallback) {
    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        formData.append('bloco', bloco);
        formData.append('rascunho_id', rascunhoId);
        
        // Adicionar dados do formulário
        Object.keys(dados).forEach(key => {
            if (Array.isArray(dados[key])) {
                dados[key].forEach(value => {
                    formData.append(key + '[]', value);
                });
            } else {
                formData.append(key, dados[key]);
            }
        });

        const response = await fetch(APP_URL + '/diagnostico/salvar-bloco', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (result.sucesso) {
            // Executar callback de redirecionamento personalizado
            if (redirectCallback) {
                redirectCallback(result);
            } else {
                // Redirecionamento padrão
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            }
        } else {
            showToast(result.mensagem || 'Erro ao salvar bloco', 'error');
        }
        
        return result;
    } catch (error) {
        console.error('Erro na requisição:', error);
        showToast('Erro na conexão: ' + error.message, 'error');
        throw error;
    }
}

// Mostrar toast de feedback (mais elegante que alert)
function showToast(message, type = 'success') {
    // Remover toasts existentes
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transition-all duration-300 transform translate-x-full`;
    
    switch (type) {
        case 'success':
            toast.className += ' bg-green-500';
            break;
        case 'error':
            toast.className += ' bg-red-500';
            break;
        case 'warning':
            toast.className += ' bg-yellow-500';
            break;
        default:
            toast.className += ' bg-blue-500';
    }
    
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remover após 3 segundos
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}