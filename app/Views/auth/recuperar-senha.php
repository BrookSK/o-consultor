<?php $tituloPagina = 'Recuperar Senha'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#1E3A5F', 600: '#1E3A5F', 700: '#162D4A' },
                        accent: '#E07B00',
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary tracking-wide">O CONSULTOR</h1>
            <p class="text-gray-500 mt-2 text-sm">Sistema Operacional Empresarial</p>
        </div>

        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Recuperar senha</h2>
            <p class="text-sm text-gray-500 mb-6">Informe seu email e enviaremos as instruções de recuperação.</p>

            <?= Flash::renderizar() ?>

            <form action="<?= APP_URL ?>/recuperar-senha" method="POST">
                <?= Csrf::campo() ?>

                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                           placeholder="seu@email.com.br"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition">
                </div>

                <button type="submit"
                        class="w-full bg-primary text-white py-2.5 px-4 rounded-lg font-medium text-sm hover:bg-primary-700 transition focus:ring-2 focus:ring-primary/30">
                    Enviar instruções
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                <a href="<?= APP_URL ?>/login" class="text-primary font-medium hover:underline">← Voltar ao login</a>
            </p>
        </div>
    </div>

</body>
</html>
