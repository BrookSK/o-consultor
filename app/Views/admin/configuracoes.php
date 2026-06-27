<?php $tituloPagina = 'Configurações'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li><li>/</li><li class="font-medium text-primary">Configurações</li></ol></nav>
<h1 class="text-2xl font-bold text-gray-800 mb-6">Configurações do Sistema</h1>

<div x-data="{ aba: 'geral' }">
<div class="border-b border-gray-200 mb-6"><nav class="flex gap-0 overflow-x-auto">
    <button @click="aba='geral'" :class="aba==='geral'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Geral</button>
    <button @click="aba='modulos'" :class="aba==='modulos'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Módulos</button>
    <button @click="aba='apis'" :class="aba==='apis'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">APIs de Conteúdo</button>
    <button @click="aba='academy'" :class="aba==='academy'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Academy</button>
    <button @click="aba='notificacoes'" :class="aba==='notificacoes'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Notificações</button>
    <button @click="aba='email'" :class="aba==='email'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm">Email/SMTP</button>
</nav></div>

<!-- ABA GERAL -->
<div x-show="aba==='geral'" class="max-w-2xl space-y-4">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome da plataforma</label><input type="text" name="config[app_nome]" value="O Consultor" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Email de contato</label><input type="email" name="config[app_email_contato]" value="contato@oconsultor.com.br" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Idioma padrão</label><select name="config[app_idioma]" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option>pt-BR</option><option>en</option><option>es</option></select></div>
        <div><label class="block text-sm font-medium text-gray-700 mb-1">Cor primária</label><div class="flex items-center gap-3"><input type="color" name="config[app_cor_primaria]" value="#1E3A5F" class="w-10 h-10 rounded border cursor-pointer"><span class="text-sm text-gray-500">#1E3A5F</span></div></div>
        <button onclick="salvarConfig('geral')" class="w-full bg-accent text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-orange-700">💾 Salvar Configurações Gerais</button>
    </div>
</div>

<!-- ABA MÓDULOS -->
<div x-show="aba==='modulos'" style="display:none" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-3">
        <p class="text-sm text-gray-500 mb-4">Habilitar/desabilitar módulos por cliente.</p>
        <?php foreach ($dados['modulos'] as $chave => $config): ?>
        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
            <div>
                <span class="text-sm text-gray-700"><?= str_replace(['modulo_', '_'], ['', ' '], ucwords($chave, '_')) ?></span>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($config['descricao']) ?></p>
            </div>
            <input type="checkbox" name="config[<?= $chave ?>]" value="1" 
                   <?= $config['valor'] === '1' ? 'checked' : '' ?>
                   class="w-4 h-4 text-primary rounded border-gray-300">
        </label>
        <?php endforeach; ?>
        <button onclick="salvarConfig('modulos')" class="w-full mt-4 bg-primary text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-700">💾 Salvar Módulos</button>
    </div>
</div>

