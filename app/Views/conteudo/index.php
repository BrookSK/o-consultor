<?php $tituloPagina = 'Central de Conteúdo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Central de Conteúdo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Central de Conteúdo</h1>
        <p class="text-gray-500 mt-1">Notícias, cursos, cases e inteligência de mercado.</p>
    </div>
    <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
    <a href="<?= APP_URL ?>/central-de-conteudo/admin" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">⚙️ Gerenciar</a>
    <?php endif; ?>
</div>

<!-- 4 Abas -->
<div x-data="{ aba: 'noticias' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'noticias'" :class="aba === 'noticias' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📰 Notícias e Atualidades</button>
            <button @click="aba = 'academy'" :class="aba === 'academy' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">🎓 Academy</button>
            <button @click="aba = 'casos'" :class="aba === 'casos' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📋 Casos Reais</button>
            <button @click="aba = 'inteligencia'" :class="aba === 'inteligencia' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">🧠 Inteligência de Mercado</button>
        </nav>
    </div>

    <!-- ABA 1: NOTÍCIAS -->
    <div x-show="aba === 'noticias'" x-transition>
        <!-- Perfil de Busca (colapsável) -->
        <div x-data="{ perfilAberto: false }" class="mb-6">
            <button @click="perfilAberto = !perfilAberto" class="flex items-center gap-2 text-sm text-primary font-medium hover:underline">
                <svg class="w-4 h-4 transition" :class="perfilAberto && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                ⚙️ Configurar meu perfil de busca
            </button>
            <div x-show="perfilAberto" x-transition class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Setor/Nicho</label>
                        <input type="text" value="<?= htmlspecialchars($dados['perfil_busca']['setor']) ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Língua</label>
                        <input type="text" value="<?= htmlspecialchars($dados['perfil_busca']['lingua']) ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sites de referência</label>
                        <div class="space-y-2">
                            <?php foreach ($dados['perfil_busca']['sites'] as $site): ?>
                            <div class="flex items-center gap-2">
                                <input type="url" value="<?= htmlspecialchars($site) ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-lg">&times;</button>
                            </div>
                            <?php endforeach; ?>
                            <button onclick="let div=document.createElement('div');div.className='flex items-center gap-2';div.innerHTML='<input type=\'url\' placeholder=\'https://...\' class=\'flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary\'><button onclick=\'this.parentElement.remove()\' class=\'text-red-400 hover:text-red-600 text-lg\'>&times;</button>';this.before(div)" class="text-sm text-primary font-medium hover:underline">+ Adicionar site</button>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Palavras-chave adicionais</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (($dados['perfil_busca']['palavras_chave'] ?? []) as $kw): ?>
                            <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium flex items-center gap-1"><?= htmlspecialchars($kw) ?> <button onclick="this.parentElement.remove()" class="text-primary/50 hover:text-primary">&times;</button></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequência</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="diaria" <?= ($dados['perfil_busca']['frequencia'] ?? '') === 'diaria' ? 'selected' : '' ?>>Diária</option>
                            <option value="semanal" <?= ($dados['perfil_busca']['frequencia'] ?? '') === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button onclick="buscarAgora()" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:bg-orange-700">🔍 Buscar agora</button>
                        <span class="text-xs text-gray-400">Último: <?= !empty($dados['perfil_busca']['ultimo_update']) ? date('d/m/Y H:i', strtotime($dados['perfil_busca']['ultimo_update'])) : 'Nunca' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-2 mb-4">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas categorias</option><option>Mercado</option><option>Tecnologia</option><option>Regulamentação</option><option>Tendência</option><option>Negócio</option></select>
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Toda relevância</option><option>Alta</option><option>Média</option><option>Baixa</option></select>
        </div>

        <!-- Feed de Notícias -->
        <div class="space-y-4">
            <?php foreach ($dados['noticias'] as $noticia):
                $catBadge = match($noticia['categoria']) {
                    'Mercado' => 'bg-blue-100 text-blue-700',
                    'Tecnologia' => 'bg-purple-100 text-purple-700',
                    'Regulamentação' => 'bg-red-100 text-red-700',
                    'Tendência' => 'bg-green-100 text-green-700',
                    'Negócio' => 'bg-orange-100 text-orange-700',
                    default => 'bg-gray-100 text-gray-700',
                };
                $relBadge = match($noticia['relevancia']) {
                    'alta' => 'bg-red-50 text-red-700 border-l-red-500',
                    'media' => 'bg-yellow-50 text-yellow-700 border-l-yellow-500',
                    default => 'bg-gray-50 text-gray-600 border-l-gray-300',
                };
            ?>
            <a href="<?= APP_URL ?>/central-de-conteudo/noticia?id=<?= $noticia['id'] ?>" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition border-l-4 <?= $relBadge ?>">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-gray-400 font-medium"><?= htmlspecialchars($noticia['fonte']) ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $catBadge ?>"><?= htmlspecialchars($noticia['categoria']) ?></span>
                            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($noticia['data'])) ?></span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-1"><?= htmlspecialchars($noticia['titulo']) ?></h3>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($noticia['resumo']) ?></p>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded <?= $noticia['relevancia'] === 'alta' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($noticia['relevancia']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ABA 2: ACADEMY -->
    <div x-show="aba === 'academy'" x-transition style="display:none;">
        <div class="max-w-2xl mx-auto py-8">
            <!-- Card Central Academy -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                    <span class="text-3xl">🎓</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">My Academy</h2>
                <p class="text-sm text-gray-500 mb-6">Seus cursos estão na plataforma My Academy. Clique abaixo para acessar com seu login já configurado.</p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                            <?= strtoupper(substr($dados['usuario']['nome'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($dados['usuario']['nome'] ?? '') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($dados['usuario']['email'] ?? '') ?></p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mb-6">Acesse sua área de alunos, cursos, comunidade e certificados diretamente na plataforma.</p>

                <a href="<?= APP_URL ?>/academy/sso" target="_blank"
                   class="inline-block w-full bg-primary text-white py-3.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition">
                    🚀 Acessar meus cursos
                </a>

                <p class="text-xs text-gray-400 mt-4">Você será redirecionado para <?= htmlspecialchars($dados['academy_url']) ?> com login automático (SSO).</p>
            </div>
        </div>
    </div>

    <!-- ABA 3: CASOS REAIS -->
    <div x-show="aba === 'casos'" x-transition style="display:none;">
        <div class="flex flex-wrap gap-2 mb-4">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos os setores</option><option>Tecnologia</option><option>SaaS</option><option>Varejo</option></select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($dados['casos'] as $caso): ?>
            <a href="<?= APP_URL ?>/central-de-conteudo/caso?id=<?= $caso['id'] ?>" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition block">
                <?php if ($caso['exclusivo']): ?>
                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-accent/10 text-accent mb-2">⭐ Exclusivo para Clientes</span>
                <?php endif; ?>
                <h3 class="text-sm font-semibold text-gray-800 mb-2"><?= htmlspecialchars($caso['titulo']) ?></h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600"><?= htmlspecialchars($caso['setor']) ?></span>
                <p class="text-xs text-gray-500 mt-3"><strong>Desafio:</strong> <?= htmlspecialchars($caso['desafio']) ?></p>
                <p class="text-xs text-green-700 mt-2"><strong>Resultado:</strong> <?= htmlspecialchars($caso['resultado']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ABA 4: INTELIGÊNCIA DE MERCADO -->
    <div x-show="aba === 'inteligencia'" x-transition style="display:none;">
        <div class="flex flex-wrap gap-2 mb-4">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos os tipos</option><option>Tendência</option><option>Regulamentação</option><option>Mercado</option><option>Tecnologia</option></select>
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Toda relevância</option><option>Alta</option><option>Média</option></select>
            <label class="flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-lg text-xs cursor-pointer"><input type="checkbox" class="w-3 h-3"> Apenas do meu setor</label>
        </div>

        <div class="space-y-4">
            <?php foreach ($dados['inteligencia'] as $intel):
                $tipoBadge = match($intel['tipo']) {
                    'Tendência' => 'bg-purple-100 text-purple-700',
                    'Regulamentação' => 'bg-red-100 text-red-700',
                    'Mercado' => 'bg-blue-100 text-blue-700',
                    'Tecnologia' => 'bg-green-100 text-green-700',
                    default => 'bg-gray-100 text-gray-700',
                };
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-gray-400"><?= htmlspecialchars($intel['fonte']) ?></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $tipoBadge ?>"><?= htmlspecialchars($intel['tipo']) ?></span>
                            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($intel['data'])) ?></span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-2"><?= htmlspecialchars($intel['titulo']) ?></h3>
                        <p class="text-xs text-gray-600 mb-2"><strong>Impacto:</strong> <?= htmlspecialchars($intel['impacto']) ?></p>
                        <p class="text-xs text-green-700"><strong>Ação sugerida:</strong> <?= htmlspecialchars($intel['acao']) ?></p>
                    </div>
                    <div class="flex flex-col gap-1 flex-shrink-0">
                        <span class="text-xs font-bold px-2 py-0.5 rounded <?= $intel['relevancia'] === 'alta' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($intel['relevancia']) ?></span>
                        <button onclick="salvarItem(this)" class="text-xs text-primary hover:underline">💾 Salvar</button>
                        <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
                        <button onclick="compartilharItem(this, '<?= htmlspecialchars(addslashes($intel['titulo'])) ?>')" class="text-xs text-primary hover:underline">📤 Compartilhar</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
async function buscarAgora() {
    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/buscar-agora', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) { if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem); }
    } catch(e) { alert('Erro.'); }
}

function salvarItem(btn) {
    btn.innerHTML = '✓ Salvo';
    btn.className = 'text-xs text-green-600 font-semibold';
    btn.disabled = true;
    if (typeof Toast !== 'undefined') Toast.sucesso('Conteúdo salvo nos seus favoritos!');
}

function compartilharItem(btn, titulo) {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(titulo + ' — ' + url);
    }
    btn.innerHTML = '✓ Copiado';
    btn.className = 'text-xs text-green-600 font-semibold';
    setTimeout(() => { btn.innerHTML = '📤 Compartilhar'; btn.className = 'text-xs text-primary hover:underline'; }, 3000);
    if (typeof Toast !== 'undefined') Toast.sucesso('Link copiado para a área de transferência!');
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
