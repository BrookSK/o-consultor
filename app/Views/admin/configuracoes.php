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
        <?php foreach (['Diagnóstico', 'Plano de Ação', 'Manual Operacional', 'Central de Conteúdo', 'Máquina de Conteúdo', 'Academy', 'Parceiros', 'Governança'] as $mod): ?>
        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
            <span class="text-sm text-gray-700"><?= $mod ?></span>
            <input type="checkbox" checked class="w-4 h-4 text-primary rounded border-gray-300">
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- ABA APIs DE CONTEÚDO -->
<div x-show="aba==='apis'" style="display:none" class="max-w-3xl">
    <div class="space-y-4">
        <!-- Perplexity -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-semibold text-gray-800">Perplexity</h4>
                    <p class="text-xs text-gray-400 mt-0.5">Busca de notícias e conteúdos da web em tempo real.</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-xs text-gray-500">Ativa</span>
                    <input type="checkbox" name="config[perplexity_ativo]" value="1" checked class="w-4 h-4 text-primary rounded border-gray-300">
                </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs text-gray-500 mb-1">Chave de API</label><input type="password" name="config[perplexity_key]" placeholder="pplx-..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-xs outline-none focus:border-primary"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Modelo</label><input type="text" name="config[perplexity_modelo]" value="sonar-pro" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            </div>
        </div>

        <!-- OpenAI -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-semibold text-gray-800">OpenAI (GPT + DALL-E)</h4>
                    <p class="text-xs text-gray-400 mt-0.5">Análise, resumo, geração de conteúdo e imagens.</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-xs text-gray-500">Ativa</span>
                    <input type="checkbox" name="config[openai_ativo]" value="1" checked class="w-4 h-4 text-primary rounded border-gray-300">
                </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs text-gray-500 mb-1">Chave de API</label><input type="password" name="config[openai_key]" placeholder="sk-..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-xs outline-none focus:border-primary"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Modelo</label><input type="text" name="config[openai_modelo]" value="gpt-4o" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            </div>
        </div>

        <!-- Anthropic -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-semibold text-gray-800">Anthropic (Claude)</h4>
                    <p class="text-xs text-gray-400 mt-0.5">Alternativa ao GPT para análise de conteúdo (fallback).</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-xs text-gray-500">Ativa</span>
                    <input type="checkbox" name="config[anthropic_ativo]" value="1" class="w-4 h-4 text-primary rounded border-gray-300">
                </label>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs text-gray-500 mb-1">Chave de API</label><input type="password" name="config[anthropic_key]" placeholder="sk-ant-..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-xs outline-none focus:border-primary"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Modelo</label><input type="text" name="config[anthropic_modelo]" value="claude-sonnet-4-20250514" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            </div>
        </div>

        <div class="flex gap-3">
            <button onclick="salvarConfig('apis')" class="flex-1 bg-accent text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-orange-700">💾 Salvar APIs</button>
            <button onclick="testarApis()" class="flex-1 bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">🧪 Testar Todas</button>
        </div>

        <!-- Regra de uso -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-xs text-blue-700">
            <strong>Regra de uso:</strong> Perplexity → busca. GPT ou Claude → análise e resumo. Se ambos ativos: GPT padrão, Claude fallback. Se nenhuma ativa: aviso ao admin.
        </div>
    </div>
</div>

<!-- ABA ACADEMY -->
<div x-show="aba==='academy'" style="display:none" class="max-w-3xl space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <h4 class="font-semibold text-gray-800">Integração My Academy (SSO)</h4>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs text-gray-500 mb-1">URL base</label><input type="url" value="<?= htmlspecialchars($dados['academy']['url']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Rota SSO</label><input type="text" value="<?= htmlspecialchars($dados['academy']['rota_sso']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Chave JWT (criptografada)</label><input type="password" value="••••••••••••••••" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Parâmetro token</label><input type="text" value="<?= htmlspecialchars($dados['academy']['parametro']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="testarAcademy()" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">🧪 Testar Integração</button>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Status: <?= ucfirst($dados['academy']['status']) ?></span>
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
        <?php foreach (['Diagnóstico concluído' => true, 'SOP aprovado' => true, 'KPI em zona vermelha' => true, 'Tarefa vencida' => true, 'Novo conteúdo disponível' => false, 'Novo cliente cadastrado' => true, 'Login após 30 dias inativo' => false] as $evento => $ativo): ?>
        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
            <span class="text-sm text-gray-700"><?= $evento ?></span>
            <input type="checkbox" <?= $ativo ? 'checked' : '' ?> class="w-4 h-4 text-primary rounded border-gray-300">
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- ABA EMAIL/SMTP -->
<div x-show="aba==='email'" style="display:none" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-semibold text-gray-800">Configuração SMTP</h4>
            <label class="flex items-center gap-2 cursor-pointer">
                <span class="text-xs text-gray-500">Ativo</span>
                <input type="checkbox" name="config[smtp_ativo]" value="1" class="w-4 h-4 text-primary rounded border-gray-300">
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs text-gray-500 mb-1">Servidor SMTP *</label><input type="text" name="config[smtp_host]" placeholder="smtp.gmail.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Porta *</label><input type="number" name="config[smtp_porta]" value="587" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Usuário (email) *</label><input type="text" name="config[smtp_usuario]" placeholder="noreply@seudominio.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Senha *</label><input type="password" name="config[smtp_senha]" placeholder="••••••••" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Criptografia</label>
                <select name="config[smtp_criptografia]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="tls">TLS (porta 587) — Recomendado</option>
                    <option value="ssl">SSL (porta 465)</option>
                    <option value="nenhuma">Nenhuma (porta 25)</option>
                </select>
            </div>
            <div><label class="block text-xs text-gray-500 mb-1">Nome do remetente</label><input type="text" name="config[smtp_remetente_nome]" value="O Consultor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div class="md:col-span-2"><label class="block text-xs text-gray-500 mb-1">Email do remetente (From)</label><input type="email" name="config[smtp_remetente_email]" placeholder="noreply@oconsultor.digital" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
        </div>
        <div class="flex gap-3 pt-2">
            <button onclick="salvarConfig('smtp')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">Salvar SMTP</button>
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
    document.querySelectorAll('[name^="config["]').forEach(el => {
        if (el.type === 'checkbox') fd.append(el.name, el.checked ? '1' : '0');
        else fd.append(el.name, el.value);
    });
    const res = await fetch('<?= APP_URL ?>/admin/configuracoes/salvar', { method:'POST', body:fd });
    const data = await res.json();
    if (data.sucesso) { if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem); }
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
