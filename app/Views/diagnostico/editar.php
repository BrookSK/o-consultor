<?php
$empresa = $dados['empresa'] ?? [];
$respostas = $dados['respostas'] ?? [];
$diagnostico = $dados['diagnostico'] ?? [];
$diagId = (int) ($diagnostico['id'] ?? 0);
// Helper de seleção
$sel = function ($campo, $valor) use ($respostas) {
    return (($respostas[$campo] ?? '') === $valor) ? 'selected' : '';
};
$val = function ($campo, $default = '') use ($respostas) {
    return htmlspecialchars((string) ($respostas[$campo] ?? $default));
};
$deps = (array) ($respostas['departamentos'] ?? []);
$tituloPagina = 'Editar diagnóstico';
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
        <a href="<?