<?php $tituloPagina = 'Manual Operacional Completo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Manual Completo</li>
    </ol>
</nav>

<div class="max-w-5xl mx-auto">
    <!-- Header do Manual -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-6">
        <div class="text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <span class="text-4xl">📚</span>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manual Operacional Completo</h1>
                    <p class="text-xl text-gray-600 mt-1"><?= htmlspecialchars($dados['empresa']['nome']) ?></p>
                </div>
            </div>
            
            <div class="flex items-center justify-center gap-6 mt-6 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span>Versão <?= htmlspecialchars($dados['manual']['versao']) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span>Gerado em <?= date('d/m/Y H:i', strtotime($dados['manual']['criado_em'])) ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    <span>Baseado no Diagnóstico #<?= $dados['diagnostico']['id'] ?></span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-center gap-4 mt-8">
            <button onclick="exportarPDF()" class="px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">
                📄 Exportar PDF
            </button>
            <button onclick="compartilharManual()" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                📤 Compartilhar
            </button>
            <button onclick="imprimirManual()" class="px-6 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700">
                🖨️ Imprimir
            </button>
            <a href="<?= APP_URL ?>/manual-operacional" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                ← Voltar
            </a>
        </div>
    </div>

    <!-- Badge da Nova Arquitetura -->
    <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🧠</span>
            <div>
                <h3 class="font-semibold text-purple-800">Gerado com Nova Arquitetura Profunda</h3>
                <p class="text-sm text-purple-600 mt-1">
                    Este manual foi criado usando a nova arquitetura de 3 etapas: análise organizacional específica → 
                    geração individual e profunda de cada SOP → consolidação final inteligente.
                </p>
            </div>
        </div>
    </div>

    <!-- Conteúdo do Manual -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Barra de Navegação Interna -->
        <div class="bg-gray-50 border-b border-gray-200 p-4">
            <div class="flex items-center gap-4">
                <button onclick="toggleIndice()" class="flex items-center gap-2 px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                    📋 Índice
                </button>
                <button onclick="buscarNoManual()" class="flex items-center gap-2 px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                    🔍 Buscar
                </button>
                <div class="text-sm text-gray-500">
                    <span id="contador-palavras"><?= str_word_count($dados['manual']['conteudo_completo']) ?> palavras</span> • 
                    <span><?= number_format(strlen($dados['manual']['conteudo_completo']) / 1000, 1) ?>KB</span>
                </div>
            </div>
        </div>

        <!-- Índice (recolhível) -->
        <div id="indice-manual" class="hidden bg-blue-50 border-b border-blue-200 p-6">
            <h4 class="font-semibold text-blue-800 mb-3">Índice do Manual</h4>
            <div id="lista-indice" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Barra de Busca -->
        <div id="busca-manual" class="hidden bg-yellow-50 border-b border-yellow-200 p-4">
            <div class="flex items-center gap-3">
                <input type="text" id="input-busca" placeholder="Buscar no manual..." 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                <button onclick="executarBusca()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary-700">
                    Buscar
                </button>
                <button onclick="limparBusca()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Limpar
                </button>
            </div>
            <div id="resultados-busca" class="mt-3 text-sm text-gray-600"></div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="p-8">
            <div id="conteudo-manual" class="prose prose-lg max-w-none">
                <?php
                // Converter Markdown para HTML se necessário
                $conteudo = $dados['manual']['conteudo_completo'];
                
                // Verificar se é Markdown (contém # headers)
                if (strpos($conteudo, '#') !== false) {
                    // Conversão simples de Markdown para HTML
                    $conteudo = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $conteudo);
                    $conteudo = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $conteudo);
                    $conteudo = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $conteudo);
                    $conteudo = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $conteudo);
                    $conteudo = preg_replace('/^\*\*(.+)\*\*$/m', '<strong>$1</strong>', $conteudo);
                    $conteudo = preg_replace('/^- (.+)$/m', '<li>$1</li>', $conteudo);
                    $conteudo = preg_replace('/(\n<li>.*<\/li>)\n(?!<li>)/s', '<ul>$1</ul>', $conteudo);
                    $conteudo = str_replace('---', '<hr>', $conteudo);
                    $conteudo = nl2br($conteudo);
                }
                
                echo $conteudo;
                ?>
            </div>
        </div>
    </div>

    <!-- Footer do Manual -->
    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 mt-6 text-center text-sm text-gray-500">
        <p><strong>Manual Operacional Completo</strong> - <?= htmlspecialchars($dados['empresa']['nome']) ?></p>
        <p class="mt-1">
            Gerado automaticamente pelo sistema O Consultor em <?= date('d/m/Y H:i', strtotime($dados['manual']['criado_em'])) ?>
            • Versão <?= htmlspecialchars($dados['manual']['versao']) ?>
        </p>
        <p class="mt-2 text-xs">
            Este manual foi criado com base no diagnóstico específico da empresa e contém procedimentos 
            personalizados para o nicho de atuação e realidade organizacional.
        </p>
    </div>
