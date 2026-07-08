<?php $tituloPagina = 'Resultado do Diagnóstico'; ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Resultado</li>
    </ol>
</nav>

<?php
    $resultado = $dados['resultado'];
    $nivel = $resultado['nivel'];
?>

<!-- Header com Score -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <!-- Score Circle -->
        <div class="flex-shrink-0">
            <div class="w-28 h-28 rounded-full border-8 flex items-center justify-center"
                 style="border-color: <?= $nivel['cor'] ?>">
                <div class="text-center">
                    <p class="text-3xl font-bold" style="color: <?= $nivel['cor'] ?>"><?= $resultado['score'] ?></p>
                    <p class="text-xs text-gray-500">de 4</p>
                </div>
            </div>
        </div>
        <!-- Info -->
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($resultado['empresa']) ?></h1>
            <div class="flex items-center justify-center md:justify-start gap-2 mt-2">
                <span class="px-3 py-1 rounded-full text-sm font-semibold text-white" style="background-color: <?= $nivel['cor'] ?>">
                    Nível <?= $resultado['score'] ?> — <?= htmlspecialchars($nivel['label']) ?>
                </span>
            </div>
            <p class="text-gray-600 mt-3 text-sm max-w-xl"><?= htmlspecialchars($nivel['descricao']) ?></p>
        </div>
        <!-- Indicadores visuais -->
        <div class="flex-shrink-0 flex gap-2">
            <?php for ($i = 1; $i <= 4; $i++): 
                $nivelCores = [1 => '#CC2222', 2 => '#f59e0b', 3 => '#1a7a1a', 4 => '#1E3A5F'];
            ?>
            <div class="w-4 h-16 rounded-full <?= $i <= $resultado['score'] ? '' : 'opacity-20' ?>"
                 style="background-color: <?= $nivelCores[$i] ?>"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Resumo por Área -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Resumo por Área</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($resultado['areas'] as $area):
            $statusConfig = match($area['status']) {
                'adequado' => ['bg' => 'bg-green-50 border-green-200', 'badge' => 'bg-green-100 text-green-700', 'icon' => '✓'],
                'atenção' => ['bg' => 'bg-yellow-50 border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-700', 'icon' => '⚠'],
                'crítico' => ['bg' => 'bg-red-50 border-red-200', 'badge' => 'bg-red-100 text-red-700', 'icon' => '✗'],
                default => ['bg' => 'bg-gray-50 border-gray-200', 'badge' => 'bg-gray-100 text-gray-700', 'icon' => '—'],
            };
        ?>
        <div class="rounded-lg border p-4 <?= $statusConfig['bg'] ?>">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($area['area']) ?></h4>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $statusConfig['badge'] ?>">
                    <?= $statusConfig['icon'] ?> <?= ucfirst($area['status']) ?>
                </span>
            </div>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($area['comentario']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Mapa de Riscos -->
<?php if (!empty($resultado['riscos'])): ?>
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">🚨 Mapa de Riscos</h2>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Tipo</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Descrição</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Criticidade</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Ação Sugerida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($resultado['riscos'] as $risco):
                        $critBadge = match($risco['criticidade']) {
                            'alta' => 'bg-red-100 text-red-700',
                            'media' => 'bg-yellow-100 text-yellow-700',
                            default => 'bg-blue-100 text-blue-700',
                        };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($risco['tipo']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($risco['descricao']) ?></td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $critBadge ?>">
                                <?= ucfirst($risco['criticidade']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($risco['acao']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Confirmação + Ações -->
<div class="bg-gradient-to-r from-primary/5 to-accent/5 rounded-lg border border-primary/20 p-6 mb-6">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Dados Registrados com Sucesso</h3>
            <p class="text-sm text-gray-600 mt-1">
                O sistema usará estas informações para gerar seu <strong>Plano de Ação</strong> e <strong>SOPs personalizados</strong> com base no diagnóstico da empresa.
            </p>
        </div>
    </div>
</div>

<!-- Botões de ação -->
<div class="flex flex-col sm:flex-row gap-4">
    <?php 
    $diagnosticoId = $resultado['diagnostico_id'] ?? null;
    $sopUrl = APP_URL . '/manual-operacional' . ($diagnosticoId ? '?diagnostico_id=' . $diagnosticoId : '');
    ?>
    <button type="button" onclick="gerarPlanoAcao(<?= (int) $diagnosticoId ?>)"
       class="flex-1 bg-primary text-white px-6 py-4 rounded-lg font-medium text-sm hover:bg-primary-700 transition text-center flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>
            <span class="block font-semibold">Gerar Plano de Ação</span>
            <span class="block text-xs text-white/70">Ações prioritárias baseadas no diagnóstico</span>
        </span>
    </button>

    <button onclick="gerarManualCompleto(<?= $resultado['diagnostico_id'] ?>)" 
            class="flex-1 bg-purple-600 text-white px-6 py-4 rounded-lg font-medium text-sm hover:bg-purple-700 transition text-center flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        <span>
            <span class="block font-semibold">🧠 Manual Completo (Nova Arquitetura)</span>
            <span class="block text-xs text-white/70">50-70 SOPs profissionais com estrutura N1/N2/N3</span>
        </span>
    </button>
    
    <a href="<?= APP_URL ?>/sop/listar-por-diagnostico?diagnostico_id=<?= $resultado['diagnostico_id'] ?>" 
       class="flex-1 bg-green-600 text-white px-6 py-4 rounded-lg font-medium text-sm hover:bg-green-700 transition text-center flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>
            <span class="block font-semibold">📋 Ver SOPs Gerados</span>
            <span class="block text-xs text-white/70">Visualizar todos os SOPs criados para este diagnóstico</span>
        </span>
    </a>
</div>

<!-- Editar / voltar -->
<div class="mt-6 flex items-center justify-center gap-4">
    <?php if (!empty($resultado['diagnostico_id'])): ?>
    <a href="<?= APP_URL ?>/diagnostico/editar?diagnostico_id=<?= (int) $resultado['diagnostico_id'] ?>"
       class="inline-flex items-center gap-2 px-5 py-2.5 border border-primary text-primary rounded-lg text-sm font-medium hover:bg-primary hover:text-white transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        Editar / Refazer diagnóstico
    </a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/diagnostico" class="text-sm text-gray-500 hover:text-primary">← Voltar para lista de diagnósticos</a>
</div>

<!-- NOVA ARQUITETURA: Modal de Loading -->
<div id="modal-loading-manual" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-xl">
        <div class="text-center">
            <div class="inline-block w-16 h-16 border-4 border-gray-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2" id="modal-titulo-manual">Iniciando Nova Arquitetura...</h3>
            <p class="text-sm text-gray-500" id="modal-subtitulo-manual">Analisando estrutura organizacional...</p>
        </div>
    </div>
</div>

<script>
// Gerar/Regerar Plano de Ação — verifica se já existe e pergunta como o Manual Completo.
async function gerarPlanoAcao(diagnosticoId) {
    try {
        const chk = await fetch('<?= APP_URL ?>/plano-de-acao/existe?diagnostico_id=' + diagnosticoId);
        const info = await chk.json();

        if (info.sucesso && info.existe) {
            const regerar = confirm(
                'Já existe um Plano de Ação para este diagnóstico.\n\n' +
                'OK = Regerar do zero (o plano atual, tarefas e métricas serão substituídos)\n' +
                'Cancelar = Abrir o plano atual'
            );
            if (regerar) {
                window.location.href = info.regerar;
            } else {
                window.location.href = info.redirect_ver;
            }
            return;
        }
    } catch (e) {
        // Se a verificação falhar, segue para a geração padrão.
    }
    // Não existe: confirmar a criação
    if (confirm('Deseja gerar o Plano de Ação com base neste diagnóstico?')) {
        window.location.href = '<?= APP_URL ?>/plano-de-acao/gerar-automatico?diagnostico_id=' + diagnosticoId;
    }
}

// NOVA ARQUITETURA: Gerar Manual Completo
async function gerarManualCompleto(diagnosticoId) {
    // 1. Verificar se já existe estrutura para este diagnóstico
    try {
        const chk = await fetch('<?= APP_URL ?>/sop/estrutura-existe?diagnostico_id=' + diagnosticoId);
        const info = await chk.json();

        if (info.sucesso && info.existe) {
            // Já existe: perguntar se quer recriar ou apenas revisar a seleção
            const recriar = confirm(
                'Já existe uma estrutura de serviços para este diagnóstico.\n\n' +
                'OK = Recriar do zero (a estrutura atual e as seleções serão substituídas)\n' +
                'Cancelar = Apenas revisar/selecionar os serviços atuais'
            );
            if (!recriar) {
                // Ir direto para a tela de seleção (draft) sem recriar
                window.location.href = info.redirect_selecao;
                return;
            }
            // Se optou por recriar, segue para regenerar abaixo
        } else {
            // Não existe ainda: confirmar a criação
            if (!confirm('Deseja gerar o Manual Operacional Completo?\n\nSerá montada a estrutura de setores e serviços e, em seguida, você escolhe quais serviços realmente comporão seus SOPs.')) {
                return;
            }
        }
    } catch (e) {
        // Se a verificação falhar, seguimos com o fluxo padrão de geração
    }

    // 2. Gerar/recriar a estrutura e ir para a tela de seleção (draft)
    document.getElementById('modal-loading-manual').classList.remove('hidden');
    document.getElementById('modal-titulo-manual').textContent = 'Montando estrutura...';
    document.getElementById('modal-subtitulo-manual').textContent = 'Preparando setores e serviços...';

    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('diagnostico_id', diagnosticoId);

        const response = await fetch('<?= APP_URL ?>/sop/gerar-manual-completo', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.sucesso) {
            document.getElementById('modal-titulo-manual').textContent = 'Estrutura criada!';
            document.getElementById('modal-subtitulo-manual').textContent = 'Agora escolha os serviços do seu Manual...';
            setTimeout(() => { window.location.href = result.redirect; }, 1200);
        } else {
            document.getElementById('modal-loading-manual').classList.add('hidden');
            alert('Erro ao iniciar geração do manual: ' + (result.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro na geração do manual:', error);
        document.getElementById('modal-loading-manual').classList.add('hidden');
        alert('Erro de conexão. Tente novamente.');
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