<!-- ABA APIs DE CONTEÚDO (F-14) -->
<div x-show="aba==='apis'" style="display:none" class="max-w-4xl">
    <div class="space-y-4">
        
        <!-- PERPLEXITY CARD -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" id="card-perplexity">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-sm">P</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800">Perplexity</h4>
                        <p class="text-xs text-gray-500">Busca de conteúdo e notícias em tempo real</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span id="badge-perplexity" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Desconhecido
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-xs text-gray-500">Ativa</span>
                        <input type="checkbox" id="toggle-perplexity" onchange="toggleApiF14('perplexity', this.checked)"
                               class="w-4 h-4 text-primary rounded border-gray-300">
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                        <option>llama-3.1-sonar-huge-128k-online</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <button onclick="salvarChaveF14('perplexity')" 
                        class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                    💾 Salvar Chave
                </button>
                <button onclick="testarApiF14('perplexity')" 
                        class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    🧪 Testar
                </button>
            </div>
        </div>

        <!-- OPENAI CARD -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" id="card-openai">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <span class="text-green-600 font-bold text-sm">AI</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800">OpenAI (GPT)</h4>
                        <p class="text-xs text-gray-500">Geração de texto, análise e DALL-E para imagens</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span id="badge-openai" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Desconhecido
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-xs text-gray-500">Ativa</span>
                        <input type="checkbox" id="toggle-openai" onchange="toggleApiF14('openai', this.checked)"
                               class="w-4 h-4 text-primary rounded border-gray-300">
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
            <div class="flex gap-2 mt-3">
                <button onclick="salvarChaveF14('openai')" 
                        class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                    💾 Salvar Chave
                </button>
                <button onclick="testarApiF14('openai')" 
                        class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    🧪 Testar
                </button>
            </div>
        </div>

        <!-- ANTHROPIC CARD -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" id="card-anthropic">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="text-purple-600 font-bold text-sm">C</span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-800">Anthropic (Claude)</h4>
                        <p class="text-xs text-gray-500">Alternativa/fallback ao GPT para análise de conteúdo</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span id="badge-anthropic" class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        Desconhecido
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <span class="text-xs text-gray-500">Ativa</span>
                        <input type="checkbox" id="toggle-anthropic" onchange="toggleApiF14('anthropic', this.checked)"
                               class="w-4 h-4 text-primary rounded border-gray-300">
                    </label>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Chave de API</label>
                    <input type="password" id="chave-anthropic" placeholder="sk-ant-..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Modelo</label>
                    <select id="modelo-anthropic" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option>claude-3-sonnet-20240229</option>
                        <option>claude-3-opus-20240229</option>
                        <option>claude-3-haiku-20240307</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <button onclick="salvarChaveF14('anthropic')" 
                        class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
                    💾 Salvar Chave
                </button>
                <button onclick="testarApiF14('anthropic')" 
                        class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                    🧪 Testar
                </button>
            </div>
        </div>

        <!-- REGRA DE USO -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-700 mt-4">
            <div class="space-y-2">
                <div><strong>Funcionalidades por API:</strong></div>
                <ul class="space-y-1 list-disc list-inside ml-2">
                    <li><strong>Perplexity:</strong> Busca de notícias e conteúdos da web em tempo real para Central de Conteúdo</li>
                    <li><strong>OpenAI (GPT):</strong> Geração de SOPs, análise de diagnóstico, transcrição de áudio (Whisper)</li>
                    <li><strong>OpenAI (DALL-E):</strong> Geração de imagens para conteúdo na Máquina de Conteúdo</li>
                    <li><strong>Anthropic (Claude):</strong> Alternativa/fallback ao GPT para análise de conteúdo e SOPs</li>
                </ul>
                <div class="mt-2"><strong>Regra de Fallback:</strong> Perplexity=busca, GPT/Claude=análise. Se GPT e Claude ativos: GPT como padrão, Claude como fallback. Se nenhuma API ativa: modo manual.</div>
            </div>
        </div>

        <!-- BANNER DE AVISO -->
        <div id="banner-nenhuma-api" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-700" style="display: none;">
            <div class="flex items-center gap-3">
                <span class="text-yellow-500">⚠️</span>
                <div>
                    <strong>Nenhuma API configurada</strong>
                    <p class="mt-1">O feed de notícias e geração de conteúdo estão desabilitados. Configure pelo menos uma API para ativar essas funcionalidades.</p>
                </div>
            </div>
        </div>
    </div>

        <!-- Regra de uso -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-700">
            <div class="space-y-2">
                <div><strong>Funcionalidades por API:</strong></div>
                <ul class="space-y-1 list-disc list-inside ml-2">
                    <li><strong>OpenAI:</strong> Geração de SOPs, análise de diagnóstico, transcrição de áudio (Whisper) e geração de imagens (DALL-E)</li>
                    <li><strong>Perplexity:</strong> Busca de notícias e conteúdos da web em tempo real para Central de Conteúdo</li>
                    <li><strong>Anthropic:</strong> Alternativa/fallback ao OpenAI para análise de conteúdo</li>
                </ul>
                <div class="mt-2"><strong>Regra:</strong> Se OpenAI ativo = usado prioritariamente. Se ambos ativos = OpenAI padrão + Anthropic fallback. Se nenhuma ativa = modo manual.</div>
            </div>
        </div>

        <!-- Configurações avançadas -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-800 mb-4">Configurações Avançadas</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php foreach ($dados['api_config'] as $chave => $config): ?>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><?= ucfirst(str_replace('_', ' ', str_replace('api_', '', $chave))) ?></label>
                    <input type="number" name="config[<?= $chave ?>]" value="<?= htmlspecialchars($config['valor']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ABA ACADEMY -->