</div>

<script>
// Gerar índice automaticamente
document.addEventListener('DOMContentLoaded', function() {
    gerarIndice();
});

function gerarIndice() {
    const conteudo = document.getElementById('conteudo-manual');
    const headers = conteudo.querySelectorAll('h1, h2, h3, h4');
    const listaIndice = document.getElementById('lista-indice');
    
    if (headers.length === 0) return;
    
    let indiceHTML = '';
    headers.forEach((header, index) => {
        const id = `secao-${index}`;
        header.id = id;
        
        const nivel = header.tagName.toLowerCase();
        const texto = header.textContent;
        const classe = {
            'h1': 'font-semibold text-blue-800',
            'h2': 'font-medium text-blue-700 ml-2',
            'h3': 'text-blue-600 ml-4',
            'h4': 'text-blue-500 ml-6'
        }[nivel];
        
        indiceHTML += `<a href="#${id}" class="${classe} block py-1 hover:underline">${texto}</a>`;
    });
    
    listaIndice.innerHTML = indiceHTML;
}

function toggleIndice() {
    const indice = document.getElementById('indice-manual');
    indice.classList.toggle('hidden');
}

function buscarNoManual() {
    const busca = document.getElementById('busca-manual');
    busca.classList.toggle('hidden');
    
    if (!busca.classList.contains('hidden')) {
        document.getElementById('input-busca').focus();
    }
}

function executarBusca() {
    const termo = document.getElementById('input-busca').value.trim().toLowerCase();
    if (!termo) return;
    
    const conteudo = document.getElementById('conteudo-manual');
    const texto = conteudo.textContent.toLowerCase();
    const resultados = document.getElementById('resultados-busca');
    
    // Contar ocorrências
    const ocorrencias = (texto.match(new RegExp(termo, 'g')) || []).length;
    
    if (ocorrencias > 0) {
        resultados.innerHTML = `✅ Encontradas ${ocorrencias} ocorrência(s) de "${termo}"`;
        
        // Destacar no texto (implementação básica)
        highlightText(termo);
    } else {
        resultados.innerHTML = `❌ Nenhuma ocorrência encontrada para "${termo}"`;
    }
}

function highlightText(termo) {
    const conteudo = document.getElementById('conteudo-manual');
    const walker = document.createTreeWalker(
        conteudo,
        NodeFilter.SHOW_TEXT,
        null,
        false
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        const parent = textNode.parentNode;
        if (parent.tagName !== 'SCRIPT' && parent.tagName !== 'STYLE') {
            const text = textNode.textContent;
            const regex = new RegExp(`(${termo})`, 'gi');
            if (regex.test(text)) {
                const highlightedText = text.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
                const wrapper = document.createElement('span');
                wrapper.innerHTML = highlightedText;
                parent.replaceChild(wrapper, textNode);
            }
        }
    });
}

function limparBusca() {
    document.getElementById('input-busca').value = '';
    document.getElementById('resultados-busca').innerHTML = '';
    
    // Remover highlights
    const marks = document.querySelectorAll('#conteudo-manual mark');
    marks.forEach(mark => {
        const parent = mark.parentNode;
        parent.replaceChild(document.createTextNode(mark.textContent), mark);
        parent.normalize();
    });
}

function exportarPDF() {
    // Implementação futura: gerar PDF do manual
    alert('Funcionalidade de exportar PDF será implementada em breve.');
}

function compartilharManual() {
    if (navigator.share) {
        navigator.share({
            title: 'Manual Operacional - <?= htmlspecialchars($dados['empresa']['nome']) ?>',
            text: 'Manual Operacional Completo gerado pelo sistema O Consultor',
            url: window.location.href
        });
    } else {
        // Fallback: copiar link
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copiado para a área de transferência!');
        });
    }
}

function imprimirManual() {
    window.print();
}

// Navegação suave para âncoras
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const target = document.querySelector(e.target.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
});

// Busca com Enter
document.getElementById('input-busca')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        executarBusca();
    }
});
</script>

<style>
/* Estilos para impressão */
@media print {
    .no-print, nav, button { display: none !important; }
    .bg-white { background: white !important; }
    .shadow-sm { box-shadow: none !important; }
    .border { border: 1px solid #ddd !important; }
    body { font-size: 12pt; }
    h1 { font-size: 18pt; }
    h2 { font-size: 16pt; }
    h3 { font-size: 14pt; }
}

/* Estilos para o conteúdo */
.prose {
    line-height: 1.7;
}

.prose h1 {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 3px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.prose h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #4b5563;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.prose ul {
    list-style: disc;
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

.prose li {
    margin-bottom: 0.25rem;
}

.prose hr {
    border: none;
    border-top: 2px solid #e5e7eb;
    margin: 2rem 0;
}
</style>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>