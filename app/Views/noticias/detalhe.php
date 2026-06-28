<?php 
$noticia = $dados['noticia'] ?? [];
$tituloPagina = $noticia['titulo'] ?? 'Detalhes da Notícia'; 
?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/noticias" class="hover:text-primary">Central de Notícias</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Detalhes</li>
    </ol>
</nav>

<?php if (empty($noticia)): ?>
    <div class="text-center py-12">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Notícia não encontrada</h1>
        <p class="text-gray-600 mb-6">A notícia solicitada não existe ou foi removida.</p>
        <a href="<?= APP_URL ?>/noticias" 
           class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700">
            Voltar às Notícias
        </a>
    </div>
<?php else: ?>

<!-- Cabeçalho da Notícia -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full">
                <?= htmlspecialchars($noticia['categoria'] ?? 'Geral') ?>
            </span>
            <span class="text-sm text-gray-500">
                📰 <?= htmlspecialchars($noticia['fonte'] ?? 'Fonte desconhecida') ?>
            </span>
            <span class="text-sm text-gray-500">
                🕒 <?= date('d/m/Y H:i', strtotime($noticia['data_publicacao'] ?? 'now')) ?>
            </span>
            <?php if (($noticia['relevancia'] ?? 0) >= 8): ?>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">
                    ⭐ Alta Relevância
                </span>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center gap-3">
            <button onclick="toggleFavorito()" 
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                <?= ($noticia['favorita'] ?? false) ? '⭐' : '☆' ?> 
                <?= ($noticia['favorita'] ?? false) ? 'Remover dos Favoritos' : 'Adicionar aos Favoritos' ?>
            </button>
            
            <a href="<?= htmlspecialchars($noticia['url'] ?? '#') ?>" target="_blank"
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                🔗 Ver Original
            </a>
        </div>
    </div>
    
    <h1 class="text-2xl font-bold text-gray-800 mb-3">
        <?= htmlspecialchars($noticia['titulo'] ?? 'Título não disponível') ?>
    </h1>
    
    <?php if (!empty($noticia['resumo'])): ?>
        <p class="text-lg text-gray-600 leading-relaxed">
            <?= htmlspecialchars($noticia['resumo']) ?>
        </p>
    <?php endif; ?>
</div>

