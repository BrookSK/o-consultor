<?php $tituloPagina = 'Bem-vindo'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F',700:'#162D4A'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-900/50 min-h-screen flex items-center justify-center p-4">

<div x-data="onboardingWizard()" class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden">
    <!-- Progresso -->
    <div class="h-1 bg-gray-200"><div class="h-full bg-accent transition-all duration-500" :style="'width:'+(step/5*100)+'%'"></div></div>

    <!-- STEP 1: Boas-vindas -->
    <div x-show="step===1" class="p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
            <span class="text-3xl">🚀</span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Bem-vindo ao Consultor, <?= htmlspecialchars($dados['usuario']['nome'] ?? '') ?>!</h2>
        <p class="text-gray-500 mb-6">Sua plataforma de gestão empresarial inteligente.</p>
        <div class="text-left max-w-md mx-auto space-y-3 mb-8">
            <div class="flex items-start gap-3"><span class="text-green-500 mt-0.5">✓</span><p class="text-sm text-gray-700">Diagnóstico completo da sua empresa com score de maturidade</p></div>
            <div class="flex items-start gap-3"><span class="text-green-500 mt-0.5">✓</span><p class="text-sm text-gray-700">Plano de ação e SOPs gerados automaticamente pela IA</p></div>
            <div class="flex items-start gap-3"><span class="text-green-500 mt-0.5">✓</span><p class="text-sm text-gray-700">Conteúdo, cursos e inteligência de mercado para sua empresa</p></div>
        </div>
        <button @click="step=2" class="bg-primary text-white px-8 py-3 rounded-lg font-medium hover:bg-primary-700 transition">Vamos começar →</button>
    </div>

    <!-- STEP 2: Módulos -->
    <div x-show="step===2" style="display:none" class="p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-2 text-center">O que você terá acesso</h2>
        <p class="text-sm text-gray-500 text-center mb-6">5 módulos integrados para estruturar sua empresa.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
            <div class="border border-gray-200 rounded-lg p-4"><span class="text-xl">📋</span><h4 class="font-semibold text-gray-800 text-sm mt-2">Diagnóstico</h4><p class="text-xs text-gray-500 mt-1">Avaliação completa de maturidade.</p></div>
            <div class="border border-gray-200 rounded-lg p-4"><span class="text-xl">🎯</span><h4 class="font-semibold text-gray-800 text-sm mt-2">Plano de Ação</h4><p class="text-xs text-gray-500 mt-1">Prioridades geradas por IA.</p></div>
            <div class="border border-gray-200 rounded-lg p-4"><span class="text-xl">📖</span><h4 class="font-semibold text-gray-800 text-sm mt-2">Manual Operacional</h4><p class="text-xs text-gray-500 mt-1">SOPs completos por departamento.</p></div>
            <div class="border border-gray-200 rounded-lg p-4"><span class="text-xl">📰</span><h4 class="font-semibold text-gray-800 text-sm mt-2">Central de Conteúdo</h4><p class="text-xs text-gray-500 mt-1">Notícias e inteligência do setor.</p></div>
            <div class="border border-gray-200 rounded-lg p-4 sm:col-span-2 bg-blue-50 border-blue-200">
                <span class="text-xl">🎓</span><h4 class="font-semibold text-gray-800 text-sm mt-2">Academy</h4>
                <p class="text-xs text-gray-500 mt-1">Seus cursos já estão prontos na plataforma My Academy. Configuraremos o acesso em seguida.</p>
            </div>
        </div>
        <div class="flex justify-between">
            <button @click="step=1" class="text-sm text-gray-500 hover:text-gray-700">← Voltar</button>
            <button @click="step=3" class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Próximo →</button>
        </div>
    </div>

    <!-- STEP 3: Dados rápidos -->
    <div x-show="step===3" style="display:none" class="p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-2 text-center">Conte-nos sobre sua empresa</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Informações básicas para personalizar sua experiência.</p>
        <div class="space-y-4 max-w-md mx-auto">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome da empresa</label><input type="text" x-model="form.empresa" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Nome da sua empresa"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
                <select x-model="form.setor" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione...</option>
                    <option>Tecnologia</option><option>Varejo</option><option>Serviços</option><option>Saúde</option><option>Construção</option><option>Alimentação</option><option>Educação</option><option>Indústria</option><option>Logística</option><option>Costura/Moda</option><option>Financeiro</option><option>Jurídico</option><option>Imobiliário</option><option>Outro</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Colaboradores</label><input type="number" x-model="form.colaboradores" min="1" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Quantas pessoas na empresa?"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Faturamento mensal</label>
                <select x-model="form.faturamento" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione...</option>
                    <option>Até R$ 50 mil</option><option>R$ 50-100 mil</option><option>R$ 100-300 mil</option><option>R$ 300-500 mil</option><option>R$ 500 mil - R$ 1 milhão</option><option>Acima de R$ 1 milhão</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Principal desafio</label><textarea x-model="form.desafio" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Qual o maior desafio da sua empresa hoje?"></textarea></div>
        </div>
        <div class="flex justify-between mt-6">
            <button @click="step=2" class="text-sm text-gray-500 hover:text-gray-700">← Voltar</button>
            <button @click="step=4" class="bg-primary text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Próximo →</button>
        </div>
    </div>

    <!-- STEP 4: Recomendação -->
    <div x-show="step===4" style="display:none" class="p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-3xl">📋</span>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Recomendamos iniciar pelo Diagnóstico</h2>
        <p class="text-sm text-gray-600 max-w-md mx-auto mb-6">Com base nele, o sistema gerará automaticamente seu <strong>Plano de Ação</strong> e todos os <strong>SOPs da sua empresa</strong>, já preenchidos no padrão do seu setor.</p>
        <div class="bg-primary/5 border border-primary/20 rounded-lg p-4 text-left max-w-md mx-auto mb-6">
            <p class="text-xs text-primary font-medium">O diagnóstico avalia 5 áreas e leva cerca de 10 minutos. Após concluí-lo, tudo é gerado automaticamente.</p>
        </div>
        <div class="flex flex-col gap-3 max-w-sm mx-auto">
            <button @click="step=5" class="bg-accent text-white py-3 rounded-lg font-semibold text-sm hover:bg-orange-700">🚀 Ir para o Diagnóstico agora</button>
            <button @click="step=5" class="text-sm text-gray-500 hover:text-gray-700">Fazer depois</button>
        </div>
    </div>

    <!-- STEP 5: Academy -->
    <div x-show="step===5" style="display:none" class="p-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
            <span class="text-3xl">🎓</span>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-3">Vincular Academy</h2>
        <p class="text-sm text-gray-500 mb-6">Você tem conta na My Academy?</p>

        <div x-data="{ temConta: null }" class="max-w-sm mx-auto">
            <div class="flex gap-3 mb-4">
                <button @click="temConta=true" :class="temConta===true?'bg-primary text-white':'bg-gray-100 text-gray-700'" class="flex-1 py-2.5 rounded-lg text-sm font-medium transition">Sim, tenho</button>
                <button @click="temConta=false" :class="temConta===false?'bg-primary text-white':'bg-gray-100 text-gray-700'" class="flex-1 py-2.5 rounded-lg text-sm font-medium transition">Não tenho</button>
            </div>
            <div x-show="temConta===true" class="space-y-3">
                <input type="email" placeholder="email@myacademy.com.br" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <button @click="concluir()" class="w-full bg-accent text-white py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700">Vincular agora</button>
            </div>
            <div x-show="temConta===false" class="space-y-3">
                <p class="text-xs text-gray-500">Criaremos seu acesso. Você receberá um convite por email.</p>
                <button @click="concluir()" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Criar conta Academy</button>
            </div>
            <button @click="concluir()" class="mt-4 text-sm text-gray-400 hover:text-gray-600">Fazer isso depois</button>
        </div>
    </div>
</div>

<script>
function onboardingWizard() {
    return {
        step: 1,
        form: { empresa: '', setor: '', colaboradores: '', faturamento: '', desafio: '' },
        async concluir() {
            const fd = new FormData();
            fd.append('csrf_token', '<?= Csrf::token() ?>');
            try {
                const res = await fetch('<?= APP_URL ?>/onboarding/concluir', { method:'POST', body:fd });
                const data = await res.json();
                if (data.sucesso) window.location.href = data.redirect || '<?= APP_URL ?>/dashboard';
            } catch(e) { window.location.href = '<?= APP_URL ?>/dashboard'; }
        }
    };
}
</script>
</body>
</html>
