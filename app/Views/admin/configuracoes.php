<?php $tituloPagina = 'Configurações'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Configurações</li>
    </ol>
</nav>

<h1 class="text-2xl font-bold text-gray-800 mb-6">Configurações do Sistema</h1>

<div x-data="{ aba: 'apis' }">
    <!-- Navegação das abas -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba='geral'" :class="aba==='geral'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Geral</button>
            <button @click="aba='apis'" :class="aba==='apis'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">APIs de IA</button>
            <button @click="aba='academy'" :class="aba==='academy'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Academy</button>
            <button @click="aba='email'" :class="aba==='email'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Email/SMTP</button>
        </nav>
    </div>

    <!-- ABA GERAL -->
    <div x-show="aba==='geral'" class="max-w-2xl">
        <form id="form-geral">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="font-semibold text-gray-800 mb-4">Configurações Gerais</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da plataforma</label>
                    <input type="text" name="config[app_nome]" value="O Consultor" 
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email de contato</label>
                    <input type="email" name="config[app_email_contato]" value="contato@oconsultor.com.br" 
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                
                <button type="button" onclick="salvarConfig('geral')" 
                        class="w-full bg-accent text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-orange-700">
                    💾 Salvar Configurações Gerais
                </button>
            </div>
        </form>
    </div>
    <!-- ABA APIs DE IA -->
    <div x-show="aba==='apis'" class="max-w-4xl">
        <div class="space-y-6">
            
            <!-- OPENAI CARD -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-green-600 font-bold text-sm">AI</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">OpenAI (GPT + DALL-E)</h4>
                            <p class="text-xs text-gray-500">Geração de texto, análise e imagens</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="badge-openai" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            Não configurada
                        </span>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-xs text-gray-500">Ativa</span>
                            <input type="checkbox" id="toggle-openai" onchange="toggleApiF14('openai', this.checked)"
                                   class="w-4 h-4 text-primary rounded border-gray-300">
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Chave de API</label>
                        <input type="password" id="chave-openai" placeholder="sk-..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Modelo</label>
                        <select id="modelo-openai" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option>gpt-4</option>
                            <option>gpt-4-turbo</option>
                            <option>gpt-3.5-turbo</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="salvarChaveF14('openai')" 
                            class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                        💾 Salvar Chave
                    </button>
                    <button type="button" onclick="testarApiF14('openai')" 
                            class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                        🧪 Testar API
                    </button>
                </div>
            </div>
            <!-- PERPLEXITY CARD -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-blue-600 font-bold text-sm">P</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Perplexity</h4>
                            <p class="text-xs text-gray-500">Busca de notícias em tempo real</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="badge-perplexity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            Não configurada
                        </span>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-xs text-gray-500">Ativa</span>
                            <input type="checkbox" id="toggle-perplexity" onchange="toggleApiF14('perplexity', this.checked)"
                                   class="w-4 h-4 text-primary rounded border-gray-300">
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Chave de API</label>
                        <input type="password" id="chave-perplexity" placeholder="pplx-..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Modelo</label>
                        <select id="modelo-perplexity" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option>llama-3.1-sonar-small-128k-online</option>
                            <option>llama-3.1-sonar-large-128k-online</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="salvarChaveF14('perplexity')" 
                            class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                        💾 Salvar Chave
                    </button>
                    <button type="button" onclick="testarApiF14('perplexity')" 
                            class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                        🧪 Testar API
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Outras abas simplificadas -->
    <div x-show="aba==='academy'" style="display:none" class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Integração My Academy</h3>
            <p class="text-sm text-gray-500">Configuração em desenvolvimento...</p>
        </div>
    </div>

    <div x-show="aba==='email'" style="display:none" class="max-w-2xl">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Configuração SMTP</h3>
            <p class="text-sm text-gray-500">Configuração em desenvolvimento...</p>
        </div>
    </div>
</div>

<script>
// Função para salvar configurações gerais
async function salvarConfig(grupo) {
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('grupo', grupo);
        
        // Coletar campos do grupo
        const form = document.getElementById('form-' + grupo);
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    formData.append(input.name, input.checked ? '1' : '0');
                } else if (input.value) {
                    formData.append(input.name, input.value);
                }
            });
        }
        
        const response = await fetch('<?= APP_URL ?>/admin/configuracoes/salvar', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast('Configurações salvas com sucesso!', 'success');
        } else {
            showToast(data.erro || 'Erro ao salvar configurações', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}
// Funções para APIs F-14
async function toggleApiF14(provedor, status) {
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('provedor', provedor);
        formData.append('status', status ? '1' : '0');
        
        const response = await fetch('<?= APP_URL ?>/admin/api/toggle', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            const badge = document.getElementById('badge-' + provedor);
            if (badge) {
                if (status) {
                    badge.textContent = 'Ativa';
                    badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
                } else {
                    badge.textContent = 'Inativa';
                    badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
                }
            }
            showToast(data.mensagem, 'success');
        } else {
            document.getElementById('toggle-' + provedor).checked = !status;
            showToast(data.erro || 'Erro ao alterar status da API', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('toggle-' + provedor).checked = !status;
        showToast('Erro de conexão', 'error');
    }
}

async function salvarChaveF14(provedor) {
    const chaveInput = document.getElementById('chave-' + provedor);
    const chave = chaveInput.value.trim();
    
    if (!chave) {
        showToast('Digite a chave de API', 'error');
        chaveInput.focus();
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('provedor', provedor);
        formData.append('chave', chave);
        
        const response = await fetch('<?= APP_URL ?>/admin/api/salvar-chave', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            chaveInput.value = '••••••••••••••••';
            showToast(data.mensagem, 'success');
            
            const badge = document.getElementById('badge-' + provedor);
            if (badge) {
                badge.textContent = 'Configurada';
                badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600';
            }
        } else {
            showToast(data.erro || 'Erro ao salvar chave', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}
async function testarApiF14(provedor) {
    const button = event.target;
    const originalText = button.textContent;
    
    button.textContent = '🔄 Testando...';
    button.disabled = true;
    
    const badge = document.getElementById('badge-' + provedor);
    if (badge) {
        badge.textContent = 'Testando...';
        badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-600';
    }
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('provedor', provedor);
        
        const response = await fetch('<?= APP_URL ?>/admin/api/testar', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (badge) {
            if (data.sucesso) {
                badge.textContent = 'Funcionando';
                badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
            } else {
                badge.textContent = 'Erro';
                badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
            }
        }
        
        showToast(data.mensagem, data.sucesso ? 'success' : 'error');
        
    } catch (error) {
        console.error('Erro:', error);
        if (badge) {
            badge.textContent = 'Erro de rede';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
        }
        showToast('Erro de conexão', 'error');
    } finally {
        button.textContent = originalText;
        button.disabled = false;
    }
}

// Função para mostrar toast/notificação
function showToast(message, type) {
    // Criar elemento de toast
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm font-medium z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remover após 3 segundos
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>