<div x-show="aba==='academy'" style="display:none" class="max-w-3xl space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-gray-800">Integração My Academy (SSO)</h4>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $dados['academy_ativo'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                <?= $dados['academy_ativo'] ? 'Ativa' : 'Inativa' ?>
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($dados['academy'] as $chave => $config): ?>
                <?php if (str_contains($chave, '_ativo')) continue; ?>
                <div>
                    <label class="block text-xs text-gray-500 mb-1"><?= ucfirst(str_replace(['_', 'academy_'], [' ', ''], $chave)) ?></label>
                    <input type="<?= $config['sensivel'] ? 'password' : 'text' ?>" 
                           name="config[<?= $chave ?>]"
                           value="<?= $config['sensivel'] && !empty($config['valor']) ? '••••••••••••••••' : htmlspecialchars($config['valor']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
            <?php endforeach; ?>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Status da integração</label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="config[academy_ativo]" value="1" 
                           <?= $dados['academy_ativo'] ? 'checked' : '' ?>
                           class="w-4 h-4 text-primary rounded border-gray-300">
                    <span class="text-sm text-gray-700">Ativar Academy SSO</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3">
            <button onclick="salvarConfig('academy')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">💾 Salvar Academy</button>
            <button onclick="testarAcademy()" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">🧪 Testar Integração</button>
        </div>
    </div>

    <!-- Usuários Academy -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100"><h4 class="font-semibold text-gray-800">Vinculação Academy por Usuário</h4></div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-2 font-medium text-gray-500">Nome</th><th class="px-4 py-2 font-medium text-gray-500">Email</th><th class="px-4 py-2 font-medium text-gray-500">Vinculada</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['usuarios_academy'] as $ua): ?>
            <tr><td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($ua['nome']) ?></td><td class="px-4 py-2 text-gray-500 text-xs"><?= htmlspecialchars($ua['email']) ?></td>
                <td class="px-4 py-2 text-center"><?= $ua['vinculada'] ? '<span class="text-green-600 font-medium text-xs">✓ Vinculada</span>' : '<span class="text-red-500 text-xs">✗ Não</span>' ?></td>
                <td class="px-4 py-2 text-right"><?= !$ua['vinculada'] ? '<button onclick="alert(\'Convite enviado para ' . htmlspecialchars($ua['email']) . '!\')" class="text-xs px-2 py-1 bg-accent text-white rounded hover:bg-orange-700">Enviar convite</button>' : '' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ABA NOTIFICAÇÕES -->
<div x-show="aba==='notificacoes'" style="display:none" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-3">
        <p class="text-sm text-gray-500 mb-4">Configurar eventos que disparam notificações.</p>
        <?php foreach ($dados['notificacoes'] as $chave => $config): ?>
        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
            <div>
                <span class="text-sm text-gray-700"><?= str_replace(['notif_', '_'], ['', ' '], ucwords($chave, '_')) ?></span>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($config['descricao']) ?></p>
            </div>
            <input type="checkbox" name="config[<?= $chave ?>]" value="1" 
                   <?= $config['valor'] === '1' ? 'checked' : '' ?>
                   class="w-4 h-4 text-primary rounded border-gray-300">
        </label>
        <?php endforeach; ?>
        <button onclick="salvarConfig('notificacoes')" class="w-full mt-4 bg-primary text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-700">💾 Salvar Notificações</button>
    </div>
</div>

