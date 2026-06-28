<?php $tituloPagina = 'Meu Painel'; ?>
<?php ob_start(); ?>

<!-- Loading Spinner -->
<div id="dashboard-loading" class="flex items-center justify-center py-20">
    <div class="text-center">
        <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
        <p class="text-sm text-gray-500 mt-3">Carregando painel...</p>
    </div>
</div>

<!-- Dashboard Content -->
<div id="dashboard-content" class="hidden">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($dados['saudacao']) ?>, <?= htmlspecialchars($dados['usuario']['nome']) ?>!</h1>
    <p class="text-gray-500 mt-1"><?= htmlspecialchars(ucfirst($dados['data_atual'])) ?></p>
</div>

<!-- Widget Jornada Integrada -->
<?php 
require_once APP_PATH . '/Helpers/JornadaCliente.php';
echo JornadaCliente::renderWidgetNavegacao($dados['usuario']['empresa_id']);
?>

<?php if (!$dados['onboarding_concluido']): ?>
<!-- Onboarding CTA -->
<div class="bg-gradient-to-r from-accent to-orange-700 rounded-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold">🚀 Complete sua jornada!</h3>
            <p class="text-white/90 text-sm mt-1">Finalize o onboarding para acessar todos os recursos</p>
        </div>
        <a href="<?= APP_URL ?>/onboarding" class="bg-white/20 hover:bg-white/30 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition">
            Continuar →
        </a>
    </div>
