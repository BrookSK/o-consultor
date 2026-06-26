<?php $tituloPagina = 'Governança'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Governança</li>
    </ol>
</nav>

<h1 class="text-2xl font-bold text-gray-800 mb-6">Governança Corporativa</h1>

<div x-data="{ aba: 'hierarquia' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'hierarquia'" :class="aba === 'hierarquia' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">🏛️ Hierarquia</button>
            <button @click="aba = 'reunioes'" :class="aba === 'reunioes' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📅 Reuniões</button>
            <button @click="aba = 'compliance'" :class="aba === 'compliance' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">✓ Compliance</button>
            <button @click="aba = 'principios'" :class="aba === 'principios' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">💡 Princípios</button>
        </nav>
    </div>

    <!-- ABA HIERARQUIA -->
    <div x-show="aba === 'hierarquia'" x-transition>
        <p class="text-sm text-gray-500 mb-6">Estrutura de 9 níveis da cadeia de governança.</p>
        <div class="flex flex-col items-center gap-2">
            <?php
            $niveis = [
                ['nome' => 'CLIENTE', 'desc' => 'Origem da demanda e beneficiário final', 'cor' => 'bg-blue-600'],
                ['nome' => 'CONSULTOR', 'desc' => 'Análise, diagnóstico e acompanhamento', 'cor' => 'bg-blue-500'],
                ['nome' => 'IA', 'desc' => 'Geração de SOPs, conteúdo e análise de dados', 'cor' => 'bg-purple-600'],
                ['nome' => 'PLANO DE AÇÃO', 'desc' => 'Prioridades, tarefas e prazos definidos', 'cor' => 'bg-indigo-600'],
                ['nome' => 'HOLDING', 'desc' => 'Gestão estratégica e governança central', 'cor' => 'bg-primary'],
                ['nome' => 'ESPECIALIZADAS', 'desc' => 'Empresas do grupo com expertise específica', 'cor' => 'bg-teal-600'],
                ['nome' => 'PARCEIROS', 'desc' => 'Rede homologada de prestadores de serviço', 'cor' => 'bg-orange-600'],
                ['nome' => 'EXECUÇÃO', 'desc' => 'Implementação operacional das ações', 'cor' => 'bg-yellow-600'],
                ['nome' => 'RESULTADOS', 'desc' => 'KPIs, métricas e entrega de valor', 'cor' => 'bg-green-600'],
            ];
            foreach ($niveis as $i => $n):
            ?>
            <div class="w-full max-w-lg">
                <div class="<?= $n['cor'] ?> text-white rounded-lg p-4 flex items-center gap-4">
                    <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold"><?= $i + 1 ?></span>
                    <div>
                        <p class="font-semibold text-sm"><?= $n['nome'] ?></p>
                        <p class="text-xs text-white/80"><?= $n['desc'] ?></p>
                    </div>
                </div>
                <?php if ($i < 8): ?>
                <div class="flex justify-center"><div class="w-0.5 h-4 bg-gray-300"></div></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ABA REUNIÕES -->
    <div x-show="aba === 'reunioes'" x-transition style="display:none;">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Reuniões de governança e comitês.</p>
            <button onclick="document.getElementById('modal-reuniao-gov').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">+ Nova Reunião</button>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3 font-medium text-gray-500">Data</th><th class="text-left px-4 py-3 font-medium text-gray-500">Título</th><th class="text-left px-4 py-3 font-medium text-gray-500">Participantes</th><th class="px-4 py-3 font-medium text-gray-500">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($dados['reunioes'] as $r):
                        $stCfg = $r['status'] === 'agendada' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800 font-medium"><?= date('d/m/Y', strtotime($r['data'])) ?> <?= $r['hora'] ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($r['titulo']) ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($r['participantes']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $stCfg ?>"><?= ucfirst($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ABA COMPLIANCE -->
    <div x-show="aba === 'compliance'" x-transition style="display:none;">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Checklist de conformidade e auditorias.</p>
            <button onclick="registrarAuditoria()" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">📋 Registrar Auditoria</button>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3 font-medium text-gray-500">Item</th><th class="px-4 py-3 font-medium text-gray-500">Status</th><th class="px-4 py-3 font-medium text-gray-500">Última Verificação</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($dados['compliance'] as $c):
                        $stCfg = match($c['status']) {
                            'conforme' => ['badge' => 'bg-green-100 text-green-700', 'icon' => '✓'],
                            'pendente' => ['badge' => 'bg-yellow-100 text-yellow-700', 'icon' => '◎'],
                            'atencao' => ['badge' => 'bg-red-100 text-red-700', 'icon' => '⚠'],
                            default => ['badge' => 'bg-gray-100 text-gray-600', 'icon' => '—'],
                        };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($c['item']) ?></td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $stCfg['badge'] ?>"><?= $stCfg['icon'] ?> <?= ucfirst($c['status']) ?></span></td>
                        <td class="px-4 py-3 text-center text-gray-500"><?= date('d/m/Y', strtotime($c['ultima'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ABA PRINCÍPIOS -->
    <div x-show="aba === 'principios'" x-transition style="display:none;">
        <p class="text-sm text-gray-500 mb-6">Princípios que regem a governança da holding.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $principios = [
                ['titulo' => 'Transparência', 'desc' => 'Todas as decisões, processos e resultados são visíveis para as partes envolvidas. Sem informações ocultas.', 'icone' => '👁️'],
                ['titulo' => 'Centralização', 'desc' => 'A holding centraliza governança, padrões e ferramentas. Cada empresa mantém autonomia operacional dentro do framework.', 'icone' => '🎯'],
                ['titulo' => 'Padronização', 'desc' => 'Processos, SOPs e métricas seguem padrões unificados. Facilitam escalabilidade e comparação entre empresas.', 'icone' => '📐'],
                ['titulo' => 'Escalabilidade', 'desc' => 'Toda decisão considera a capacidade de escala. Nenhum processo é aceito se não for replicável.', 'icone' => '📈'],
                ['titulo' => 'Segurança', 'desc' => 'Dados, acessos e operações seguem protocolos de segurança. LGPD, backups e contingência são mandatórios.', 'icone' => '🔒'],
                ['titulo' => 'Governança Compartilhada', 'desc' => 'Decisões estratégicas são colegiadas. Comitê de governança se reúne periodicamente com representação de todos os níveis.', 'icone' => '🤝'],
            ];
            foreach ($principios as $pr):
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                <span class="text-2xl mb-3 block"><?= $pr['icone'] ?></span>
                <h4 class="font-semibold text-gray-800 mb-2"><?= $pr['titulo'] ?></h4>
                <p class="text-xs text-gray-600 leading-relaxed"><?= $pr['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Nova Reunião -->
<div id="modal-reuniao-gov" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Nova Reunião de Governança</h3>
        <form id="form-reuniao-gov" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-xs font-medium text-gray-700 mb-1">Data *</label><input type="date" name="data" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1">Hora *</label><input type="time" name="hora" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            </div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Título *</label><input type="text" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Comitê de Governança Mensal"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Participantes</label><input type="text" name="participantes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Separados por vírgula"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Pauta</label><textarea name="pauta" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Temas a serem discutidos"></textarea></div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-reuniao-gov').classList.add('hidden')" class="flex-1 border border-gray-300 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('form-reuniao-gov').addEventListener('submit', async function(e) {
    e.preventDefault();
    const res = await fetch('<?= APP_URL ?>/governanca/reuniao', { method:'POST', body: new FormData(this) });
    const data = await res.json();
    if (data.sucesso) { document.getElementById('modal-reuniao-gov').classList.add('hidden'); alert(data.mensagem); location.reload(); }
});

async function registrarAuditoria() {
    if (!confirm('Registrar nova auditoria de compliance?')) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    const res = await fetch('<?= APP_URL ?>/governanca/auditoria', { method:'POST', body:fd });
    const data = await res.json();
    if (data.sucesso) alert(data.mensagem);
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