<!-- ABA EMAIL/SMTP -->
<div x-show="aba==='email'" style="display:none" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-gray-800">Configuração SMTP</h4>
            <label class="flex items-center gap-2 cursor-pointer">
                <span class="text-xs text-gray-500">Ativo</span>
                <input type="checkbox" name="config[smtp_ativo]" value="1" 
                       <?= $dados['smtp_ativo'] ? 'checked' : '' ?>
                       class="w-4 h-4 text-primary rounded border-gray-300">
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php 
            $smtpFields = [
                'smtp_host' => ['label' => 'Servidor SMTP', 'type' => 'text', 'placeholder' => 'smtp.gmail.com', 'required' => true],
                'smtp_porta' => ['label' => 'Porta', 'type' => 'number', 'placeholder' => '587', 'required' => true],
                'smtp_usuario' => ['label' => 'Usuário (email)', 'type' => 'text', 'placeholder' => 'noreply@seudominio.com', 'required' => true],
                'smtp_senha' => ['label' => 'Senha', 'type' => 'password', 'placeholder' => '••••••••', 'required' => true],
                'smtp_criptografia' => ['label' => 'Criptografia', 'type' => 'select', 'options' => ['tls' => 'TLS (porta 587) — Recomendado', 'ssl' => 'SSL (porta 465)', 'nenhuma' => 'Nenhuma (porta 25)']],
                'smtp_remetente_nome' => ['label' => 'Nome do remetente', 'type' => 'text', 'placeholder' => 'O Consultor'],
                'smtp_remetente_email' => ['label' => 'Email do remetente (From)', 'type' => 'email', 'placeholder' => 'noreply@oconsultor.digital', 'class' => 'md:col-span-2']
            ];
            
            foreach ($smtpFields as $chave => $field):
                $config = $dados['smtp'][$chave] ?? ['valor' => '', 'sensivel' => false];
                $valor = $config['sensivel'] && !empty($config['valor']) ? '••••••••••••••••' : $config['valor'];
            ?>
            <div class="<?= $field['class'] ?? '' ?>">
                <label class="block text-xs text-gray-500 mb-1">
                    <?= $field['label'] ?><?= $field['required'] ?? false ? ' *' : '' ?>
                </label>
                <?php if ($field['type'] === 'select'): ?>
                    <select name="config[<?= $chave ?>]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <?php foreach ($field['options'] as $optValue => $optLabel): ?>
                        <option value="<?= $optValue ?>" <?= $config['valor'] === $optValue ? 'selected' : '' ?>>
                            <?= $optLabel ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= $field['type'] ?>" 
                           name="config[<?= $chave ?>]" 
                           value="<?= htmlspecialchars($valor) ?>"
                           placeholder="<?= $field['placeholder'] ?? '' ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="flex gap-3 pt-2">
            <button onclick="salvarConfig('smtp')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">💾 Salvar SMTP</button>
            <button onclick="testarSmtp()" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">🧪 Testar Conexão</button>
            <button onclick="enviarEmailTeste()" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">📧 Enviar Email de Teste</button>
        </div>
    </div>

    <!-- Guia rápido -->
    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-700">
        <strong>Guia rápido:</strong>
        <ul class="mt-2 space-y-1 list-disc list-inside">
            <li><strong>Gmail:</strong> smtp.gmail.com / porta 587 / TLS (requer "Senha de App")</li>
            <li><strong>Hostinger:</strong> smtp.hostinger.com / porta 587 / TLS</li>
            <li><strong>Outlook:</strong> smtp-mail.outlook.com / porta 587 / TLS</li>
            <li><strong>SendGrid:</strong> smtp.sendgrid.net / porta 587 / TLS (user: apikey)</li>
        </ul>
    </div>
</div>

</div>

<script>
async function testarApis() {
    const fd = new FormData(); fd.append('csrf_token', '<?= Csrf::token() ?>');
    const res = await fetch('<?= APP_URL ?>/admin/testar-apis', { method:'POST', body:fd });
    const data = await res.json();
    if (data.sucesso) { let msg = data.resultados.map(r => r.api + ': ' + r.status + ' (' + r.tempo + ')').join('\n'); alert('Resultado:\n' + msg); }
}
async function testarAcademy() {
    const fd = new FormData(); fd.append('csrf_token', '<?= Csrf::token() ?>');
    const res = await fetch('<?= APP_URL ?>/admin/testar-academy', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem || 'Teste concluído.');
}
async function salvarConfig(grupo) {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('grupo', grupo);
    
    // Coletar apenas campos do grupo específico ou todos se for 'geral'
    let selector = '[name^="config["]';
    if (grupo !== 'geral') {
        selector = `[name^="config[${grupo}"]`;
    }
    
    document.querySelectorAll(selector).forEach(el => {
        if (el.type === 'checkbox') {
            fd.append(el.name, el.checked ? '1' : '0');
        } else if (el.value && el.value !== '••••••••••••••••') {
            // Não enviar campos de senha mascarados
            fd.append(el.name, el.value);
        }
    });
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/configuracoes/salvar', { 
            method: 'POST', 
            body: fd 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            if (typeof showNotifToast !== 'undefined') {
                showNotifToast(data.mensagem, 'sucesso');
            } else {
                alert(data.mensagem);
            }
            
            // Recarregar a página após salvar para mostrar novos valores
            if (grupo === 'apis' || grupo === 'smtp' || grupo === 'academy') {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            alert(data.erro || 'Erro ao salvar configurações');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão ao salvar configurações');
    }
}
async function testarSmtp() {
    const fd = new FormData(); fd.append('csrf_token', '<?= Csrf::token() ?>');
    const res = await fetch('<?= APP_URL ?>/admin/testar-smtp', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem || data.erro || 'Teste concluído.');
}
async function enviarEmailTeste() {
    const para = prompt('Email para teste:', '<?= Auth::usuario()['email'] ?? '' ?>');
    if (!para) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('email_teste', para);
    const res = await fetch('<?= APP_URL ?>/admin/testar-smtp', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem || data.erro || 'Resultado do envio.');
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
// === F-14: FUNCÕES DE API MANAGEMENT ===

// Toggle API ativo/inativo - F-14
async function toggleApiF14(provedor, status) {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('provedor', provedor);
    fd.append('status', status ? 1 : 0);
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/api/toggle', { 
            method: 'POST', 
            body: fd 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            // Atualizar badge visual
            const badge = document.getElementById('badge-' + provedor);
            if (status) {
                badge.textContent = 'Ativa';
                badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
            } else {
                badge.textContent = 'Inativa';
                badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
            }
            
            showNotifToast(data.mensagem, 'sucesso');
            verificarBannerApis();
        } else {
            // Reverter toggle se falhou
            document.getElementById('toggle-' + provedor).checked = !status;
            showNotifToast(data.erro, 'erro');
        }
    } catch (error) {
        console.error('Erro ao toggle API:', error);
        document.getElementById('toggle-' + provedor).checked = !status;
        showNotifToast('Erro de conexão', 'erro');
    }
}

