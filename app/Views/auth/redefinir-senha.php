<?php
// Verificar se dados foram passados
if (!isset($dados)) {
    header('Location: ' . APP_URL . '/recuperar-senha');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        accent: '#f97316'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-primary">O Consultor</h1>
            <p class="text-gray-600 mt-2">Redefinir Senha</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Mensagens -->
            <?php if (Flash::has('erro')): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= Flash::get('erro') ?>
                </div>
            <?php endif; ?>

            <!-- Saudação -->
            <div class="text-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Olá, <?= htmlspecialchars($dados['nome']) ?>!</h2>
                <p class="text-gray-600 text-sm mt-1">Defina sua nova senha abaixo</p>
            </div>

            <!-- Formulário -->
            <form method="POST" action="<?= APP_URL ?>/redefinir-senha">
                <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($dados['token']) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($dados['email']) ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                        <input type="password" name="senha" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                        <input type="password" name="confirmar_senha" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                               placeholder="Digite a senha novamente">
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full mt-6 bg-primary text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    🔒 Redefinir Senha
                </button>
            </form>

            <!-- Link de volta -->
            <div class="text-center mt-6">
                <a href="<?= APP_URL ?>/login" class="text-sm text-gray-600 hover:text-primary">
                    ← Voltar para Login
                </a>
            </div>
        </div>

        <!-- Aviso de segurança -->
        <div class="mt-4 text-center text-xs text-gray-500">
            <p>Por segurança, este link expira em 2 horas</p>
        </div>
    </div>

    <script>
        // Validar se senhas coincidem
        document.querySelector('form').addEventListener('submit', function(e) {
            const senha = document.querySelector('input[name="senha"]').value;
            const confirmar = document.querySelector('input[name="confirmar_senha"]').value;
            
            if (senha !== confirmar) {
                e.preventDefault();
                alert('As senhas não coincidem.');
            }
        });
    </script>
</body>
</html>