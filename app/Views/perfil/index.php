<?php $tituloPagina = 'Meu Perfil'; ?>
<?php ob_start(); ?>
<?php $u = $dados['usuario']; ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li><li>/</li><li class="font-medium text-primary">Meu Perfil</li></ol></nav>

<!-- Header Perfil -->
<div class="flex items-center gap-4 mb-6">
    <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold"><?= strtoupper(substr($u['nome'] ?? 'U', 0, 2)) ?></div>
    <div>
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($u['nome'] ?? '') ?></h1>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($u['email'] ?? '') ?> • <?= $u['perfil'] ?></p>
    </div>
</div>

<!-- 5 Abas -->
<div x-data="{ aba: 'dados' }">
<div class="border-b border-gray-200 mb-6"><nav class="flex gap-0 overflow-x-auto">
    <button @click="aba='dados'" :class="aba==='dados'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm whitespace-nowrap">👤 Meus Dados</button>
    <button @click="aba='academy'" :class="aba==='academy'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm whitespace-nowrap">🎓 Academy</button>
    <button @click="aba='seguranca'" :class="aba==='seguranca'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm whitespace-nowrap">🔒 Segurança</button>
    <button @click="aba='notificacoes'" :class="aba==='notificacoes'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm whitespace-nowrap">🔔 Notificações</button>
    <button @click="aba='historico'" :class="aba==='historico'?'border-b-2 border-primary text-primary font-semibold':'text-gray-500'" class="px-4 py-3 text-sm whitespace-nowrap">📋 Histórico</button>
</nav></div>

<!-- ABA MEUS DADOS -->
<div x-show="aba==='dados'" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="<?= APP_URL ?>/perfil/salvar" method="POST" class="space-y-4">
            <?= Csrf::campo() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label><input type="text" name="nome" value="<?= htmlspecialchars($u['nome'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label><input type="tel" name="telefone" placeholder="(00) 00000-0000" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Cargo</label><input type="text" name="cargo" placeholder="Seu cargo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label><input type="text" disabled value="Tech Solutions" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500"></div>
            <div class="flex justify-end"><button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button></div>
        </form>
    </div>
</div>

<!-- ABA ACADEMY -->
<div x-show="aba==='academy'" style="display:none" class="max-w-2xl space-y-6">
    <div id="academy-section" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Status da conta Academy</h3>
        <?php if ($dados['academy_vinculada']): ?>
        <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg mb-4">
            <span class="text-green-600 text-lg">✓</span>
            <div>
                <p class="text-sm font-medium text-green-800">Conta vinculada</p>
                <p class="text-xs text-green-600"><?= htmlspecialchars($dados['academy_email']) ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= APP_URL ?>/academy/sso" target="_blank" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">🚀 Acessar Academy</a>
            <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50" onclick="if(confirm('Desvincular conta da Academy?')) alert('Conta desvinculada.')">Desvincular</button>
        </div>
        <?php else: ?>
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
            <p class="text-sm text-yellow-800">Conta não vinculada. Vincule para acessar seus cursos.</p>
        </div>
        <form id="form-vincular" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="email" name="email_academy" placeholder="email@myacademy.com.br" required class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">Vincular</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Histórico de acessos SSO -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Histórico de acessos via SSO</h4>
        <div class="space-y-2">
            <?php foreach ($dados['acessos_academy'] as $ac): ?>
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-700"><?= $ac['data'] ?></span>
                <span class="text-xs text-gray-400"><?= $ac['ip'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ABA SEGURANÇA -->
<div x-show="aba==='seguranca'" style="display:none" class="max-w-2xl space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Alterar Senha</h3>
        <form id="form-senha" class="space-y-4" x-data="{ nova: '', forca: 0 }">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Senha atual</label><input type="password" name="senha_atual" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                <input type="password" name="nova_senha" required x-model="nova" @input="forca = calcForca(nova)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <!-- Checklist em tempo real -->
                <div class="mt-2 space-y-1 text-xs">
                    <p :class="nova.length >= 6 ? 'text-green-600' : 'text-gray-400'"><span x-text="nova.length >= 6 ? '✓' : '○'"></span> Mínimo 6 caracteres</p>
                    <p :class="/[A-Z]/.test(nova) ? 'text-green-600' : 'text-gray-400'"><span x-text="/[A-Z]/.test(nova) ? '✓' : '○'"></span> Uma letra maiúscula</p>
                    <p :class="/[0-9]/.test(nova) ? 'text-green-600' : 'text-gray-400'"><span x-text="/[0-9]/.test(nova) ? '✓' : '○'"></span> Um número</p>
                    <p :class="/[^A-Za-z0-9]/.test(nova) ? 'text-green-600' : 'text-gray-400'"><span x-text="/[^A-Za-z0-9]/.test(nova) ? '✓' : '○'"></span> Um caractere especial</p>
                </div>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label><input type="password" name="confirmar_senha" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Alterar Senha</button>
        </form>
    </div>
    <!-- Histórico de logins -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Últimos 10 logins</h4>
        <table class="w-full text-xs"><thead class="bg-gray-50"><tr><th class="text-left px-3 py-2 font-medium text-gray-500">Data</th><th class="text-left px-3 py-2 font-medium text-gray-500">IP</th><th class="text-left px-3 py-2 font-medium text-gray-500">Dispositivo</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['logins'] as $l): ?>
            <tr><td class="px-3 py-2 text-gray-700"><?= $l['data'] ?></td><td class="px-3 py-2 text-gray-500"><?= $l['ip'] ?></td><td class="px-3 py-2 text-gray-500"><?= $l['dispositivo'] ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
    </div>
</div>

