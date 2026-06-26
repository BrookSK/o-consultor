<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página não encontrada | O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center">
            <span class="text-4xl">🔍</span>
        </div>
        <h1 class="text-5xl font-bold text-primary mb-3">404</h1>
        <h2 class="text-xl font-semibold text-gray-800 mb-3">Página não encontrada</h2>
        <p class="text-gray-500 mb-8 text-sm leading-relaxed">A página que você procura não existe ou foi movida. Verifique o endereço ou volte ao painel.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= APP_URL ?? '' ?>/dashboard" class="bg-primary text-white px-6 py-3 rounded-lg font-medium text-sm hover:opacity-90 transition">
                ← Voltar ao Dashboard
            </a>
            <a href="<?= APP_URL ?? '' ?>/login" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-lg font-medium text-sm hover:bg-gray-50 transition">
                Ir para Login
            </a>
        </div>
    </div>
</body>
</html>
