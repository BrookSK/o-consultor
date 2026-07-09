<?php $tituloPagina = 'Central de Notícias'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Central de Notícias</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Central de Notícias</h1>
        <p class="text-gray-600">Notícias relevantes para o seu negócio</p>
    </div>
    <div class="flex gap-3">
        <button onclick="configurarPerfil()" 
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            ⚙️ Configurar Perfil
        </button>
        <button onclick="buscarAgora()" 
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
            🔍 Buscar Agora
        </button>
    </div>
</div>

<!-- Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
                <span class="text-blue-600 text-lg">📰</span>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-500">Notícias Hoje</p>
                <p class="text-xl font-semibold text-gray-800" id="noticias-hoje">0</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
                <span class="text-green-600 text-lg">⭐</span>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-500">Favoritas</p>
                <p class="text-xl font-semibold text-gray-800" id="noticias-favoritas">0</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
                <span class="text-yellow-600 text-lg">🎯</span>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-500">Relevantes</p>
                <p class="text-xl font-semibold text-gray-800" id="noticias-relevantes">0</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
                <span class="text-purple-600 text-lg">📊</span>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-500">Analisadas</p>
                <p class="text-xl font-semibold text-gray-800" id="noticias-analisadas">0</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-wrap gap-4 items-center">
        <select id="filtro-categoria" onchange="filtrarNoticias()" 
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none">
            <option value="">Todas as categorias</option>
            <option value="mercado">Mercado</option>
            <option value="tecnologia">Tecnologia</option>
            <option value="regulatorio">Regulatório</option>
            <option value="economia">Economia</option>
            <option value="setor">Setor Específico</option>
        </select>
        
        <select id="filtro-periodo" onchange="filtrarNoticias()" 
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none">
            <option value="">Todos os períodos</option>
            <option value="hoje">Hoje</option>
            <option value="semana">Esta semana</option>
            <option value="mes">Este mês</option>
        </select>
        
        <label class="flex items-center">
            <input type="checkbox" id="apenas-favoritas" onchange="filtrarNoticias()" 
                   class="rounded border-gray-300 text-primary focus:ring-primary/20">
            <span class="ml-2 text-sm text-gray-700">Apenas favoritas</span>
        </label>
        
        <input type="text" id="busca-texto" onkeyup="filtrarNoticias()" 
               placeholder="Buscar por título ou conteúdo..."
               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none">
    </div>
</div>

<!-- Lista de Notícias -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div id="loading-noticias" class="p-8 text-center text-gray-500">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="mt-2">Carregando notícias...</p>
    </div>
    
    <div id="lista-noticias" class="divide-y divide-gray-100 hidden">
        <!-- Notícias serão carregadas via JavaScript -->
    </div>
    
    <div id="sem-noticias" class="p-8 text-center text-gray-500 hidden">
        <p class="text-lg">📰</p>
        <p class="mt-2">Nenhuma notícia encontrada</p>
        <p class="text-sm mt-1">Ajuste os filtros ou execute uma nova busca</p>
    </div>
</div>

<!-- Modal Configurar Perfil -->
<div id="modal-perfil" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">Configurar Perfil de Busca</h3>
                <button onclick="fecharModalPerfil()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-4">
                <p class="text-gray-600 mb-4">Configure os sites de referência para busca de notícias relevantes ao seu negócio.</p>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sites de Referência</label>
                    <div id="lista-sites" class="space-y-2 mb-4">
                        <!-- Sites serão carregados via JS -->
                    </div>
                    
                    <div class="flex gap-2">
                        <input type="text" id="novo-site" placeholder="Ex: https://valor.globo.com" 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none">
                        <button onclick="adicionarSite()" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700 text-sm">
                            Adicionar
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button onclick="fecharModalPerfil()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="salvarPerfil()" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                        Salvar Configurações
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let noticiasData = [];
let sitesReferencia = [];

document.addEventListener('DOMContentLoaded', function() {
    carregarNoticias();
    carregarEstatisticas();
});

async function carregarNoticias() {
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/buscar');
        if (response.ok) {
            const data = await response.json();
            noticiasData = data.noticias || [];
            renderizarNoticias(noticiasData);
            atualizarEstatisticas();
        } else {
            mostrarSemNoticias();
        }
    } catch (error) {
        console.error('Erro ao carregar notícias:', error);
        mostrarSemNoticias();
    } finally {
        document.getElementById('loading-noticias').classList.add('hidden');
    }
}

