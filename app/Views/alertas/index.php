<?php $tituloPagina = 'Alertas'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li><li>/</li><li class="font-medium text-primary">Alertas</li></ol></nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🔔 Alertas e Notificações</h1>
        <p class="text-gray-500 mt-1"><?= count(array_filter($dados['alertas'], fn($a) => !$a['lido'])) ?> não lidos</p>
    </div>
    <button onclick="marcarTodosLidos()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">✓ Marcar todos como lidos</button>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-2 mb-4">
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas prioridades</option><option>Alta</option><option>Média</option><option>Baixa</option><option>Informativo</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos módulos</option><option>KPIs</option><option>Plano de Ação</option><option>Manual Operacional</option><option>Diagnóstico</option><option>Governança</option><option>Central de Conteúdo</option><option>Academy</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos status</option><option>Não lidos</option><option>Lidos</option></select>
</div>

<!-- Tabela de Alertas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr>
                <th class="w-8 px-4 py-3"></th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Alerta</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Módulo</th>
                <th class="px-4 py-3 font-medium text-gray-500">Prioridade</th>
                <th class="px-4 py-3 font-medium text-gray-500">Data</th>
                <th class="px-4 py-3 font-medium text-gray-500">Ações</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['alertas'] as $a):
                $prioCfg = match($a['prioridade']) {
                    'alta' => ['badge' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-500'],
                    'media' => ['badge' => 'bg-yellow-100 text-yellow-700', 'dot' => 'bg-yellow-500'],
                    'baixa' => ['badge' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-400'],
                    default => ['badge' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400'],
                };
                $rowClass = !$a['lido'] ? 'bg-blue-50/30 font-medium' : '';
            ?>
            <tr class="hover:bg-gray-50 <?= $rowClass ?>">
                <td class="px-4 py-3"><span class="w-2.5 h-2.5 rounded-full inline-block <?= !$a['lido'] ? $prioCfg['dot'] : 'bg-gray-200' ?>"></span></td>
                <td class="px-4 py-3">
                    <p class="text-gray-800 <?= !$a['lido'] ? 'font-semibold' : '' ?>"><?= htmlspecialchars($a['titulo']) ?></p>
                    <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($a['descricao']) ?></p>
                </td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs text-gray-600"><?= htmlspecialchars($a['modulo']) ?></span></td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $prioCfg['badge'] ?>"><?= ucfirst($a['prioridade']) ?></span></td>
                <td class="px-4 py-3 text-center text-xs text-gray-400"><?= date('d/m H:i', strtotime($a['data'])) ?></td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="<?= APP_URL . $a['link'] ?>" class="text-xs text-primary hover:underline">Ir →</a>
                        <?php if ($a['tipo'] === 'kpi_vermelho' && $a['sop_id']): ?>
                        <button onclick="verContencao('<?= htmlspecialchars($a['sop_id']) ?>')" class="text-xs px-2 py-0.5 bg-red-600 text-white rounded hover:bg-red-700">Contingência</button>
                        <?php endif; ?>
                        <?php if (!$a['lido']): ?>
                        <button onclick="marcarLido(<?= $a['id'] ?>)" class="text-xs text-gray-400 hover:text-gray-600">✓</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Plano de Contingência -->
<div id="modal-contencao" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between sticky top-0 bg-white">
            <h3 class="text-lg font-semibold text-gray-800">🚨 Plano de Contingência</h3>
            <button onclick="document.getElementById('modal-contencao').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <!-- N1 -->
            <div class="border-l-4 border-l-blue-500 bg-blue-50/50 rounded-r-lg p-4">
                <div class="flex items-center gap-2 mb-2"><span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">N1</span><span class="text-sm font-semibold text-blue-800">Contingência Operacional</span></div>
                <p class="text-sm text-gray-700"><strong>Ativa quando:</strong> Serviço individual não responde mas demais estão operacionais.</p>
                <p class="text-sm text-gray-600 mt-2"><strong>Ação:</strong> Identificar serviço no Zabbix → verificar logs → restart → redirecionar se necessário → documentar no GLPI.</p>
                <p class="text-xs text-gray-500 mt-1"><strong>Quem:</strong> Analista N2 — máximo 30 minutos.</p>
            </div>
            <!-- N2 -->
            <div class="border-l-4 border-l-yellow-500 bg-yellow-50/50 rounded-r-lg p-4">
                <div class="flex items-center gap-2 mb-2"><span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">N2</span><span class="text-sm font-semibold text-yellow-800">Contingência Gerencial</span></div>
                <p class="text-sm text-gray-700"><strong>Ativa quando:</strong> Múltiplos serviços falharam OU downtime >30 min OU impacto significativo.</p>
                <p class="text-sm text-gray-600 mt-2"><strong>Ação:</strong> Acionar Gerente Ops → avaliar rollback → comunicar cliente → mobilizar plantão.</p>
                <p class="text-xs text-gray-500 mt-1"><strong>Quem:</strong> Gerente de Operações — resposta em 15 min.</p>
            </div>
            <!-- N3 -->
            <div class="border-l-4 border-l-red-500 bg-red-50/50 rounded-r-lg p-4">
                <div class="flex items-center gap-2 mb-2"><span class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">N3</span><span class="text-sm font-semibold text-red-800">Contingência Executiva / Jurídica</span></div>
                <p class="text-sm text-gray-700"><strong>Ativa quando:</strong> Perda de dados OU downtime >2h OU risco legal/financeiro.</p>
                <p class="text-sm text-gray-600 mt-2"><strong>Ação:</strong> Acionar Diretoria + Jurídico → preservar evidências → comunicação formal → acionar seguro.</p>
                <p class="text-xs text-gray-500 mt-1"><strong>Quem:</strong> Diretor TI + Jurídico + CEO — imediato.</p>
            </div>
        </div>
    </div>
</div>

<!-- Preferências de Notificação (link) -->
<div class="mt-6 text-center">
    <a href="<?= APP_URL ?>/perfil" class="text-sm text-primary hover:underline">⚙️ Configurar preferências de notificação</a>
</div>

<script>
async function marcarLido(id) {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('alerta_id', id);
    await fetch('<?= APP_URL ?>/alertas/marcar-lido', { method:'POST', body:fd });
    location.reload();
}
async function marcarTodosLidos() {
    // Em produção: endpoint específico. Aqui simula.
    alert('Todos os alertas marcados como lidos.');
    location.reload();
}
function verContencao(sopId) {
    document.getElementById('modal-contencao').classList.remove('hidden');
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
