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
                        <!-- Indicador visual de status -->
                        <div class="flex items-center gap-2">
                            <div id="status-dot-openai" class="w-2.5 h-2.5 rounded-full bg-gray-400"></div>
                            <span id="badge-openai" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Verificando...
                            </span>
                        </div>
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
                        <input type="password" id="chave-openai" placeholder="sk-proj-xxx... ou sk-test (para desenvolvimento)" 
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

            <!-- ANTHROPIC CARD -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-purple-600 font-bold text-sm">C</span>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800">Anthropic (Claude)</h4>
                            <p class="text-xs text-gray-500">Análise avançada e geração de conteúdo</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Indicador visual de status -->
                        <div class="flex items-center gap-2">
                            <div id="status-dot-anthropic" class="w-2.5 h-2.5 rounded-full bg-gray-400"></div>
                            <span id="badge-anthropic" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Verificando...
                            </span>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span class="text-xs text-gray-500">Ativa</span>
                            <input type="checkbox" id="toggle-anthropic" onchange="toggleApiF14('anthropic', this.checked)"
                                   class="w-4 h-4 text-primary rounded border-gray-300">
                        </label>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Chave de API</label>
                        <input type="password" id="chave-anthropic" placeholder="sk-ant-xxx... ou sk-ant-test (para desenvolvimento)" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Modelo</label>
                        <select id="modelo-anthropic" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="claude-3-sonnet-20240229">Claude 3 Sonnet</option>
                            <option value="claude-3-haiku-20240307">Claude 3 Haiku</option>
                            <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Max Tokens</label>
                        <select id="max-tokens-anthropic" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="1024">1.024</option>
                            <option value="2048">2.048</option>
                            <option value="4096" selected>4.096</option>
                            <option value="8192">8.192</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="salvarChaveF14('anthropic')" 
                            class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                        💾 Salvar Chave
                    </button>
                    <button type="button" onclick="testarApiF14('anthropic')" 
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
                        <!-- Indicador visual de status -->
                        <div class="flex items-center gap-2">
                            <div id="status-dot-perplexity" class="w-2.5 h-2.5 rounded-full bg-gray-400"></div>
                            <span id="badge-perplexity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Verificando...
                            </span>
                        </div>
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
                        <input type="password" id="chave-perplexity" placeholder="pplx-xxx... ou pplx-test (para desenvolvimento)" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Modelo</label>
                        <select id="modelo-perplexity" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="sonar">sonar (rápido e econômico)</option>
                            <option value="sonar-pro">sonar-pro (busca aprofundada)</option>
                            <option value="sonar-reasoning-pro">sonar-reasoning-pro (raciocínio + busca)</option>
                            <option value="sonar-deep-research">sonar-deep-research (pesquisa exaustiva)</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Os modelos "llama-3.1-sonar-*" foram descontinuados pela Perplexity.</p>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Servidor SMTP</label>
                    <input type="text" id="smtp-host" placeholder="smtp.gmail.com" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Porta</label>
                    <select id="smtp-port" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="587">587 (TLS)</option>
                        <option value="465">465 (SSL)</option>
                        <option value="25">25 (Não seguro)</option>
                        <option value="2525">2525 (Alternativo)</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Email Remetente</label>
                    <input type="email" id="smtp-email" placeholder="noreply@suaempresa.com" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nome do Remetente</label>
                    <input type="text" id="smtp-nome" placeholder="O Consultor" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Usuário SMTP</label>
                    <input type="text" id="smtp-usuario" placeholder="usuario@gmail.com" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Senha/Token</label>
                    <input type="password" id="smtp-senha" placeholder="senha ou token de aplicativo" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-xs text-gray-500 mb-1">Segurança</label>
                <select id="smtp-seguranca" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="none">Nenhuma</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button type="button" onclick="salvarSmtp()" 
                        class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                    💾 Salvar Configuração
                </button>
                <button type="button" onclick="testarSmtp()" 
                        class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    📧 Testar Envio
                </button>
            </div>
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
        // Atualizar UI durante salvamento
        updateApiStatus(provedor, 'saving');
        
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
            
            // Atualizar indicador visual para "configurada"
            updateApiStatus(provedor, 'configured');
        } else {
            showToast(data.erro || 'Erro ao salvar chave', 'error');
            updateApiStatus(provedor, 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
        updateApiStatus(provedor, 'error');
    }
}
async function testarApiF14(provedor) {
    const button = event.target;
    const originalText = button.textContent;
    
    button.textContent = '🔄 Testando...';
    button.disabled = true;
    
    // Atualizar status visual para testando
    updateApiStatus(provedor, 'testing');
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('provedor', provedor);
        
        const response = await fetch('<?= APP_URL ?>/admin/api/testar', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            updateApiStatus(provedor, 'working');
            showToast(data.mensagem, 'success');
        } else {
            updateApiStatus(provedor, 'error');
            showToast(data.erro || 'Erro no teste da API', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        updateApiStatus(provedor, 'error');
        showToast('Erro de conexão', 'error');
    } finally {
        button.textContent = originalText;
        button.disabled = false;
    }
}

// Função para atualizar indicadores visuais de status da API
function updateApiStatus(provedor, status) {
    const dot = document.getElementById('status-dot-' + provedor);
    const badge = document.getElementById('badge-' + provedor);
    
    if (!dot || !badge) return;
    
    switch (status) {
        case 'not_configured':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
            badge.textContent = 'Não configurada';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600';
            break;
            
        case 'configured':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-yellow-500';
            badge.textContent = 'Configurada';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700';
            break;
            
        case 'working':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-green-500';
            badge.textContent = 'Funcionando';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
            break;
            
        case 'error':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
            badge.textContent = 'Erro';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
            break;
            
        case 'inactive':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-300';
            badge.textContent = 'Inativa';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500';
            break;
            
        case 'testing':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse';
            badge.textContent = 'Testando...';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-600';
            break;
            
        case 'saving':
            dot.className = 'w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse';
            badge.textContent = 'Salvando...';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-600';
            break;
            
        default:
            dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
            badge.textContent = 'Verificando...';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600';
    }
}

// Função para carregar status atual das APIs
async function loadApiStatuses() {
    const apis = ['openai', 'anthropic', 'perplexity'];
    
    for (const provedor of apis) {
        try {
            const formData = new FormData();
            formData.append('csrf_token', '<?= Csrf::token() ?>');
            formData.append('provedor', provedor);
            formData.append('action', 'check_status');
            
            const response = await fetch('<?= APP_URL ?>/admin/api/status', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.sucesso) {
                // Atualizar checkbox ativo/inativo
                const toggle = document.getElementById('toggle-' + provedor);
                if (toggle) {
                    toggle.checked = data.ativo === true;
                }
                
                // Atualizar status visual baseado na configuração
                if (!data.ativo) {
                    updateApiStatus(provedor, 'inactive');
                } else if (data.configurada) {
                    // Se está ativa e configurada, assumir como funcionando (pode ser testada depois)
                    updateApiStatus(provedor, 'configured');
                    
                    // Mostrar chave mascarada se existir
                    const chaveInput = document.getElementById('chave-' + provedor);
                    if (chaveInput && data.chave_mascarada) {
                        chaveInput.value = data.chave_mascarada;
                    }
                } else {
                    updateApiStatus(provedor, 'not_configured');
                }
            } else {
                updateApiStatus(provedor, 'error');
            }
            
        } catch (error) {
            console.error(`Erro ao verificar status da API ${provedor}:`, error);
            updateApiStatus(provedor, 'error');
        }
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

// Função para carregar dados das APIs ao inicializar
async function loadApiData() {
    // Carregar status das APIs do banco de dados
    await loadApiStatuses();
    
    // Configurar event listeners
    const apis = ['openai', 'perplexity', 'anthropic'];
    
    apis.forEach(provedor => {
        const toggle = document.getElementById('toggle-' + provedor);
        
        if (toggle) {
            toggle.addEventListener('change', function() {
                toggleApiF14(provedor, this.checked);
            });
        }
    });
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', loadApiData);

// Funções SMTP
async function salvarSmtp() {
    const config = {
        smtp_host: document.getElementById('smtp-host').value.trim(),
        smtp_port: document.getElementById('smtp-port').value,
        smtp_email: document.getElementById('smtp-email').value.trim(),
        smtp_nome: document.getElementById('smtp-nome').value.trim(),
        smtp_usuario: document.getElementById('smtp-usuario').value.trim(),
        smtp_senha: document.getElementById('smtp-senha').value.trim(),
        smtp_seguranca: document.getElementById('smtp-seguranca').value
    };
    
    // Validações básicas
    if (!config.smtp_host || !config.smtp_email || !config.smtp_usuario || !config.smtp_senha) {
        showToast('Preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        Object.keys(config).forEach(key => {
            formData.append(key, config[key]);
        });
        
        const response = await fetch('<?= APP_URL ?>/admin/smtp/salvar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            showToast('Configuração SMTP salva com sucesso!', 'success');
            // Limpar senha por segurança
            document.getElementById('smtp-senha').value = '••••••••••';
        } else {
            showToast(result.erro || 'Erro ao salvar configuração', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}

async function testarSmtp() {
    const btn = event.target;
    const originalText = btn.textContent;
    
    btn.disabled = true;
    btn.textContent = '📤 Enviando...';
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        
        const response = await fetch('<?= APP_URL ?>/admin/smtp/testar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            showToast('E-mail de teste enviado com sucesso!', 'success');
        } else {
            showToast(result.erro || 'Erro ao enviar e-mail de teste', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = originalText;
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>