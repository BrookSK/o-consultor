<?php $tituloPagina = 'SOPs - ' . htmlspecialchars($dados['empresa']['nome']); ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" class="hover:text-primary">Resultado</a></li>
        <li>/</li>
        <li class="font-medium text-primary">SOPs Gerados</li>
    </ol>
</nav>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">SOPs - <?= htmlspecialchars($dados['empresa']['nome']) ?></h1>
            <p class="text-gray-600 mt-1">
                Procedimentos Operacionais Padrão organizados por setores
                <br><span class="text-sm text-gray-500">🎯 Clique em qualquer serviço para ver ou gerenciar seu SOP</span>
            </p>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Diagnóstico realizado em</div>
            <div class="font-semibold text-gray-800">
                <?= date('d/m/Y', strtotime($dados['diagnostico']['criado_em'])) ?>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-blue-600">Setores</div>
                    <div class="text-2xl font-bold text-blue-700"><?= $dados['estatisticas']['total_setores'] ?></div>
                </div>
                <div class="text-blue-400">🏢</div>
            </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-purple-600">Serviços</div>
                    <div class="text-2xl font-bold text-purple-700"><?= $dados['estatisticas']['total_servicos'] ?></div>
                </div>
                <div class="text-purple-400">⚙️</div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-green-600">SOPs Gerados</div>
                    <div class="text-2xl font-bold text-green-700"><?= $dados['estatisticas']['total_sops'] ?></div>
                </div>
                <div class="text-green-400">📋</div>
            </div>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-orange-600">Progresso</div>
                    <div class="text-2xl font-bold text-orange-700"><?= $dados['estatisticas']['percentual_conclusao'] ?>%</div>
                </div>
                <div class="text-orange-400">📊</div>
            </div>
        </div>
    </div>
</div>

<!-- FLUXO LINEAR: Setores > Serviços > SOPs -->
<div class="space-y-6">
    <?php if (!empty($dados['setores_organizados'])): ?>
        <?php foreach ($dados['setores_organizados'] as $setorData): ?>
        <?php $setor = $setorData['setor']; ?>
        <?php $servicos = $setorData['servicos']; ?>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Cabeçalho do Setor -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">
                            <?php 
                            switch($setor['tipo_setor'] ?? 'operacional') {
                                case 'core':
                                    echo '⚙️';
                                    break;
                                case 'apoio':
                                    echo '🛠️';
                                    break; 
                                case 'estrategico':
                                    echo '📋';
                                    break;
                                default:
                                    echo '📁';
                                    break;
                            }
                            ?>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span>
                                <span><?= $setor['total_servicos'] ?? 0 ?> serviços</span>
                                <span class="text-green-600"><?= $setor['total_sops'] ?? 0 ?> SOPs</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php 
                        $totalServicos = $setor['total_servicos'] ?? 0;
                        $totalSops = $setor['total_sops'] ?? 0;
                        ?>
                        <!-- Indicador de Status do Setor -->
                        <?php if ($totalSops == $totalServicos && $totalServicos > 0): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-medium">✓ Completo</span>
                        <?php elseif ($totalSops > 0): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full font-medium">⚠ Parcial (<?= $totalSops ?>/<?= $totalServicos ?>)</span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full font-medium">○ Pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Serviços do Setor -->
            <div class="p-6">
                <?php if (!empty($servicos)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($servicos as $servico): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer"
                         onclick="acessarServico(<?= $servico['id'] ?>, '<?= $servico['status_final'] ?>', <?= $servico['sop_id'] ?? 'null' ?>)">
                        
                        <!-- Cabeçalho do Serviço -->
                        <div class="flex items-center justify-between mb-3">
                            <code class="text-xs font-mono bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($servico['codigo_servico'] ?? 'N/A') ?></code>
                            
                            <?php
                            switch($servico['status_final'] ?? 'mapeado') {
                                case 'mapeado':
                                    $statusConfig = ['bg' => 'bg-gray-100 text-gray-600', 'icon' => '○', 'label' => 'Mapeado'];
                                    break;
                                case 'detalhado':
                                    $statusConfig = ['bg' => 'bg-blue-100 text-blue-700', 'icon' => '◐', 'label' => 'Detalhado'];
                                    break;
                                case 'sop_gerado':
                                    $statusConfig = ['bg' => 'bg-green-100 text-green-700', 'icon' => '●', 'label' => 'SOP Pronto'];
                                    break;
                                default:
                                    $statusConfig = ['bg' => 'bg-gray-100 text-gray-500', 'icon' => '?', 'label' => 'Indefinido'];
                                    break;
                            }
                            ?>
                            
                            <span class="px-2 py-1 text-xs font-medium rounded <?= $statusConfig['bg'] ?>">
                                <?= $statusConfig['icon'] ?> <?= $statusConfig['label'] ?>
                            </span>
                        </div>
                        
                        <!-- Nome e Categoria -->
                        <h3 class="font-medium text-gray-800 mb-2 leading-tight"><?= htmlspecialchars($servico['nome_servico'] ?? 'Serviço sem nome') ?></h3>
                        
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded"><?= ucfirst($servico['categoria'] ?? 'geral') ?></span>
                            
                            <?php if (($servico['status_final'] ?? '') === 'sop_gerado'): ?>
                            <span class="text-green-600 font-medium">👆 Ver SOP</span>
                            <?php elseif (($servico['status_final'] ?? '') === 'detalhado'): ?>
                            <span class="text-blue-600 font-medium">👆 Gerar SOP</span>
                            <?php else: ?>
                            <span class="text-gray-600 font-medium">👆 Detalhar</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Data de criação do SOP se existir -->
                        <?php if (!empty($servico['sop_criado_em'])): ?>
                        <div class="mt-2 text-xs text-gray-400">
                            SOP criado em <?= date('d/m/Y', strtotime($servico['sop_criado_em'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <p>Nenhum serviço mapeado para este setor ainda.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <!-- Se não existem setores/SOPs -->
    <div class="text-center py-12">
        <div class="text-6xl mb-4">📋</div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Nenhum SOP encontrado</h2>
        <p class="text-gray-600 mb-6">
            Para começar a usar SOPs, primeiro você precisa gerar a estrutura organizacional.
        </p>
        <a href="<?= APP_URL ?>/sop?diagnostico_id=<?= $dados['diagnostico']['id'] ?>" 
           class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 font-medium">
            🚀 Iniciar Geração de SOPs
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Função para acessar serviço de forma inteligente baseado no status
function acessarServico(servicoId, status, sopId) {
    console.log('Acessando serviço:', servicoId, status, sopId);
    
    if (status === 'sop_gerado' && sopId) {
        // Se tem SOP, ir direto para visualizar
        window.open('<?= APP_URL ?>/sop/ver-sop-individual?id=' + sopId, '_blank');
    } else {
        // Se não tem SOP, ir para detalhes do serviço
        window.open('<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId, '_blank');
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
