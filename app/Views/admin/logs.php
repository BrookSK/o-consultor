<?php $tituloPagina = 'Logs do Sistema'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li><li>/</li><li class="font-medium text-primary">Logs</li></ol></nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">📋 Logs do Sistema</h1>
    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50" title="Exportar em CSV">📥 Exportar CSV</button>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas ações</option><option>Login/Logout</option><option>Geração de SOP</option><option>Aprovação</option><option>Acesso Academy</option><option>Alteração</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos módulos</option><option>Auth</option><option>Manual Operacional</option><option>Academy</option><option>Máquina de Conteúdo</option><option>Admin</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos usuários</option><option>admin@oconsultor.com.br</option><option>consultor@oconsultor.com.br</option><option>cliente@empresa.com.br</option></select>
    <input type="date" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none">
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b"><tr>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Data/Hora</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Usuário</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Ação</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Módulo</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Detalhes</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">IP</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['logs'] as $log): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 text-gray-500 font-mono"><?= $log['data'] ?></td>
                <td class="px-4 py-2.5 text-gray-700"><?= htmlspecialchars($log['usuario']) ?></td>
                <td class="px-4 py-2.5 font-medium text-gray-800"><?= htmlspecialchars($log['acao']) ?></td>
                <td class="px-4 py-2.5"><span class="px-2 py-0.5 bg-gray-100 rounded text-gray-600"><?= htmlspecialchars($log['modulo']) ?></span></td>
                <td class="px-4 py-2.5 text-gray-500 max-w-xs truncate"><?= htmlspecialchars($log['detalhes']) ?></td>
                <td class="px-4 py-2.5 text-gray-400 font-mono"><?= htmlspecialchars($log['ip']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-xs text-gray-500">Exibindo <?= count($dados['logs']) ?> registros</div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
