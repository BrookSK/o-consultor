<?php $tituloPagina = 'Revisar SOP'; ?>
<?php ob_start(); ?>
<?php $sop = $dados['sop']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($sop['id']) ?></li>
    </ol>
</nav>

<!-- Header SOP -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold text-primary bg-primary/10"><?= htmlspecialchars($sop['id']) ?></span>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">v<?= $sop['versao'] ?></span>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700"><?= htmlspecialchars($sop['norma']) ?></span>
            </div>
            <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($sop['nome']) ?></h1>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($sop['empresa']) ?> — <?= htmlspecialchars($sop['setor']) ?></p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <button onclick="salvarRascunho()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Salvar Rascunho</button>
            <button onclick="aprovarSop()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">✓ Aprovar SOP</button>
        </div>
    </div>
</div>

<!-- Componente 1: OBJETIVO -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">1. Objetivo</h3>
    <textarea rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($sop['objetivo']) ?></textarea>
</div>

<!-- Componente 2: ESCOPO -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">2. Escopo</h3>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-green-700 mb-1">✓ Aplica-se a:</label>
            <textarea rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($sop['escopo_aplica']) ?></textarea>
        </div>
        <div>
            <label class="block text-xs font-medium text-red-700 mb-1">✗ Não se aplica a:</label>
            <textarea rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($sop['escopo_nao_aplica']) ?></textarea>
        </div>
    </div>
</div>

<!-- Componente 3: SUBTÓPICOS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">3. Subtópicos do Processo</h3>
    <div class="space-y-3">
        <?php foreach ($sop['subtopicos'] as $i => $sub): ?>
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center font-bold"><?= chr(65 + $i) ?></span>
                <input type="text" value="<?= htmlspecialchars($sub['nome']) ?>" class="flex-1 px-3 py-1.5 border border-gray-200 rounded text-sm font-semibold outline-none focus:border-primary">
            </div>
            <textarea rows="2" class="w-full px-3 py-2 border border-gray-200 rounded text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($sub['descricao']) ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Componente 4: RESPONSÁVEIS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">4. Responsáveis</h3>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="text-left px-3 py-2 font-medium text-gray-500">Papel</th><th class="text-left px-3 py-2 font-medium text-gray-500">Cargo</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($sop['responsaveis'] as $r): ?>
            <tr><td class="px-3 py-2 font-medium text-gray-700"><?= htmlspecialchars($r['papel']) ?></td><td class="px-3 py-2"><input type="text" value="<?= htmlspecialchars($r['cargo']) ?>" class="w-full px-2 py-1 border border-gray-200 rounded text-sm outline-none focus:border-primary"></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Componente 5: PRÉ-REQUISITOS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">5. Pré-requisitos</h3>
    <ol class="space-y-2">
        <?php foreach ($sop['prerequisitos'] as $i => $pre): ?>
        <li class="flex items-start gap-2">
            <span class="w-5 h-5 rounded-full bg-primary/10 text-primary text-xs flex items-center justify-center flex-shrink-0 mt-0.5 font-bold"><?= $i + 1 ?></span>
            <input type="text" value="<?= htmlspecialchars($pre) ?>" class="flex-1 px-3 py-1.5 border border-gray-200 rounded text-sm outline-none focus:border-primary">
        </li>
        <?php endforeach; ?>
    </ol>
</div>

