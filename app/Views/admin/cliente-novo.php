<?php $tituloPagina = 'Cadastrar Novo Cliente'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/admin/clientes" class="hover:text-primary">Clientes</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Novo Cliente</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Cadastrar Novo Cliente</h1>
        <p class="text-sm text-gray-500">Crie uma nova empresa cliente com usuário responsável</p>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/clientes/criar" class="space-y-8">
    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
    
    <!-- Dados da Empresa -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Dados da Empresa</h2>
                <p class="text-sm text-gray-500">Informações básicas da empresa cliente</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nome da Empresa *
                </label>
                <input type="text" name="nome_empresa" required 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="Ex: TechSolutions Ltda">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    CNPJ
                </label>
                <input type="text" name="cnpj" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="00.000.000/0000-00" maxlength="18">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Segmento/Setor
                </label>
                <select name="segmento" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione o segmento</option>
                    <option value="Tecnologia">Tecnologia</option>
                    <option value="E-commerce">E-commerce</option>
                    <option value="Varejo">Varejo</option>
                    <option value="Alimentação">Alimentação</option>
                    <option value="Saúde">Saúde</option>
                    <option value="Educação">Educação</option>
                    <option value="Construção Civil">Construção Civil</option>
                    <option value="Serviços">Serviços</option>
                    <option value="Indústria">Indústria</option>
                    <option value="Logística">Logística</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Telefone da Empresa
                </label>
                <input type="tel" name="telefone_empresa" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="(11) 9999-9999">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Website
                </label>
                <input type="url" name="website" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="https://exemplo.com.br">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    MRR (Receita Recorrente Mensal)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R$</span>
                    <input type="number" name="mrr" step="0.01" min="0"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                           placeholder="0,00">
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Endereço Completo
            </label>
            <textarea name="endereco" rows="3"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"
                      placeholder="Rua, número, complemento, bairro"></textarea>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Cidade
                </label>
                <input type="text" name="cidade" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="São Paulo">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Estado
                </label>
                <select name="estado" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">UF</option>
                    <option value="SP">SP</option>
                    <option value="RJ">RJ</option>
                    <option value="MG">MG</option>
                    <option value="RS">RS</option>
                    <option value="PR">PR</option>
                    <option value="SC">SC</option>
                    <option value="BA">BA</option>
                    <option value="GO">GO</option>
                    <option value="PE">PE</option>
                    <option value="CE">CE</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    CEP
                </label>
                <input type="text" name="cep" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="00000-000" maxlength="10">
            </div>
        </div>
    </div>
    
    <!-- Dados do Usuário Responsável -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Usuário Responsável</h2>
                <p class="text-sm text-gray-500">Pessoa responsável pela empresa na plataforma</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nome Completo *
                </label>
                <input type="text" name="nome_responsavel" required 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="Ex: João Silva Santos">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email *
                </label>
                <input type="email" name="email_responsavel" required 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="joao@empresa.com.br">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Telefone/WhatsApp
                </label>
                <input type="tel" name="telefone_responsavel" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="(11) 99999-9999">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Cargo/Função
                </label>
                <input type="text" name="cargo_responsavel" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="Ex: CEO, Diretor, Gerente">
            </div>
        </div>
    </div>
    
    <!-- Configurações Administrativas -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Configurações Administrativas</h2>
                <p class="text-sm text-gray-500">Consultor responsável e observações</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Consultor Responsável *
                </label>
                <select name="consultor_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione o consultor responsável</option>
                    <?php foreach ($dados['consultores'] as $consultor): ?>
                    <option value="<?= $consultor['id'] ?>">
                        <?= htmlspecialchars($consultor['nome']) ?> (<?= htmlspecialchars($consultor['email']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Observações Administrativas
                </label>
                <textarea name="observacoes" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"
                          placeholder="Informações internas sobre o cliente, histórico, particularidades..."></textarea>
            </div>
        </div>
    </div>
    
    <!-- Informações Importantes -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm">
                <h3 class="font-medium text-yellow-800 mb-1">Informações importantes:</h3>
                <ul class="text-yellow-700 space-y-1">
                    <li>• O cliente será criado com status "Ativo" automaticamente</li>
                    <li>• Uma senha temporária será gerada e enviada por email para o responsável</li>
                    <li>• O consultor selecionado será notificado sobre o novo cliente</li>
                    <li>• O responsável deverá alterar a senha no primeiro acesso</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Botões de Ação -->
    <div class="flex items-center justify-end gap-4">
        <a href="<?= APP_URL ?>/admin/clientes" 
           class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Cancelar
        </a>
        <button type="submit" 
                class="px-6 py-3 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Criar Cliente
        </button>
    </div>
</form>

<script>
// Máscaras para campos
document.addEventListener('DOMContentLoaded', function() {
    // CNPJ
    const cnpjInput = document.querySelector('input[name="cnpj"]');
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // CEP
    const cepInput = document.querySelector('input[name="cep"]');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // Telefones
    document.querySelectorAll('input[type="tel"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    });
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>