<!-- ABA NOTIFICAÇÕES -->
<div x-show="aba==='notificacoes'" style="display:none" class="max-w-2xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Preferências de Notificação</h3>
        <p class="text-xs text-gray-500 mb-4">Escolha quais alertas deseja receber.</p>
        <div class="space-y-3" id="prefs-notif">
            <?php foreach ([
                ['chave' => 'kpi_vermelho', 'label' => 'KPI em zona vermelha (crítico)', 'ativo' => true],
                ['chave' => 'tarefa_vencida', 'label' => 'Tarefa vencida', 'ativo' => true],
                ['chave' => 'kpi_amarelo', 'label' => 'KPI em zona amarela', 'ativo' => true],
                ['chave' => 'sop_pendente', 'label' => 'SOP sem aprovação 30+ dias', 'ativo' => true],
                ['chave' => 'noticia_nova', 'label' => 'Nova notícia relevante', 'ativo' => true],
                ['chave' => 'conteudo_gerado', 'label' => 'Conteúdo gerado', 'ativo' => false],
                ['chave' => 'reuniao_proxima', 'label' => 'Reunião próxima (24h)', 'ativo' => true],
                ['chave' => 'diagnostico_desatualizado', 'label' => 'Diagnóstico desatualizado (90+ dias)', 'ativo' => false],
                ['chave' => 'sop_revisao', 'label' => 'SOP sem revisão (6+ meses)', 'ativo' => false],
            ] as $pref): ?>
            <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                <span class="text-sm text-gray-700"><?= $pref['label'] ?></span>
                <input type="checkbox" name="pref_<?= $pref['chave'] ?>" <?= $pref['ativo'] ? 'checked' : '' ?> class="w-4 h-4 text-primary rounded border-gray-300">
            </label>
            <?php endforeach; ?>
        </div>
        <button onclick="salvarPrefs()" class="mt-4 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">Salvar Preferências</button>
    </div>
</div>

<!-- ABA HISTÓRICO -->
<div x-show="aba==='historico'" style="display:none" class="max-w-3xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Últimas ações</h3>
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos módulos</option><option>Auth</option><option>Diagnóstico</option><option>Plano de Ação</option><option>Manual Operacional</option><option>Academy</option><option>Máquina de Conteúdo</option></select>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-2 font-medium text-gray-500">Data</th><th class="text-left px-4 py-2 font-medium text-gray-500">Ação</th><th class="px-4 py-2 font-medium text-gray-500">Módulo</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['historico'] as $h): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-xs text-gray-500"><?= $h['data'] ?></td>
                    <td class="px-4 py-2 text-gray-800"><?= htmlspecialchars($h['acao']) ?></td>
                    <td class="px-4 py-2 text-center"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs text-gray-600"><?= htmlspecialchars($h['modulo']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /abas -->

<!-- Jornada de Completude (apenas cliente) -->
<?php if (Auth::isCliente()):
    $completos = count(array_filter($dados['jornada'], fn($j) => $j['completo']));
    $total = count($dados['jornada']);
    $pct = round(($completos / $total) * 100);
?>
<div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-800">🏆 Complete sua jornada</h3>
        <span class="text-sm font-bold text-primary"><?= $pct ?>%</span>
    </div>
    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden mb-4">
        <div class="h-full bg-gradient-to-r from-primary to-accent rounded-full transition-all" style="width:<?= $pct ?>%"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
        <?php foreach ($dados['jornada'] as $j): ?>
        <div class="flex items-center gap-2 p-2 rounded <?= $j['completo'] ? 'bg-green-50' : 'bg-gray-50' ?>">
            <span class="text-sm"><?= $j['completo'] ? '✅' : '⬜' ?></span>
            <span class="text-xs <?= $j['completo'] ? 'text-green-700 font-medium' : 'text-gray-500' ?>"><?= $j['label'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($pct === 100): ?>
    <div class="mt-4 text-center p-4 bg-gradient-to-r from-primary/5 to-accent/5 rounded-lg border border-accent/20">
        <span class="text-2xl">🎉</span>
        <p class="text-sm font-bold text-primary mt-1">Operação Estruturada!</p>
        <p class="text-xs text-gray-500">Parabéns! Sua empresa está 100% estruturada na plataforma.</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function calcForca(s) { let f=0; if(s.length>=6)f++; if(/[A-Z]/.test(s))f++; if(/[0-9]/.test(s))f++; if(/[^A-Za-z0-9]/.test(s))f++; return f; }

document.getElementById('form-senha')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const res = await fetch('<?= APP_URL ?>/perfil/alterar-senha', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem || data.erro);
});

document.getElementById('form-vincular')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const res = await fetch('<?= APP_URL ?>/perfil/vincular-academy', { method:'POST', body:fd });
    const data = await res.json();
    if (data.sucesso) { 
        alert(data.mensagem); 
        location.reload(); 
    } else {
        alert(data.erro || 'Erro ao vincular Academy');
    }
});

// Desvincular Academy
async function desvincularAcademy() {
    if (!confirm('Tem certeza que deseja desvincular sua conta da Academy?\n\nVocê perderá o acesso automático aos cursos.')) {
        return;
    }
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    
    try {
        const res = await fetch('<?= APP_URL ?>/academy/desvincular', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            alert(data.mensagem);
            location.reload();
        } else {
            alert(data.erro || 'Erro ao desvincular Academy');
        }
    } catch (e) {
        alert('Erro de conexão');
    }
}

async function salvarPrefs() {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    document.querySelectorAll('#prefs-notif input[type=checkbox]').forEach(cb => fd.append(cb.name, cb.checked ? '1' : '0'));
    const res = await fetch('<?= APP_URL ?>/alertas/preferencias', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem);
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