</div>
<?php elseif ($dados['percentual_conclusao'] < 100): ?>
<!-- Progress Bar -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-gray-800">🎯 Complete sua jornada</h3>
        <span class="text-sm font-medium text-gray-600"><?= $dados['percentual_conclusao'] ?>% concluído</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
        <div class="bg-green-500 h-2.5 rounded-full transition-all duration-700" style="width: <?= $dados['percentual_conclusao'] ?>%"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <?php foreach ($dados['jornada'] as $etapa): ?>
        <div class="flex items-center gap-2">
            <div class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold
                <?= $etapa['completo'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' ?>">
                <?= $etapa['completo'] ? '✓' : '○' ?>
            </div>
            <span class="text-sm <?= $etapa['completo'] ? 'text-green-600 font-medium' : 'text-gray-500' ?>">
                <?= htmlspecialchars($etapa['label']) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php 
    $proximaEtapa = null;
    foreach ($dados['jornada'] as $etapa) {
        if (!$etapa['completo']) {
            $proximaEtapa = $etapa;
            break;
        }
    }
    if ($proximaEtapa): ?>
    <div class="mt-4 pt-4 border-t border-gray-100">
        <p class="text-sm text-gray-600">
            <span class="font-medium">Próximo passo:</span> 
            <?= htmlspecialchars($proximaEtapa['label']) ?>
            <?php if ($proximaEtapa['chave'] === 'diagnostico'): ?>
                <a href="<?= APP_URL ?>/diagnostico/novo" class="text-primary hover:underline ml-2">Fazer agora →</a>
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
<?php foreach ($dados['kpis'] as $kpi): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                <?= match($kpi['cor']) {
                    'blue' => 'bg-blue-100 text-blue-600',
                    'green' => 'bg-green-100 text-green-600',
                    'purple' => 'bg-purple-100 text-purple-600',
                    'orange' => 'bg-orange-100 text-orange-600',
                    default => 'bg-gray-100 text-gray-600',
                } ?>">
                <?php echo match($kpi['icone']) {
                    'chart' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                    'book' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                    'target' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'calendar' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                }; ?>
            </div>
            <!-- Variação com seta -->
            <span class="text-xs font-semibold flex items-center gap-0.5
                <?= $kpi['direcao'] === 'up' ? 'text-green-600' : ($kpi['direcao'] === 'neutral' ? 'text-gray-500' : 'text-red-600') ?>">
                <?php if ($kpi['direcao'] === 'up'): ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/></svg>
                <?php elseif ($kpi['direcao'] === 'down'): ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"/></svg>
                <?php endif; ?>
                <?= htmlspecialchars($kpi['variacao']) ?>
            </span>
        </div>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($kpi['titulo']) ?></p>
        <p class="text-2xl font-bold text-gray-800 mt-1"><?= htmlspecialchars($kpi['valor']) ?></p>
    </div>
<?php endforeach; ?>
</div>

<!-- Meu Plano de Ação -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">📋 Meu Plano de Ação</h3>
        <a href="<?= APP_URL ?>/plano-de-acao" class="text-sm text-primary hover:underline">Ver completo →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2.5 font-medium text-gray-500">Tarefa</th>
                    <th class="text-left px-4 py-2.5 font-medium text-gray-500">Responsável</th>
                    <th class="text-left px-4 py-2.5 font-medium text-gray-500">Prazo</th>
                    <th class="text-left px-4 py-2.5 font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['plano_acao'] as $tarefa): 
                    $statusBadge = match($tarefa['status']) {
                        'concluido' => 'bg-green-100 text-green-700',
                        'em_andamento' => 'bg-blue-100 text-blue-700',
                        default => 'bg-gray-100 text-gray-600',
                    };
                    $statusLabel = match($tarefa['status']) {
                        'concluido' => '✓ Concluído',
                        'em_andamento' => '◎ Em andamento',
                        default => '○ Pendente',
                    };
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($tarefa['titulo']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($tarefa['responsavel']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= date('d/m/Y', strtotime($tarefa['prazo'])) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                            <?= $statusLabel ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Meus Alertas: KPIs fora da meta -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">🚨 Meus Alertas — KPIs fora da Meta</h3>
    </div>
    <div class="p-4 space-y-3">
        <?php foreach ($dados['alertas_kpi'] as $alerta): ?>
        <div class="border-l-4 border-l-red-400 bg-red-50/50 rounded-r-lg p-4">
            <div class="flex items-start justify-between mb-2">
                <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($alerta['kpi']) ?></p>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-500">Meta: <strong><?= htmlspecialchars($alerta['meta']) ?></strong></span>
                    <span class="text-red-600">Atual: <strong><?= htmlspecialchars($alerta['atual']) ?></strong></span>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-gray-600"><strong>Ação sugerida:</strong> <?= htmlspecialchars($alerta['acao_sugerida']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Notícias Relevantes -->
<?php if (!empty($dados['noticias_relevantes'])): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">📰 Notícias do Seu Setor</h3>
            <a href="<?= APP_URL ?>/noticias" class="text-sm text-primary hover:underline">Ver todas →</a>
        </div>
    </div>
    <div class="p-4 space-y-3">
        <?php foreach ($dados['noticias_relevantes'] as $noticia): 
            $relevanciaColor = match($noticia['relevancia_label']) {
                'Alta' => 'bg-red-100 text-red-700',
                'Média' => 'bg-yellow-100 text-yellow-700', 
                default => 'bg-gray-100 text-gray-700'
            };
        ?>
        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                    <h4 class="font-medium text-gray-800 text-sm mb-1">
                        <a href="<?= APP_URL ?>/noticias/detalhe/<?= $noticia['id'] ?>" class="hover:text-primary transition">
                            <?= htmlspecialchars($noticia['titulo']) ?>
                        </a>
                    </h4>
                    <p class="text-xs text-gray-600 mb-2 line-clamp-2"><?= htmlspecialchars($noticia['resumo_ia'] ?? substr($noticia['conteudo'], 0, 120) . '...') ?></p>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $relevanciaColor ?>">
                            <?= $noticia['relevancia_label'] ?>
                        </span>
                        <span class="text-xs text-gray-500"><?= $noticia['data_formatada'] ?></span>
                        <?php if (!empty($noticia['fonte'])): ?>
                            <span class="text-xs text-gray-400">• <?= htmlspecialchars($noticia['fonte']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($noticia['imagem_url'])): ?>
                <img src="<?= htmlspecialchars($noticia['imagem_url']) ?>" alt="" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-500 text-center">
                💡 Notícias personalizadas com base no seu diagnóstico e perfil da empresa
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Conteúdo Recomendado (3 cards por setor) -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800 text-lg">📚 Conteúdo Recomendado para Você</h3>
        <a href="<?= APP_URL ?>/central-de-conteudo" class="text-sm text-primary hover:underline">Ver todos →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($dados['conteudo_recomendado'] as $cont): 
            $icone = match($cont['tipo']) {
                'artigo' => '📄',
                'template' => '📋',
                'video' => '🎬',
                default => '📁',
            };
            $tipoBadge = match($cont['tipo']) {
                'artigo' => 'bg-blue-100 text-blue-700',
                'template' => 'bg-purple-100 text-purple-700',
                'video' => 'bg-red-100 text-red-700',
                default => 'bg-gray-100 text-gray-700',
            };
        ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition cursor-pointer">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xl"><?= $icone ?></span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $tipoBadge ?>"><?= ucfirst($cont['tipo']) ?></span>
            </div>
            <h4 class="text-sm font-semibold text-gray-800 mb-2"><?= htmlspecialchars($cont['titulo']) ?></h4>
            <?php if (!empty($cont['duracao'])): ?>
            <p class="text-xs text-gray-400">⏱ <?= htmlspecialchars($cont['duracao']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Banner Academy -->
<div class="bg-gradient-to-r from-primary to-[#2a4f7f] rounded-lg p-6 md:p-8 text-white relative overflow-hidden mb-4">
    <!-- Decoração -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10"></div>
    <div class="absolute bottom-0 right-20 w-20 h-20 bg-white/5 rounded-full -mb-8"></div>
    
    <div class="relative flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold">🎓 Academy — O Consultor</h3>
            <p class="text-white/80 mt-2 text-sm max-w-md">
                Acesse cursos exclusivos, trilhas de aprendizado e certificações para acelerar a maturidade da sua empresa.
            </p>
        </div>
        <a href="<?= APP_URL ?>/academy/sso" 
           class="flex-shrink-0 bg-accent hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-semibold text-sm transition shadow-lg">
            🚀 Acessar meus cursos
        </a>
    </div>
</div>

</div><!-- /dashboard-content -->

<script>
// Simular loading
setTimeout(() => {
    document.getElementById('dashboard-loading').classList.add('hidden');
    document.getElementById('dashboard-content').classList.remove('hidden');
}, 500);
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