<!-- Conteúdo Principal -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Conteúdo da Notícia -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">📄 Conteúdo Completo</h2>
            
            <div class="prose max-w-none">
                <?php if (!empty($noticia['conteudo'])): ?>
                    <div class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                        <?= nl2br(htmlspecialchars($noticia['conteudo'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">Conteúdo não disponível. <a href="<?= htmlspecialchars($noticia['url'] ?? '#') ?>" target="_blank" class="text-primary hover:underline">Ver notícia original</a>.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Análise de 5 Blocos (se disponível) -->
        <?php if (!empty($noticia['analise_blocos'])): ?>
            <?php $analise = json_decode($noticia['analise_blocos'], true); ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🤖 Análise Inteligente</h2>
                
                <div class="space-y-6">
                    <!-- Bloco 1: Relevância -->
                    <?php if (!empty($analise['relevancia'])): ?>
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h3 class="font-semibold text-gray-800 mb-2">🎯 Relevância para o Negócio</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($analise['relevancia'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bloco 2: Oportunidades -->
                    <?php if (!empty($analise['oportunidades'])): ?>
                        <div class="border-l-4 border-green-500 pl-4">
                            <h3 class="font-semibold text-gray-800 mb-2">💡 Oportunidades Identificadas</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($analise['oportunidades'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bloco 3: Riscos -->
                    <?php if (!empty($analise['riscos'])): ?>
                        <div class="border-l-4 border-red-500 pl-4">
                            <h3 class="font-semibold text-gray-800 mb-2">⚠️ Riscos e Ameaças</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($analise['riscos'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bloco 4: Ações Recomendadas -->
                    <?php if (!empty($analise['acoes'])): ?>
                        <div class="border-l-4 border-purple-500 pl-4">
                            <h3 class="font-semibold text-gray-800 mb-2">✅ Ações Recomendadas</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($analise['acoes'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bloco 5: Conclusão -->
                    <?php if (!empty($analise['conclusao'])): ?>
                        <div class="border-l-4 border-gray-500 pl-4">
                            <h3 class="font-semibold text-gray-800 mb-2">📝 Conclusão Estratégica</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($analise['conclusao'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <button onclick="criarConteudoDesdeNoticia()" 
                            class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-orange-700">
                        📝 Criar Conteúdo a partir disso
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Botão para gerar análise -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🤖 Análise Inteligente</h2>
                <p class="text-gray-600 mb-4">Esta notícia ainda não foi analisada pela IA. Clique no botão abaixo para gerar uma análise completa em 5 blocos.</p>
                
                <button onclick="gerarAnalise()" 
                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700">
                    🤖 Gerar Análise IA
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <!-- Informações da Notícia -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">📊 Informações</h3>
            
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Publicação:</span>
                    <span class="text-gray-800"><?= date('d/m/Y H:i', strtotime($noticia['data_publicacao'] ?? 'now')) ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Relevância:</span>
                    <span class="text-gray-800">
                        <?php
                        $relevancia = $noticia['relevancia'] ?? 0;
                        echo str_repeat('⭐', min(5, max(1, round($relevancia / 2))));
                        echo " ({$relevancia}/10)";
                        ?>
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Categoria:</span>
                    <span class="text-gray-800"><?= htmlspecialchars($noticia['categoria'] ?? 'Geral') ?></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Fonte:</span>
                    <span class="text-gray-800"><?= htmlspecialchars($noticia['fonte'] ?? 'N/A') ?></span>
                </div>
                
                <?php if (!empty($noticia['palavras_chave'])): ?>
                    <div>
                        <span class="text-gray-500 block mb-1">Palavras-chave:</span>
                        <div class="flex flex-wrap gap-1">
                            <?php 
                            $palavras = is_array($noticia['palavras_chave']) 
                                ? $noticia['palavras_chave'] 
                                : explode(',', $noticia['palavras_chave']);
                            foreach ($palavras as $palavra): 
                            ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                    <?= htmlspecialchars(trim($palavra)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notícias Relacionadas -->
        <?php if (!empty($dados['relacionadas'])): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-800 mb-4">🔗 Notícias Relacionadas</h3>
                
                <div class="space-y-3">
                    <?php foreach ($dados['relacionadas'] as $relacionada): ?>
                        <a href="<?= APP_URL ?>/noticias/detalhe/<?= $relacionada['id'] ?>" 
                           class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <h4 class="text-sm font-medium text-gray-800 mb-1">
                                <?= htmlspecialchars($relacionada['titulo']) ?>
                            </h4>
                            <p class="text-xs text-gray-500">
                                <?= date('d/m/Y', strtotime($relacionada['data_publicacao'])) ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<script>
async function toggleFavorito() {
    const btn = event.target;
    const textoOriginal = btn.innerHTML;
    
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/favoritar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Csrf::token() ?>',
                noticia_id: <?= $noticia['id'] ?? 0 ?>
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            if (data.favorita) {
                btn.innerHTML = '⭐ Remover dos Favoritos';
            } else {
                btn.innerHTML = '☆ Adicionar aos Favoritos';
            }
            showToast(data.favorita ? '✅ Adicionada aos favoritos!' : '✅ Removida dos favoritos!', 'success');
        } else {
            showToast('❌ Erro ao atualizar favorito', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('❌ Erro de conexão', 'error');
    }
}

async function gerarAnalise() {
    const btn = event.target;
    const textoOriginal = btn.textContent;
    btn.textContent = '🤖 Gerando análise...';
    btn.disabled = true;
    
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/gerar-analise', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Csrf::token() ?>',
                noticia_id: <?= $noticia['id'] ?? 0 ?>
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast('✅ Análise gerada com sucesso!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(`❌ ${data.erro}`, 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('❌ Erro de conexão', 'error');
    } finally {
        btn.textContent = textoOriginal;
        btn.disabled = false;
    }
}

function criarConteudoDesdeNoticia() {
    const titulo = '<?= addslashes($noticia['titulo'] ?? '') ?>';
    const resumo = '<?= addslashes($noticia['resumo'] ?? '') ?>';
    
    // Redirecionar para máquina de conteúdo com dados pré-preenchidos
    const params = new URLSearchParams({
        tema: titulo,
        inspiracao: resumo,
        fonte: 'noticia',
        fonte_id: <?= $noticia['id'] ?? 0 ?>
    });
    
    window.open(`<?= APP_URL ?>/maquina-conteudo/gerar?${params}`, '_blank');
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm font-medium z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>