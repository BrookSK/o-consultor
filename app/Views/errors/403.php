<?php $tituloPagina = 'Acesso Negado'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acesso Negado — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E3A5F',
                        accent: '#E07B00'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto text-center">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-4">403</h1>
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Acesso não autorizado</h2>
            
            <p class="text-gray-600 mb-6">
                Você não tem permissão para acessar esta página. 
                Verifique se você está logado com o perfil correto.
            </p>
            
            <div class="space-y-3">
                <a href="<?= APP_URL ?>/dashboard" 
                   class="block bg-primary text-white py-2 px-6 rounded-lg hover:bg-primary-700 transition-colors">
                    Voltar ao meu painel
                </a>
                
                <a href="<?= APP_URL ?>/logout" 
                   class="block text-accent hover:text-orange-700 text-sm">
                    Fazer login com outro usuário
                </a>
            </div>
        </div>
        
        <p class="text-xs text-gray-500 mt-4">
            Este acesso foi registrado por questões de segurança.
        </p>
    </div>
</body>
</html>