// Salvar chave de API - F-14
async function salvarChaveF14(provedor) {
    const chaveInput = document.getElementById('chave-' + provedor);
    const chave = chaveInput.value.trim();
    
    if (!chave) {
        showNotifToast('Digite a chave de API', 'erro');
        chaveInput.focus();
        return;
    }
    
    // Não salvar se for valor mascarado
    if (chave === '••••••••••••••••' || chave.includes('•')) {
        showNotifToast('Campo já está preenchido. Digite nova chave para alterar.', 'aviso');
        return;
    }
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('provedor', provedor);
    fd.append('chave', chave);
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/api/salvar-chave', { 
            method: 'POST', 
            body: fd 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            // Mascarar campo após salvar
            chaveInput.value = '••••••••••••••••';
            
            showNotifToast(data.mensagem, 'sucesso');
            
            // Limpar status de teste para forçar novo teste
            const badge = document.getElementById('badge-' + provedor);
            badge.textContent = 'Não testada';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600';
        } else {
            showNotifToast(data.erro, 'erro');
        }
    } catch (error) {
        console.error('Erro ao salvar chave:', error);
        showNotifToast('Erro de conexão', 'erro');
    }
}

// Testar API individual - F-14
async function testarApiF14(provedor) {
    const badge = document.getElementById('badge-' + provedor);
    const button = event.target;
    
    // Mostrar loading
    const textoOriginal = button.textContent;
    button.textContent = '🔄 Testando...';
    button.disabled = true;
    
    badge.textContent = 'Testando...';
    badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-600';
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('provedor', provedor);
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/api/testar', { 
            method: 'POST', 
            body: fd 
        });
        const data = await res.json();
        
        // Atualizar badge com resultado
        if (data.sucesso) {
            badge.textContent = 'Ativa';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
        } else {
            badge.textContent = 'Erro';
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
        }
        
        showNotifToast(data.mensagem, data.sucesso ? 'sucesso' : 'erro');
        
    } catch (error) {
        console.error('Erro no teste:', error);
        badge.textContent = 'Erro de rede';
        badge.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600';
        showNotifToast('Erro de conexão', 'erro');
    } finally {
        button.textContent = textoOriginal;
        button.disabled = false;
    }
}

// Verificar se deve mostrar banner de aviso
function verificarBannerApis() {
    const togglePerplexity = document.getElementById('toggle-perplexity').checked;
    const toggleOpenai = document.getElementById('toggle-openai').checked;
    const toggleAnthropic = document.getElementById('toggle-anthropic').checked;
    
    const nenhumaAtiva = !togglePerplexity && !toggleOpenai && !toggleAnthropic;
    const banner = document.getElementById('banner-nenhuma-api');
    
    banner.style.display = nenhumaAtiva ? 'block' : 'none';
}

// Função de notificação toast
function showNotifToast(mensagem, tipo) {
    // Se existe sistema de toast, usar
    if (typeof showNotification !== 'undefined') {
        showNotification(mensagem, tipo);
        return;
    }
    
    // Fallback para alert
    alert(mensagem);
}

// Carregar status iniciais ao abrir aba APIs
document.addEventListener('DOMContentLoaded', function() {
    // Observer para detectar quando aba APIs é aberta
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.style.display !== 'none' && mutation.target.hasAttribute('x-show') && 
                mutation.target.getAttribute('x-show').includes('apis')) {
                carregarStatusApis();
            }
        });
    });
    
    const abaApis = document.querySelector('[x-show*="apis"]');
    if (abaApis) {
        observer.observe(abaApis, { attributes: true, attributeFilter: ['style'] });
    }
});

// Carregar status das APIs do servidor
async function carregarStatusApis() {
    // Esta função seria implementada para buscar status atuais do servidor
    // Por enquanto, apenas verificar banner
    verificarBannerApis();
}