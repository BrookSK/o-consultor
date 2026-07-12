<?php $tituloPagina = 'Criar Vídeo'; ?>
<?php ob_start(); ?>
<?php $cont = $dados['conteudo']; ?>

<nav class="mb-4">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/maquina-de-conteudo" class="hover:text-primary">Máquina de Conteúdo</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/maquina-de-conteudo/editar/<?= (int) $cont['id'] ?>" class="hover:text-primary">Editar Post</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Criar Vídeo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-gray-800">🎬 Criador de Vídeo — <?= htmlspecialchars($cont['tema']) ?></h1>
    <div class="flex items-center gap-2">
        <button onclick="salvarProjeto(true)" id="btn-salvar-proj" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">💾 Salvar</button>
        <button onclick="exportarVideo(this)" id="btn-exportar-video" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:bg-orange-700">⬇️ Exportar Vídeo</button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <!-- Biblioteca de imagens -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700">🖼️ Imagens do post</h4>
            </div>
            <p class="text-[11px] text-gray-400 mb-2">As imagens são as que já foram geradas para este post.</p>
            <div id="lista-imagens" class="space-y-2 max-h-[520px] overflow-y-auto">
                <?php // Fallback server-side: mostra as imagens mesmo se o JS não rodar. ?>
                <?php if (empty($dados['imagens_post'])): ?>
                <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded p-2">Este post ainda não tem imagens geradas. Gere as imagens na tela de edição do post antes de criar o vídeo.</p>
                <?php else: ?>
                <?php foreach ($dados['imagens_post'] as $ii => $iu): ?>
                <div class="border rounded-lg p-2 bg-white flex gap-2 items-center">
                    <img src="<?= htmlspecialchars($iu) ?>" class="w-14 h-14 object-cover rounded">
                    <span class="text-[11px] text-gray-500">Cena <?= $ii + 1 ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pré-visualização -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">▶️ Pré-visualização</h4>
            <div class="flex justify-center">
                <canvas id="preview-canvas" width="270" height="480" class="rounded-lg bg-gray-900 max-w-full"></canvas>
            </div>
            <div class="mt-3">
                <input type="range" id="barra-progresso" min="0" max="1000" value="0" class="w-full" oninput="buscarTempo(this.value)">
                <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
                    <span id="tempo-atual">0.0s</span>
                    <div class="flex items-center gap-2">
                        <button onclick="reiniciarPreview()" class="px-2 py-1 border rounded hover:bg-gray-50" title="Reiniciar">⏮</button>
                        <button onclick="voltarPreview()" class="px-2 py-1 border rounded hover:bg-gray-50" title="Voltar">⏪</button>
                        <button onclick="togglePlay()" id="btn-play" class="px-3 py-1 bg-primary text-white rounded" title="Play/Pause">▶</button>
                        <button onclick="avancarPreview()" class="px-2 py-1 border rounded hover:bg-gray-50" title="Avançar">⏩</button>
                    </div>
                    <span id="tempo-total">0.0s</span>
                </div>
            </div>
        </div>

        <!-- Mini timeline -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">🎞️ Timeline</h4>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-500 w-16 shrink-0">Imagens</span>
                    <div id="track-imagens" class="flex-1 flex gap-1 bg-gray-100 rounded p-1 min-h-[36px] overflow-x-auto"></div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-500 w-16 shrink-0">Narração</span>
                    <div id="track-narracao" class="flex-1 bg-gray-100 rounded p-1 min-h-[28px] text-[11px] text-gray-400 flex items-center px-2">—</div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-gray-500 w-16 shrink-0">Música</span>
                    <div id="track-musica" class="flex-1 bg-gray-100 rounded p-1 min-h-[28px] text-[11px] text-gray-400 flex items-center px-2">—</div>
                </div>
            </div>
            <button onclick="sincronizarComNarracao()" class="mt-3 text-xs px-3 py-1.5 border border-primary/40 text-primary rounded hover:bg-primary/5">🔗 Sincronizar imagens com a narração</button>
        </div>
    </div>

    <!-- Painel de propriedades / abas -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4" x-data="{ aba: 'clipe' }">
            <div class="flex gap-1 border-b border-gray-200 mb-3 text-sm">
                <button @click="aba='clipe'" :class="aba==='clipe'?'border-b-2 border-primary text-primary font-medium':'text-gray-500'" class="px-3 py-2">Clipe</button>
                <button @click="aba='narracao'" :class="aba==='narracao'?'border-b-2 border-primary text-primary font-medium':'text-gray-500'" class="px-3 py-2">Narração</button>
                <button @click="aba='musica'" :class="aba==='musica'?'border-b-2 border-primary text-primary font-medium':'text-gray-500'" class="px-3 py-2">Música</button>
                <button @click="aba='texto'" :class="aba==='texto'?'border-b-2 border-primary text-primary font-medium':'text-gray-500'" class="px-3 py-2">Texto</button>
            </div>

            <!-- ABA CLIPE -->
            <div x-show="aba==='clipe'" class="space-y-3">
                <div id="props-clipe" class="text-sm text-gray-500">Selecione uma imagem para editar.</div>
                <div class="border-t pt-3">
                    <label class="block text-xs text-gray-500 mb-1">Movimento automático</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="mov-auto" onchange="toggleMovAuto(this)"> Alternar movimentos automaticamente</label>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Velocidade da transição (s)</label>
                    <input type="number" id="transicao-velocidade" step="0.1" min="0.1" max="2" value="0.5" onchange="setTransicaoVel(this.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                </div>
            </div>

            <!-- ABA NARRAÇÃO -->
            <div x-show="aba==='narracao'" class="space-y-3" style="display:none;">
                <label class="px-3 py-2 bg-gray-100 rounded text-sm cursor-pointer inline-block">📤 Enviar áudio
                    <input type="file" accept=".mp3,.wav,.aac,.m4a,.ogg" class="hidden" onchange="uploadAudio(this,'narracao')">
                </label>
                <button onclick="abrirModalVoz()" class="px-3 py-2 bg-primary text-white rounded text-sm">🎙️ Gerar Voz (ElevenLabs)</button>
                <div id="narracao-info" class="text-xs text-gray-500">Sem narração.</div>
                <div id="narracao-controles" class="space-y-2 hidden">
                    <audio id="narracao-audio" controls class="w-full"></audio>
                    <label class="block text-xs text-gray-500">Volume</label>
                    <input type="range" min="0" max="1" step="0.05" value="1" oninput="setNarrVol(this.value)" class="w-full">
                    <button onclick="removerNarracao()" class="text-xs text-red-600 hover:underline">Remover narração</button>
                </div>
            </div>

            <!-- ABA MÚSICA -->
            <div x-show="aba==='musica'" class="space-y-3" style="display:none;">
                <label class="px-3 py-2 bg-gray-100 rounded text-sm cursor-pointer inline-block">📤 Enviar música
                    <input type="file" accept=".mp3,.wav,.aac,.m4a" class="hidden" onchange="uploadAudio(this,'musica')">
                </label>
                <div id="musica-info" class="text-xs text-gray-500">Sem música.</div>
                <div id="musica-controles" class="space-y-2 hidden">
                    <audio id="musica-audio" controls class="w-full"></audio>
                    <label class="block text-xs text-gray-500">Volume</label>
                    <input type="range" min="0" max="1" step="0.05" value="0.5" oninput="setMusVol(this.value)" class="w-full">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="mus-loop" checked onchange="setMusLoop(this.checked)"> Repetir (loop)</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="mus-reduz" checked onchange="setMusReduz(this.checked)"> Reduzir música durante a narração</label>
                    <button onclick="removerMusica()" class="text-xs text-red-600 hover:underline">Remover música</button>
                </div>
            </div>

            <!-- ABA TEXTO -->
            <div x-show="aba==='texto'" class="space-y-3" style="display:none;">
                <input type="text" id="txt-frase" placeholder="Frase" class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                <div class="grid grid-cols-2 gap-2">
                    <input type="color" id="txt-cor" value="#FFFFFF" class="w-full h-9 border rounded">
                    <input type="number" id="txt-tamanho" value="48" min="10" max="200" class="w-full px-2 py-2 border rounded text-sm" placeholder="Tamanho">
                </div>
                <select id="txt-posicao" class="w-full px-3 py-2 border rounded text-sm">
                    <option value="topo">Topo</option>
                    <option value="centro">Centro</option>
                    <option value="base" selected>Base</option>
                </select>
                <label class="block text-xs text-gray-500">Aparece nas imagens (nº separados por vírgula, vazio = todas)</label>
                <input type="text" id="txt-imagens" placeholder="ex.: 1,2" class="w-full px-3 py-2 border rounded text-sm">
                <button onclick="adicionarTexto()" class="px-3 py-2 bg-primary text-white rounded text-sm">+ Adicionar frase</button>
                <div id="lista-textos" class="space-y-1 text-xs"></div>
            </div>
        </div>

        <!-- Status de exportação -->
        <div id="box-exportacao" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mt-4 hidden">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Exportação</h4>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden"><div id="exp-barra" class="bg-accent h-3" style="width:0%"></div></div>
            <p id="exp-etapa" class="text-xs text-gray-500 mt-2">Na fila...</p>
            <div id="exp-resultado" class="mt-3 hidden">
                <video id="exp-video" controls class="w-full rounded-lg bg-black"></video>
                <a id="exp-download" href="#" download class="mt-2 inline-block px-3 py-2 bg-primary text-white rounded text-sm">⬇️ Baixar vídeo</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gerar Voz -->
