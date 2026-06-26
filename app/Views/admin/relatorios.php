<?php $tituloPagina = 'Relatórios'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li><li>/</li><li class="font-medium text-primary">Relatórios</li></ol></nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">📊 Relatórios</h1>
    <div class="flex gap-2">
        <input type="date" value="2026-06-01" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none">
        <input type="date" value="2026-06-26" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none">
        <button disabled class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-400 cursor-not-allowed" title="Exportação disponível em breve">📥 Exportar</button>
    </div>
</div>

<div x-data="{ rel: 'clientes' }">
<div class="flex gap-2 mb-6">
    <button @click="rel='clientes'" :class="rel==='clientes'?'bg-primary text-white':'bg-white text-gray-700 border'" class="px-4 py-2 rounded-lg text-sm font-medium">Clientes</button>
    <button @click="rel='operacional'" :class="rel==='operacional'?'bg-primary text-white':'bg-white text-gray-700 border'" class="px-4 py-2 rounded-lg text-sm font-medium">Operacional</button>
    <button @click="rel='conteudo'" :class="rel==='conteudo'?'bg-primary text-white':'bg-white text-gray-700 border'" class="px-4 py-2 rounded-lg text-sm font-medium">Conteúdo</button>
    <button @click="rel='parceiros'" :class="rel==='parceiros'?'bg-primary text-white':'bg-white text-gray-700 border'" class="px-4 py-2 rounded-lg text-sm font-medium">Parceiros</button>
</div>

<!-- Relatório Clientes -->
<div x-show="rel==='clientes'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Total Clientes</p><p class="text-2xl font-bold text-primary">23</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Novos (mês)</p><p class="text-2xl font-bold text-green-600">+4</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Churned</p><p class="text-2xl font-bold text-red-600">1</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">MRR Total</p><p class="text-2xl font-bold text-primary">R$ 85.4k</p></div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border p-6"><canvas id="chart-clientes" height="120"></canvas></div>
</div>

<!-- Relatório Operacional -->
<div x-show="rel==='operacional'" style="display:none">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">SOPs Gerados</p><p class="text-2xl font-bold text-primary">312</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">SOPs Aprovados</p><p class="text-2xl font-bold text-green-600">287</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Diagnósticos</p><p class="text-2xl font-bold text-blue-600">128</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Planos Ativos</p><p class="text-2xl font-bold text-purple-600">34</p></div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border p-6"><canvas id="chart-operacional" height="120"></canvas></div>
</div>

<!-- Relatório Conteúdo -->
<div x-show="rel==='conteudo'" style="display:none">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Posts Gerados</p><p class="text-2xl font-bold text-primary">156</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Aprovados</p><p class="text-2xl font-bold text-green-600">134</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Publicados</p><p class="text-2xl font-bold text-blue-600">98</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Imagens DALL-E</p><p class="text-2xl font-bold text-purple-600">412</p></div>
    </div>
</div>

<!-- Relatório Parceiros -->
<div x-show="rel==='parceiros'" style="display:none">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Total Parceiros</p><p class="text-2xl font-bold text-primary">35</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Homologados</p><p class="text-2xl font-bold text-green-600">28</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Solicitações (mês)</p><p class="text-2xl font-bold text-orange-600">12</p></div>
        <div class="bg-white rounded-lg shadow-sm border p-4"><p class="text-xs text-gray-500">Avaliação Média</p><p class="text-2xl font-bold text-yellow-600">4.6 ⭐</p></div>
    </div>
</div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('chart-clientes'), { type:'line', data:{ labels:['Jan','Fev','Mar','Abr','Mai','Jun'], datasets:[{label:'Clientes Ativos', data:[15,18,19,21,22,23], borderColor:'#1E3A5F', fill:false, tension:.3},{label:'MRR (k)', data:[45,52,58,65,75,85], borderColor:'#1a7a1a', fill:false, tension:.3}] }, options:{responsive:true, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}} });
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
