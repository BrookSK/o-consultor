<?php
$empresa = $dados['empresa'] ?? [];
$respostas = $dados['respostas'] ?? [];
$diagnostico = $dados['diagnostico'] ?? [];
$diagId = (int) ($diagnostico['id'] ?? 0);

$val = function ($campo, $default = '') use ($respostas) {
    return htmlspecialchars((string) ($respostas[$campo] ?? $default));
};
$sel = function ($campo, $valor) use ($respostas) {
    return (($respostas[$campo] ?? '') == $valor) ? 'selected' : '';
};
$deps = (array) ($respostas['departamentos'] ?? []);
$tituloPagina = 'Editar diagnóstico';

// Opções reutilizáveis (nível de maturidade por área).
$opcoesNivel = [
    '' => 'Selecione...',
    'inexistente' => 'Inexistente',
    'basico' => 'Básico / informal',
    'parcial' => 'Parcial / em desenvolvimento',
    'estruturado' => 'Estruturado',
    'otimizado' => 'Otimizado / maduro',
];
$departamentosLista = ['Comercial','Operações','Financeiro','Pessoas/RH','TI','Marketing','Logística','Atendimento'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F',700:'#162D4A'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-4xl mx-auto p-6">
    <nav class="mb-4 text-sm text-gray-500">
        <a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a> /
        <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $diagId ?>" class="hover:text-primary">Resultado</a> /
        <span class="text-primary font-medium">Editar</span>
    </nav>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-5">
        <h1 class="text-2xl font-bold text-gray-800">Editar diagnóstico</h1>
        <p class="text-gray-500 text-sm mt-1">Ajuste os dados da empresa e as respostas. Ao salvar, a pontuação e o resultado são recalculados (o diagnóstico é regenerado).</p>
    </div>

    <form id="formEditarDiagnostico" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
        <input type="hidden" name="diagnostico_id" value="<?= $diagId ?>">

        <!-- Dados da empresa -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Dados da empresa</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da empresa *</label>
                    <input type="text" name="empresa_nome" required value="<?= htmlspecialchars((string) ($empresa['nome'] ?? $respostas['empresa_nome'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Segmento</label>
                    <input type="text" name="segmento" value="<?= htmlspecialchars((string) ($empresa['segmento'] ?? $respostas['setor'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Faturamento mensal</label>
                    <input type="text" name="faturamento_mensal" value="<?= htmlspecialchars((string) ($empresa['faturamento_mensal'] ?? $respostas['faturamento_mensal'] ?? '')) ?>"
                           placeholder="Ex.: 50000" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nº de colaboradores</label>
                    <input type="number" name="colaboradores_internos" value="<?= htmlspecialchars((string) ($empresa['colaboradores_internos'] ?? $respostas['colaboradores_internos'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Principal desafio</label>
                    <textarea name="principal_desafio" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><?= htmlspecialchars((string) ($empresa['principal_desafio'] ?? $respostas['principal_desafio'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Departamentos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Departamentos existentes</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <?php foreach ($departamentosLista as $dep): ?>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="departamentos[]" value="<?= htmlspecialchars($dep) ?>" <?= in_array($dep, $deps) ? 'checked' : '' ?> class="w-4 h-4 rounded border-gray-300">
                    <?= htmlspecialchars($dep) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Maturidade por área -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Maturidade por área</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $camposNivel = [
                    'processos_documentados' => 'Processos documentados (%)',
                    'planejamento_documentado' => 'Planejamento documentado',
                    'maturidade_percebida' => 'Maturidade percebida (0-100)',
                    'sistema_financeiro' => 'Sistema financeiro',
                    'controle_fluxo_caixa' => 'Controle de fluxo de caixa',
                    'sistema_crm' => 'Sistema de CRM',
                    'estrutura_organizacional' => 'Estrutura organizacional',
                    'programa_capacitacao' => 'Programa de capacitação',
                    'mapeamento_riscos' => 'Mapeamento de riscos',
                    'backup_continuidade' => 'Backup e continuidade',
                    'conformidade_regulatoria' => 'Conformidade regulatória',
                ];
                foreach ($camposNivel as $campo => $label):
                    $isNumero = in_array($campo, ['processos_documentados', 'maturidade_percebida']);
                ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?= $label ?></label>
                    <?php if ($isNumero): ?>
                        <input type="number" min="0" max="100" name="<?= $campo ?>" value="<?= $val($campo) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <?php else: ?>
                        <select name="<?= $campo ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <?php foreach ($opcoesNivel as $ov => $ol): ?>
                            <option value="<?= $ov ?>" <?= $sel($campo, $ov) ?>><?= $ol ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Riscos / dependências -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Riscos e dependências</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $camposSimNao = [
                    'dependencia_pessoas' => 'Depende de pessoas-chave?',
                    'dependencia_fornecedores' => 'Depende de fornecedores críticos?',
                    'processos_sem_backup' => 'Há processos sem backup de conhecimento?',
                    'fornecedor_insubstituivel' => 'Tem fornecedor insubstituível?',
                    'cliente_concentrado' => 'Faturamento concentrado em poucos clientes?',
                ];
                foreach ($camposSimNao as $campo => $label):
                ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?= $label ?></label>
                    <select name="<?= $campo ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="nao" <?= $sel($campo, 'nao') ?>>Não</option>
                        <option value="sim" <?= $sel($campo, 'sim') ?>>Sim</option>
                    </select>
                </div>
                <?php endforeach; ?>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo para os próximos 12 meses</label>
                    <textarea name="objetivo_12_meses" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><?= $val('objetivo_12_meses') ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $diagId ?>" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">← Cancelar</a>
            <button type="submit" id="btnSalvarDiag" class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 font-semibold">Salvar e regenerar diagnóstico</button>
        </div>
    </form>
</div>

<script>
document.getElementById('formEditarDiagnostico').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarDiag');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const res = await fetch('<?= APP_URL ?>/diagnostico/atualizar', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) {
            window.location.href = data.redirect || ('<?= APP_URL ?>/diagnostico/resultado/<?= $diagId ?>');
        } else {
            alert(data.erro || 'Erro ao salvar.');
            btn.disabled = false; btn.textContent = 'Salvar e regenerar diagnóstico';
        }
    } catch (err) {
        alert('Erro de conexão. Tente novamente.');
        btn.disabled = false; btn.textContent = 'Salvar e regenerar diagnóstico';
    }
});
</script>

</body>
</html>