<!-- Componente 6: FERRAMENTAS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">6. Ferramentas</h3>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($sop['ferramentas'] as $ferr): ?>
        <span class="px-3 py-1.5 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-700"><?= htmlspecialchars($ferr) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Componente 7: PROCEDIMENTO PADRÃO (Subtópico A) -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-1">7. Procedimento Padrão</h3>
    <p class="text-xs text-gray-500 mb-4">Subtópico A — <?= htmlspecialchars($sop['subtopicos'][0]['nome']) ?></p>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-2 font-medium text-gray-500 w-10">#</th>
                    <th class="px-2 py-2 font-medium text-gray-500 text-left">Ação Detalhada</th>
                    <th class="px-2 py-2 font-medium text-gray-500 w-28">Responsável</th>
                    <th class="px-2 py-2 font-medium text-gray-500 w-16">Prazo</th>
                    <th class="px-2 py-2 font-medium text-gray-500 w-24">Sistema</th>
                    <th class="px-2 py-2 font-medium text-gray-500 w-36">Validação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($sop['procedimento_subtopico_1'] as $p): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-2 py-2 text-center font-bold text-primary"><?= $p['passo'] ?? '' ?></td>
                    <td class="px-2 py-2 text-gray-700"><?= htmlspecialchars($p['acao'] ?? '') ?></td>
                    <td class="px-2 py-2 text-gray-600"><?= htmlspecialchars($p['responsavel'] ?? '') ?></td>
                    <td class="px-2 py-2 text-gray-600"><?= htmlspecialchars($p['prazo'] ?? 'Imediato') ?></td>
                    <td class="px-2 py-2 text-gray-600"><?= htmlspecialchars($p['sistema'] ?? 'Manual') ?></td>
                    <td class="px-2 py-2 text-gray-500"><?= htmlspecialchars($p['validacao'] ?? 'Verificação visual') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Componente 8: CHECKLIST -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">8. Checklist Operacional</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <?php foreach ($sop['checklist'] as $item): ?>
        <label class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
            <input type="checkbox" class="w-4 h-4 mt-0.5 text-primary rounded border-gray-300">
            <span class="text-sm text-gray-700"><?= htmlspecialchars($item) ?></span>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- Componente 9: EVIDÊNCIAS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">9. Evidências Obrigatórias</h3>
    <ol class="space-y-2">
        <?php foreach ($sop['evidencias'] as $i => $ev): ?>
        <li class="flex items-start gap-2 text-sm text-gray-700">
            <span class="font-bold text-primary"><?= $i + 1 ?>.</span>
            <span><?= htmlspecialchars($ev) ?></span>
        </li>
        <?php endforeach; ?>
    </ol>
</div>

