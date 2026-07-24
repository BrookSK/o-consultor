<?php
$c = $dados['concorrente'];
$analise = $dados['analise'];
$posts = $dados['posts'];
$coletas = $dados['coletas'];
$resumo = $dados['resumo'];
$tituloPagina = 'Concorrente: ' . $c['nome'];
$d = $analise['dados'] ?? [];

/** Helper local: renderiza uma lista de itens (array de strings) como chips/bullets. */
function chips(array $itens): string {
    if (empty($itens)) return '<span class="text-sm text-gray-400">—</span>';
    return implode('', array_map(fn($i) => '<span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 mb-1">' . htmlspecialchars((string) $i) . '</span>', $itens));
}
function metricaOuNd($v): string {
    return $v === null || $v === '' ? '<span class="text-gray-400">não disponível</span>' : htmlspecialchars((string) $v);
}
?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/central-de-conteudo" class="hover:text-primary">Central de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($c['nome']) ?></li>
    </ol>
</nav>

<!-- Resumo do perfil -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($c['nome']) ?> <?= $c['principal'] ? '⭐' : '' ?></h1>
            <p class="text-sm text-gray-500 mt-1">
                <?= htmlspecialchars(ucfirst($c['plataforma'])) ?>
                <?= $c['nome_perfil'] ? ' • ' . htmlspecialchars($c['nome_perfil']) : '' ?>
                • <a href="<?= htmlspecialchars($c['url_publica']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">Abrir perfil 🔗</a>
            </p>
        </div>
        <form method="POST" action="<?= APP_URL ?>/central-de-conteudo/concorrente-coletar" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Coletando...';">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Analisar agora</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <div class="text-center"><p class="text-2xl font-bold text-gray-800"><?= (int) $resumo['posts'] ?></p><p class="text-xs text-gray-400">posts coletados</p></div>
        <div class="text-center"><p class="text-2xl font-bold text-gray-800"><?= $resumo['engajamento_medio'] ?? '—' ?></p><p class="text-xs text-gray-400">engajamento médio</p></div>
        <div class="text-center"><p class="text-2xl font-bold text-gray-800"><?= $c['seguidores'] ? number_format((int) $c['seguidores'], 0, ',', '.') : '—' ?></p><p class="text-xs text-gray-400">seguidores</p></div>
        <div class="text-center"><p class="text-sm font-medium text-gray-600 mt-2"><?= $c['ultima_coleta_em'] ? date('d/m/Y H:i', strtotime($c['ultima_coleta_em'])) : 'Nunca' ?></p><p class="text-xs text-gray-400">última coleta</p></div>
    </div>
</div>

<?php if ($analise): ?>
<!-- Análise da IA -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-2">Análise da IA</h2>
    <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($analise['resumo'] ?? '') ?></p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Temas recorrentes</h3>
            <div><?= chips($d['temas_recorrentes'] ?? []) ?></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Temas que performaram</h3>
            <div><?= chips($d['temas_melhor_desempenho'] ?? []) ?></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Formatos que performaram</h3>
            <div><?= chips($d['formatos_melhor_desempenho'] ?? []) ?></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Ganchos</h3>
            <div><?= chips($d['ganchos'] ?? []) ?></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">CTAs</h3>
            <div><?= chips($d['ctas'] ?? []) ?></div>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Padrões de linguagem</h3>
            <div><?= chips($d['padroes_linguagem'] ?? []) ?></div>
        </div>
    </div>

    <?php if (!empty($analise['oportunidades'])): ?>
    <div class="mt-6 bg-primary/5 border border-primary/20 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary mb-2">💡 Oportunidades para o cliente</h3>
        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
            <?php foreach ($analise['oportunidades'] as $op): ?>
            <li><?= htmlspecialchars((string) $op) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm mb-6">
    Ainda não há análise. Clique em "Analisar agora" para coletar e analisar os dados públicos deste concorrente.
</div>
<?php endif; ?>

<!-- Conteúdos coletados -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Conteúdos coletados</h2>
    <?php if (empty($posts)): ?>
    <p class="text-sm text-gray-400">Nenhum conteúdo coletado ainda.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-200">
                    <th class="py-2 pr-4">Publicação</th>
                    <th class="py-2 pr-4">Formato</th>
                    <th class="py-2 pr-4">Data</th>
                    <th class="py-2 pr-4 text-right">Curtidas</th>
                    <th class="py-2 pr-4 text-right">Comentários</th>
                    <th class="py-2 pr-4 text-right">Visualizações</th>
                    <th class="py-2 pr-4 text-right">Engajamento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-2 pr-4 max-w-xs truncate">
                        <?php if (!empty($p['url'])): ?>
                        <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline"><?= htmlspecialchars(mb_substr((string) ($p['titulo'] ?: 'Publicação'), 0, 60)) ?></a>
                        <?php else: ?>
                        <?= htmlspecialchars(mb_substr((string) ($p['titulo'] ?: 'Publicação'), 0, 60)) ?>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 pr-4"><?= htmlspecialchars((string) ($p['tipo_conteudo'] ?? '—')) ?></td>
                    <td class="py-2 pr-4"><?= $p['data_publicacao'] ? date('d/m/Y', strtotime($p['data_publicacao'])) : '—' ?></td>
                    <td class="py-2 pr-4 text-right"><?= metricaOuNd($p['curtidas']) ?></td>
                    <td class="py-2 pr-4 text-right"><?= metricaOuNd($p['comentarios']) ?></td>
                    <td class="py-2 pr-4 text-right"><?= metricaOuNd($p['visualizacoes']) ?></td>
                    <td class="py-2 pr-4 text-right font-medium"><?= metricaOuNd($p['engajamento_absoluto']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Histórico de coletas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Histórico de coletas</h2>
    <?php if (empty($coletas)): ?>
    <p class="text-sm text-gray-400">Nenhuma coleta realizada ainda.</p>
    <?php else: ?>
    <ul class="divide-y divide-gray-100 text-sm">
        <?php foreach ($coletas as $col): ?>
        <li class="py-2 flex items-center justify-between gap-4">
            <span class="text-gray-600"><?= date('d/m/Y H:i', strtotime($col['criado_em'])) ?> • <?= htmlspecialchars($col['origem']) ?></span>
            <span class="flex items-center gap-3">
                <span class="text-gray-500"><?= (int) $col['posts_coletados'] ?> posts</span>
                <?php
                $stCfg = match($col['status']) {
                    'concluida' => ['bg-green-100 text-green-700', 'Concluída'],
                    'parcial'   => ['bg-yellow-100 text-yellow-700', 'Parcial'],
                    'erro'      => ['bg-red-100 text-red-700', 'Erro'],
                    'processando' => ['bg-blue-100 text-blue-700', 'Processando'],
                    default     => ['bg-gray-100 text-gray-600', ucfirst($col['status'])],
                };
                ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $stCfg[0] ?>" title="<?= htmlspecialchars((string) ($col['mensagem'] ?? '')) ?>"><?= $stCfg[1] ?></span>
            </span>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
