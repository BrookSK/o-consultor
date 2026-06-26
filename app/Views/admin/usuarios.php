<?php $tituloPagina = 'Usuários'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li><li>/</li><li class="font-medium text-primary">Usuários</li></ol></nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gerenciar Usuários</h1>
    <button onclick="document.getElementById('modal-usuario').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">+ Novo Usuário</button>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-2 mb-4">
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos perfis</option><option>ADMIN_HOLDING</option><option>CONSULTOR_INTERNO</option><option>CLIENTE</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos status</option><option>Ativo</option><option>Inativo</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas empresas</option><option>Holding Digital</option><option>Tech Solutions</option><option>Varejo Express</option></select>
</div>

<!-- Tabela -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Nome</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Email</th>
                <th class="px-4 py-3 font-medium text-gray-500">Perfil</th>
                <th class="px-4 py-3 font-medium text-gray-500">Empresa</th>
                <th class="px-4 py-3 font-medium text-gray-500">Status</th>
                <th class="px-4 py-3 font-medium text-gray-500">Última ativ.</th>
                <th class="px-4 py-3 font-medium text-gray-500">Academy</th>
                <th class="px-4 py-3 font-medium text-gray-500">Ações</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['usuarios'] as $u):
                $perfilBadge = match($u['perfil']) { 'ADMIN_HOLDING' => 'bg-red-100 text-red-700', 'CONSULTOR_INTERNO' => 'bg-blue-100 text-blue-700', default => 'bg-green-100 text-green-700' };
                $statusBadge = $u['status'] === 'ativo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($u['nome']) ?></td>
                <td class="px-4 py-3 text-gray-600 text-xs"><?= htmlspecialchars($u['email']) ?></td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $perfilBadge ?>"><?= $u['perfil'] ?></span></td>
                <td class="px-4 py-3 text-center text-gray-600 text-xs"><?= htmlspecialchars($u['empresa']) ?></td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>"><?= ucfirst($u['status']) ?></span></td>
                <td class="px-4 py-3 text-center text-gray-400 text-xs"><?= $u['ultima_atividade'] ?></td>
                <td class="px-4 py-3 text-center">
                    <?php if ($u['academy']): ?><span class="text-green-600 text-xs font-medium">✓ Sim</span>
                    <?php else: ?><button onclick="alert('Convite enviado para <?= htmlspecialchars($u['email']) ?>! (Em produção: envia email via SMTP)')" class="text-xs px-2 py-1 bg-accent text-white rounded hover:bg-orange-700">Convidar</button><?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center"><button onclick="alert('Edição do usuário <?= htmlspecialchars($u['nome']) ?> será disponibilizada em breve.')" class="text-xs text-primary hover:underline">Editar</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Usuário -->
<div id="modal-usuario" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Novo Usuário</h3>
        <form id="form-usuario" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nome *</label><input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Email *</label><input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Perfil *</label><select name="perfil" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option>CLIENTE</option><option>CONSULTOR_INTERNO</option><option>ADMIN_HOLDING</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Empresa vinculada</label><select name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option value="">Nenhuma</option><option>Tech Solutions</option><option>Varejo Express</option><option>FoodService</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Senha temporária *</label><input type="text" name="senha" required value="<?= bin2hex(random_bytes(4)) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary font-mono"></div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-usuario').classList.add('hidden')" class="flex-1 border border-gray-300 py-2 rounded-lg text-sm text-gray-700">Cancelar</button>
                <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm font-medium">Criar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('form-usuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    const res = await fetch('<?= APP_URL ?>/admin/usuarios/salvar', { method:'POST', body: new FormData(this) });
    const data = await res.json();
    if (data.sucesso) { document.getElementById('modal-usuario').classList.add('hidden'); alert(data.mensagem); location.reload(); }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