<div id="modal-voz" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">🎙️ Gerar Voz (ElevenLabs)</h3>
        <label class="block text-xs text-gray-500 mb-1">Texto da narração</label>
        <textarea id="voz-texto" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded text-sm mb-3"></textarea>
        <label class="block text-xs text-gray-500 mb-1">Voz</label>
        <div class="flex gap-2 mb-3">
            <select id="voz-select" class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm"><option value="">Padrão</option></select>
            <button onclick="carregarVozes(this)" class="px-3 py-2 border rounded text-sm hover:bg-gray-50" title="Carregar vozes">🔄</button>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="fecharModalVoz()" class="px-4 py-2 border rounded text-sm">Cancelar</button>
            <button onclick="gerarVoz(this)" id="btn-gerar-voz" class="px-4 py-2 bg-primary text-white rounded text-sm">Gerar</button>
        </div>
    </div>
</div>

<script>
const VIDEO_CONF = {
    conteudoId: <?= (int) $cont['id'] ?>,
    projetoId: <?= (int) $dados['projeto_id'] ?>,
    csrf: '<?= Csrf::token() ?>',
    base: '<?= APP_URL ?>',
    estado: <?= json_encode($dados['estado'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    imagensPost: <?= json_encode(array_values($dados['imagens_post'] ?? []), JSON_UNESCAPED_SLASHES) ?>,
    videoUrl: <?= json_encode($dados['video_url'] ?? '') ?>,
};
</script>
<script>
<?php
    $jsVideo = @file_get_contents(PUBLIC_PATH . '/assets/js/video-editor.js');
    if ($jsVideo === false || trim($jsVideo) === '') {
        echo "console.error('[VIDEO] arquivo video-editor.js não encontrado em disco');";
        echo "alert('Editor de vídeo indisponível: arquivo de script ausente no servidor. Publique public/assets/js/video-editor.js');";
    } else {
        echo $jsVideo;
    }
?>
</script>
<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