function renderizarNoticias(noticias) {
    const lista = document.getElementById('lista-noticias');
    const semNoticias = document.getElementById('sem-noticias');
    
    if (noticias.length === 0) {
        lista.classList.add('hidden');
        semNoticias.classList.remove('hidden');
        return;
    }
    
    lista.innerHTML = '';
    lista.classList.remove('hidden');
    semNoticias.classList.add('hidden');
    
    noticias.forEach(noticia => {
        const item = document.createElement('div');
        item.className = 'p-4 hover:bg-gray-50';
        item.innerHTML = `
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                            ${noticia.categoria || 'Geral'}
                        </span>
                        <span class="text-xs text-gray-500">${formatarData(noticia.data_publicacao)}</span>
                        ${noticia.relevancia >= 8 ? '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">Alta Relevância</span>' : ''}
                    </div>
                    
                    <h3 class="font-semibold text-gray-800 hover:text-primary cursor-pointer" 
                        onclick="abrirNoticia(${noticia.id})">
                        ${noticia.titulo}
                    </h3>
                    
                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                        ${noticia.resumo || noticia.conteudo.substring(0, 150) + '...'}
                    </p>
                    
                    <div class="flex items-center gap-4 mt-3">
                        <span class="text-xs text-gray-500">📰 ${noticia.fonte}</span>
                        <button onclick="toggleFavorito(${noticia.id})" 
                                class="text-xs text-gray-500 hover:text-yellow-500">
                            ${noticia.favorita ? '⭐' : '☆'} Favoritar
                        </button>
                        <a href="${noticia.url}" target="_blank" 
                           class="text-xs text-primary hover:underline">
                            🔗 Ver original
                        </a>
                        ${noticia.analisado ? 
                            `<button onclick="verAnalise(${noticia.id})" class="text-xs text-green-600 hover:underline">
                                📊 Ver Análise
                            </button>` : 
                            `<button onclick="gerarAnalise(${noticia.id})" class="text-xs text-blue-600 hover:underline">
                                🤖 Gerar Análise IA
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `;
        lista.appendChild(item);
    });
}

function mostrarSemNoticias() {
    document.getElementById('lista-noticias').classList.add('hidden');
    document.getElementById('sem-noticias').classList.remove('hidden');
}

function atualizarEstatisticas() {
    const hoje = new Date().toDateString();
    const noticiasHoje = noticiasData.filter(n => new Date(n.data_publicacao).toDateString() === hoje).length;
    const favoritas = noticiasData.filter(n => n.favorita).length;
    const relevantes = noticiasData.filter(n => n.relevancia >= 7).length;
    const analisadas = noticiasData.filter(n => n.analisado).length;
    
    document.getElementById('noticias-hoje').textContent = noticiasHoje;
    document.getElementById('noticias-favoritas').textContent = favoritas;
    document.getElementById('noticias-relevantes').textContent = relevantes;
    document.getElementById('noticias-analisadas').textContent = analisadas;
}

async function buscarAgora() {
    const btn = event.target;
    const textoOriginal = btn.textContent;
    btn.textContent = '🔄 Buscando...';
    btn.disabled = true;

    try {
        const response = await fetch('<?= APP_URL ?>/noticias/buscar-agora', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: '<?= Csrf::token() ?>' })
        });

        const data = await response.json();

        if (!data.sucesso) {
            showToast(`❌ ${data.erro}`, 'error');
            return;
        }

        await acompanharBuscaNoticias(data.fila_id, btn);

    } catch (error) {
        console.error('Erro:', error);
        showToast('❌ Erro de conexão', 'error');
    } finally {
        btn.textContent = textoOriginal;
        btn.disabled = false;
    }
}

// Acompanha (polling) a busca enfileirada. Ao concluir, recarrega a lista de notícias.
async function acompanharBuscaNoticias(filaId, btn) {
    const esperar = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    const maxTentativas = 60; // ~2min

    for (let t = 0; t < maxTentativas; t++) {
        let status;
        try {
            const res = await fetch('<?= APP_URL ?>/noticias/status-fila-busca?fila_id=' + filaId + '&_=' + Date.now());
            status = await res.json();
        } catch (e) {
            await esperar(2000);
            continue;
        }

        if (!status.sucesso) break;
        if (btn && status.mensagem) btn.textContent = '🔄 ' + status.mensagem;

        // Fallback: se não há cron/exec disponível no servidor, processa 1 passo via HTTP.
        try {
            await fetch('<?= APP_URL ?>/noticias/processar-fila-busca?_=' + Date.now());
        } catch (e) { /* best-effort */ }

        if (status.concluido) {
            showToast(`✅ ${status.noticias_novas} nova(s) notícia(s) encontrada(s)!`, 'success');
            carregarNoticias();
            return;
        }
        if (status.erro) {
            showToast(`❌ ${status.mensagem || 'Erro ao buscar notícias.'}`, 'error');
            return;
        }

        await esperar(2000);
    }

    carregarNoticias();
}

async function configurarPerfil() {
    await carregarSitesReferencia();
    document.getElementById('modal-perfil').classList.remove('hidden');
}

function fecharModalPerfil() {
    document.getElementById('modal-perfil').classList.add('hidden');
}

async function carregarSitesReferencia() {
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/perfil');
        if (response.ok) {
            const data = await response.json();
            sitesReferencia = data.sites || [];
            renderizarSites();
        }
    } catch (error) {
        console.error('Erro ao carregar sites:', error);
    }
}

function renderizarSites() {
    const lista = document.getElementById('lista-sites');
    lista.innerHTML = '';
    
    sitesReferencia.forEach((site, index) => {
        const item = document.createElement('div');
        item.className = 'flex items-center justify-between p-2 border border-gray-200 rounded-lg';
        item.innerHTML = `
            <span class="text-sm text-gray-700">${site}</span>
            <button onclick="removerSite(${index})" class="text-red-600 hover:text-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        lista.appendChild(item);
    });
}