<!-- Componente 10: RELATÓRIOS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">10. Relatórios Obrigatórios</h3>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="text-left px-3 py-2 font-medium text-gray-500">O que reportar</th><th class="text-left px-3 py-2 font-medium text-gray-500">Para quem</th><th class="text-left px-3 py-2 font-medium text-gray-500">Frequência</th><th class="text-left px-3 py-2 font-medium text-gray-500">Canal</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($sop['relatorios'] as $rel): ?>
            <tr><td class="px-3 py-2 text-gray-800"><?= htmlspecialchars($rel['oque']) ?></td><td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($rel['para_quem']) ?></td><td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($rel['frequencia']) ?></td><td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($rel['canal']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Componente 11: KPIs NATIVOS -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">11. KPIs Nativos</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">KPI</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500"><span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span> Verde</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500"><span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span> Amarela</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500"><span class="inline-block w-3 h-3 bg-red-500 rounded-full"></span> Vermelha</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Ação na zona vermelha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($sop['kpis'] as $kpi): ?>
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-800 text-sm"><?= htmlspecialchars($kpi['kpi']) ?></td>
                    <td class="px-3 py-2 text-green-700"><?= htmlspecialchars($kpi['verde']) ?></td>
                    <td class="px-3 py-2 text-yellow-700"><?= htmlspecialchars($kpi['amarela']) ?></td>
                    <td class="px-3 py-2 text-red-700"><?= htmlspecialchars($kpi['vermelha']) ?></td>
                    <td class="px-3 py-2 text-gray-600"><?= htmlspecialchars($kpi['acao_vermelha']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Componente 12: PLANOS DE CONTINGÊNCIA (N1, N2, N3) -->
<div class="space-y-4 mb-4">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">12. Planos de Contingência</h3>

    <!-- N1 -->
    <div class="border-l-4 border-l-blue-500 bg-blue-50/50 rounded-r-lg p-5 border border-blue-200">
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">N1</span>
            <span class="text-sm font-semibold text-blue-800">Contingência Operacional</span>
        </div>
        <div class="space-y-3 text-sm">
            <div><label class="block text-xs font-medium text-blue-700 mb-1">Situação que ativa:</label><textarea rows="2" class="w-full px-3 py-2 border border-blue-200 rounded text-sm outline-none resize-none"><?= htmlspecialchars($sop['contencao_n1']['situacao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-blue-700 mb-1">Ação imediata:</label><textarea rows="4" class="w-full px-3 py-2 border border-blue-200 rounded text-sm outline-none resize-none font-mono text-xs"><?= htmlspecialchars($sop['contencao_n1']['acao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-blue-700 mb-1">Quem aciona + prazo:</label><input type="text" value="<?= htmlspecialchars($sop['contencao_n1']['quem']) ?>" class="w-full px-3 py-2 border border-blue-200 rounded text-sm outline-none"></div>
            <div><label class="block text-xs font-medium text-blue-700 mb-1">Critério para escalar ao N2:</label><input type="text" value="<?= htmlspecialchars($sop['contencao_n1']['escalar']) ?>" class="w-full px-3 py-2 border border-blue-200 rounded text-sm outline-none"></div>
        </div>
    </div>

    <!-- N2 -->
    <div class="border-l-4 border-l-yellow-500 bg-yellow-50/50 rounded-r-lg p-5 border border-yellow-200">
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">N2</span>
            <span class="text-sm font-semibold text-yellow-800">Contingência Gerencial</span>
        </div>
        <div class="space-y-3 text-sm">
            <div><label class="block text-xs font-medium text-yellow-700 mb-1">Situação que ativa:</label><textarea rows="2" class="w-full px-3 py-2 border border-yellow-200 rounded text-sm outline-none resize-none"><?= htmlspecialchars($sop['contencao_n2']['situacao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-yellow-700 mb-1">Ação gerencial:</label><textarea rows="4" class="w-full px-3 py-2 border border-yellow-200 rounded text-sm outline-none resize-none font-mono text-xs"><?= htmlspecialchars($sop['contencao_n2']['acao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-yellow-700 mb-1">Quem aciona + prazo:</label><input type="text" value="<?= htmlspecialchars($sop['contencao_n2']['quem']) ?>" class="w-full px-3 py-2 border border-yellow-200 rounded text-sm outline-none"></div>
            <div><label class="block text-xs font-medium text-yellow-700 mb-1">Critério para escalar ao N3:</label><input type="text" value="<?= htmlspecialchars($sop['contencao_n2']['escalar']) ?>" class="w-full px-3 py-2 border border-yellow-200 rounded text-sm outline-none"></div>
        </div>
    </div>

    <!-- N3 -->
    <div class="border-l-4 border-l-red-500 bg-red-50/50 rounded-r-lg p-5 border border-red-200">
        <div class="flex items-center gap-2 mb-3">
            <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">N3</span>
            <span class="text-sm font-semibold text-red-800">Contingência Executiva e Jurídica</span>
        </div>
        <div class="space-y-3 text-sm">
            <div><label class="block text-xs font-medium text-red-700 mb-1">Situação que ativa:</label><textarea rows="2" class="w-full px-3 py-2 border border-red-200 rounded text-sm outline-none resize-none"><?= htmlspecialchars($sop['contencao_n3']['situacao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-red-700 mb-1">Ação executiva e jurídica:</label><textarea rows="4" class="w-full px-3 py-2 border border-red-200 rounded text-sm outline-none resize-none font-mono text-xs"><?= htmlspecialchars($sop['contencao_n3']['acao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-red-700 mb-1">Responsáveis:</label><input type="text" value="<?= htmlspecialchars($sop['contencao_n3']['quem']) ?>" class="w-full px-3 py-2 border border-red-200 rounded text-sm outline-none"></div>
            <div><label class="block text-xs font-medium text-red-700 mb-1">Comunicação:</label><textarea rows="2" class="w-full px-3 py-2 border border-red-200 rounded text-sm outline-none resize-none"><?= htmlspecialchars($sop['contencao_n3']['comunicacao']) ?></textarea></div>
            <div><label class="block text-xs font-medium text-red-700 mb-1">Documentação obrigatória:</label><textarea rows="2" class="w-full px-3 py-2 border border-red-200 rounded text-sm outline-none resize-none"><?= htmlspecialchars($sop['contencao_n3']['documentacao']) ?></textarea></div>
        </div>
    </div>
</div>

<!-- Componente 13: VERSIONAMENTO -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">13. Versionamento</h3>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr><th class="text-left px-3 py-2 font-medium text-gray-500">Versão</th><th class="text-left px-3 py-2 font-medium text-gray-500">Data</th><th class="text-left px-3 py-2 font-medium text-gray-500">Motivo</th><th class="text-left px-3 py-2 font-medium text-gray-500">Aprovador</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <tr><td class="px-3 py-2 font-mono font-bold text-primary">v1.0</td><td class="px-3 py-2 text-gray-600"><?= date('d/m/Y') ?></td><td class="px-3 py-2 text-gray-600">Criação inicial via IA</td><td class="px-3 py-2 text-gray-600">Pendente aprovação</td></tr>
        </tbody>
    </table>
</div>

<!-- Ações Finais -->
<div class="bg-gray-50 rounded-lg border border-gray-200 p-6 mb-6">
    <div class="flex flex-col sm:flex-row gap-3">
        <button onclick="aprovarSop()" class="flex-1 bg-green-600 text-white px-5 py-3 rounded-lg text-sm font-semibold hover:bg-green-700 transition text-center">✓ Aprovar SOP</button>
        <button onclick="mostrarAjusteIA()" class="flex-1 bg-blue-600 text-white px-5 py-3 rounded-lg text-sm font-medium hover:bg-blue-700 transition text-center">🤖 Solicitar Ajuste da IA</button>
        <button onclick="if(confirm('Regenerar o SOP do zero? O conteúdo atual será substituído.')) { regenerarSop(); }" class="flex-1 bg-gray-500 text-white px-5 py-3 rounded-lg text-sm font-medium hover:bg-gray-600 transition text-center">🔄 Regenerar do Zero</button>
        <button onclick="salvarRascunho()" class="flex-1 border border-gray-300 text-gray-700 px-5 py-3 rounded-lg text-sm font-medium hover:bg-gray-100 transition text-center">💾 Salvar Rascunho</button>
    </div>
</div>

<script>
async function aprovarSop() {
    if (!confirm('Confirma a aprovação deste SOP? Os KPIs serão enviados ao painel automaticamente.')) return;
    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('sop_id', '<?= $sop['id'] === 'SOP-TI-ONB-001' ? '1' : $sop['id'] ?>');
    try {
        const res = await fetch('<?= APP_URL ?>/sop/aprovar', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem);
            else alert(data.mensagem);
            setTimeout(() => window.location.href = '<?= APP_URL ?>/manual-operacional', 1000);
        } else {
            alert(data.erro || 'Erro ao aprovar SOP.');
        }
    } catch(e) { alert('Erro de conexão.'); }
}

async function salvarRascunho() {
    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    
    // CORREÇÃO: Usar o ID numérico do banco de dados, não o código SOP
    <?php if (isset($sop) && !empty($sop)): ?>
        <?php
        // Buscar ID real do SOP no banco se necessário
        if (!isset($sop['id']) || empty($sop['id'])) {
            $sopReal = Database::queryOne("SELECT id FROM sops WHERE sop_codigo = :codigo LIMIT 1", ['codigo' => $sop['sop_codigo'] ?? '']);
            $sopIdReal = $sopReal ? $sopReal['id'] : 0;
        } else {
            $sopIdReal = $sop['id'];
        }
        ?>
        formData.append('sop_id', '<?= $sopIdReal ?>');
        
        <?php if (!$sopIdReal): ?>
        console.error('ID do SOP não encontrado no banco');
        alert('Erro: SOP não identificado no sistema. Verifique se o SOP foi gerado corretamente.');
        return;
        <?php endif; ?>
    <?php else: ?>
        console.error('SOP não encontrado');
        alert('Erro: SOP não identificado');
        return;
    <?php endif; ?>
    
    // COLETAR TODOS OS CAMPOS EDITÁVEIS DA TELA
    const campos = [
        'objetivo', 'escopo_aplica', 'escopo_nao_aplica', 
        'prerequisitos', 'ferramentas', 'checklist', 
        'evidencias', 'relatorios'
    ];
    
    campos.forEach(campo => {
        const elemento = document.querySelector(`[name="${campo}"], #${campo}, .${campo}`);
        if (elemento) {
            if (elemento.type === 'checkbox') {
                formData.append(campo, elemento.checked ? '1' : '0');
            } else if (elemento.tagName === 'TEXTAREA') {
                formData.append(campo, elemento.value);
            } else if (elemento.value) {
                formData.append(campo, elemento.value);
            }
        }
    });
    
    // Coletar arrays (subtópicos, responsáveis, KPIs, etc.)
    const camposArray = ['subtopicos', 'responsaveis', 'kpis'];
    camposArray.forEach(campo => {
        const elementos = document.querySelectorAll(`[data-field="${campo}"] input, [data-field="${campo}"] textarea`);
        const valores = [];
        elementos.forEach(el => {
            if (el.value.trim()) valores.push(el.value.trim());
        });
        if (valores.length > 0) {
            formData.append(campo, JSON.stringify(valores));
        }
    });
    
    console.log('Salvando rascunho com dados:', Object.fromEntries(formData));
    
    try {
        const res = await fetch('<?= APP_URL ?>/sop/salvar-rascunho', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
        } else {
            console.error('Erro do servidor:', data);
            alert(data.erro || 'Erro ao salvar rascunho.');
        }
    } catch(e) { 
        console.error('Erro de conexão:', e);
        alert('Erro de conexão.'); 
    }
}

function mostrarAjusteIA() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Solicitar Ajuste da IA</h3>
            <p class="text-sm text-gray-600 mb-4">Descreva o que você gostaria que a IA ajuste neste SOP:</p>
            <textarea id="instrucao-ajuste" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none" placeholder="Ex: O procedimento de backup precisa incluir validação do hash MD5"></textarea>
            <div class="mt-4 flex gap-2">
                <button onclick="executarAjusteIA()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">🤖 Solicitar Ajuste</button>
                <button onclick="this.closest('.fixed').remove()" class="flex-1 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-100">Cancelar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function executarAjusteIA() {
    const instrucao = document.getElementById('instrucao-ajuste').value.trim();
    if (!instrucao) {
        alert('Por favor, descreva o ajuste desejado.');
        return;
    }

    const modal = document.querySelector('.fixed');
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 text-center">
            <div class="animate-spin w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full mx-auto mb-4"></div>
            <p class="text-sm font-medium">IA processando ajuste...</p>
            <p class="text-xs text-gray-500 mt-1">Isso pode levar alguns segundos</p>
        </div>
    `;

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('sop_id', '<?= $sop['id'] === 'SOP-TI-ONB-001' ? '1' : $sop['id'] ?>');
    formData.append('instrucao', instrucao);
    formData.append('secoes_a_ajustar', JSON.stringify(['procedimentos', 'checklist'])); // Seções mais comuns de ajuste

    try {
        const res = await fetch('<?= APP_URL ?>/sop/ajustar', { method: 'POST', body: formData });
        const data = await res.json();
        modal.remove();
        
        if (data.sucesso) {
            alert(data.mensagem);
            location.reload(); // Recarregar para mostrar versão atualizada
        } else {
            alert(data.erro || 'Erro ao processar ajuste.');
        }
    } catch(e) {
        modal.remove();
        alert('Erro de conexão.');
    }
}

async function regenerarSop() {
    // Redirecionar para gerar novamente (substitui o atual)
    window.location.href = '<?= APP_URL ?>/manual-operacional';
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