function adicionarSite() {
    const input = document.getElementById('novo-site');
    const site = input.value.trim();
    
    if (!site) return;
    
    if (!site.startsWith('http')) {
        showToast('❌ URL deve começar com http:// ou https://', 'error');
        return;
    }
    
    sitesReferencia.push(site);
    input.value = '';
    renderizarSites();
}

function removerSite(index) {
    sitesReferencia.splice(index, 1);
    renderizarSites();
}

async function salvarPerfil() {
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/salvar-perfil', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Csrf::token() ?>',
                sites: sitesReferencia
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast('✅ Perfil salvo com sucesso!', 'success');
            fecharModalPerfil();
        } else {
            showToast(`❌ ${data.erro}`, 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('❌ Erro de conexão', 'error');
    }
}

async function toggleFavorito(noticiaId) {
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/favoritar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Csrf::token() ?>',
                noticia_id: noticiaId
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            // Atualizar no array local
            const noticia = noticiasData.find(n => n.id === noticiaId);
            if (noticia) {
                noticia.favorita = !noticia.favorita;
                renderizarNoticias(noticiasData);
                atualizarEstatisticas();
            }
        }
    } catch (error) {
        console.error('Erro ao favoritar:', error);
    }
}

function abrirNoticia(noticiaId) {
    window.open(`<?= APP_URL ?>/noticias/detalhe/${noticiaId}`, '_blank');
}

async function gerarAnalise(noticiaId) {
    const btn = event.target;
    const textoOriginal = btn.textContent;
    btn.textContent = '🤖 Analisando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/gerar-analise', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Csrf::token() ?>',
                noticia_id: noticiaId
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast('✅ Análise gerada com sucesso!', 'success');
            carregarNoticias(); // Recarregar para mostrar análise
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

function verAnalise(noticiaId) {
    window.open(`<?= APP_URL ?>/noticias/analise/${noticiaId}`, '_blank');
}

function filtrarNoticias() {
    const categoria = document.getElementById('filtro-categoria').value;
    const periodo = document.getElementById('filtro-periodo').value;
    const apenasFavoritas = document.getElementById('apenas-favoritas').checked;
    const textoeBusca = document.getElementById('busca-texto').value.toLowerCase();
    
    let noticiasFiltradas = noticiasData;
    
    if (categoria) {
        noticiasFiltradas = noticiasFiltradas.filter(n => n.categoria === categoria);
    }
    
    if (periodo) {
        const agora = new Date();
        const hoje = new Date(agora.getFullYear(), agora.getMonth(), agora.getDate());
        const semanaAtras = new Date(hoje.getTime() - 7 * 24 * 60 * 60 * 1000);
        const mesAtras = new Date(hoje.getFullYear(), hoje.getMonth() - 1, hoje.getDate());
        
        noticiasFiltradas = noticiasFiltradas.filter(n => {
            const dataNoticia = new Date(n.data_publicacao);
            switch (periodo) {
                case 'hoje': return dataNoticia >= hoje;
                case 'semana': return dataNoticia >= semanaAtras;
                case 'mes': return dataNoticia >= mesAtras;
                default: return true;
            }
        });
    }
    
    if (apenasFavoritas) {
        noticiasFiltradas = noticiasFiltradas.filter(n => n.favorita);
    }
    
    if (textoeBusca) {
        noticiasFiltradas = noticiasFiltradas.filter(n => 
            n.titulo.toLowerCase().includes(textoeBusca) || 
            (n.conteudo && n.conteudo.toLowerCase().includes(textoeBusca))
        );
    }
    
    renderizarNoticias(noticiasFiltradas);
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit', 
        hour: '2-digit',
        minute: '2-digit'
    });
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