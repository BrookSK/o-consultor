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
// Config do editor. Usa JSON_HEX_TAG para escapar sinais de menor/maior e
// evitar que qualquer conteudo feche a tag de script no meio do bloco.
window.VIDEO_CONF = {
    conteudoId: <?= (int) $cont['id'] ?>,
    projetoId: <?= (int) $dados['projeto_id'] ?>,
    csrf: <?= json_encode(Csrf::token()) ?>,
    base: <?= json_encode(rtrim(APP_URL, '/')) ?>,
    estado: <?= json_encode($dados['estado'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    imagensPost: <?= json_encode(array_values($dados['imagens_post'] ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>,
    videoUrl: <?= json_encode($dados['video_url'] ?? '') ?>
};
console.log('[VIDEO] CONF', window.VIDEO_CONF && window.VIDEO_CONF.conteudoId, 'imgs=', (window.VIDEO_CONF.imagensPost||[]).length);
</script>
<script>
/* Editor de video embutido (sem dependencia de arquivo externo). */
var VE = {};
(function initState() {
    var conf = window.VIDEO_CONF || {};
    var estado = conf.estado || {};
    estado.imagens = estado.imagens || [];
    if ((!estado.imagens || estado.imagens.length === 0) && Array.isArray(conf.imagensPost) && conf.imagensPost.length > 0) {
        estado.imagens = conf.imagensPost.map(function (u) { return { url: u, duracao: 3, transicao: 'fade', movimento: 'zoom_in' }; });
    }
    estado.narracao = estado.narracao || { url: '', volume: 1, texto: '' };
    estado.musica = estado.musica || { url: '', volume: 0.5, loop: true, reduzir_na_narracao: true };
    estado.textos = estado.textos || [];
    estado.formato = estado.formato || { w: 1080, h: 1920, fps: 30 };
    if (typeof estado.transicao_velocidade !== 'number') estado.transicao_velocidade = 0.5;
    if (typeof estado.movimento_auto !== 'boolean') estado.movimento_auto = false;
    VE.conf = conf; VE.estado = estado; VE.clipe = 0; VE.imgCache = {};
    VE.tocando = false; VE.tempo = 0; VE.ultimoTs = 0; VE.pollTimer = null;
})();
var VE_MOVIMENTOS = [['estatico','Estatico'],['zoom_in','Zoom In'],['zoom_out','Zoom Out'],['esquerda_direita','Esquerda p/ Direita'],['direita_esquerda','Direita p/ Esquerda'],['cima_baixo','Cima p/ Baixo'],['baixo_cima','Baixo p/ Cima']];
var VE_TRANSICOES = [['fade','Fade'],['dissolver','Dissolver'],['slide_esquerda','Slide esquerda'],['slide_direita','Slide direita'],['zoom','Zoom'],['blur','Blur'],['nenhuma','Sem transicao']];
var VE_MOV_AUTO = ['zoom_in','esquerda_direita','zoom_out','direita_esquerda','cima_baixo','baixo_cima'];
function veEsc(s){return String(s==null?'':s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function veCarregarImg(url){return new Promise(function(resolve){if(VE.imgCache[url])return resolve(VE.imgCache[url]);var img=new Image();img.crossOrigin='anonymous';img.onload=function(){VE.imgCache[url]=img;resolve(img);};img.onerror=function(){resolve(null);};img.src=url;});}
function veMovimento(idx){if(VE.estado.movimento_auto)return VE_MOV_AUTO[idx%VE_MOV_AUTO.length];return (VE.estado.imagens[idx]&&VE.estado.imagens[idx].movimento)||'estatico';}
function veDuracaoTotal(){return VE.estado.imagens.reduce(function(s,c){return s+(parseFloat(c.duracao)||3);},0);}
function veRenderBiblioteca(){
    var wrap=document.getElementById('lista-imagens');if(!wrap)return;wrap.innerHTML='';
    VE.estado.imagens.forEach(function(c,i){
        var card=document.createElement('div');
        card.className='border rounded-lg p-2 bg-white cursor-move '+(i===VE.clipe?'ring-2 ring-primary':'');
        card.draggable=true;
        var movOpts=VE_MOVIMENTOS.map(function(m){return '<option value="'+m[0]+'"'+(c.movimento===m[0]?' selected':'')+'>'+m[1]+'</option>';}).join('');
        var trOpts=VE_TRANSICOES.map(function(t){return '<option value="'+t[0]+'"'+(c.transicao===t[0]?' selected':'')+'>'+t[1]+'</option>';}).join('');
        card.innerHTML='<div class="flex gap-2"><img src="'+veEsc(c.url)+'" class="w-14 h-14 object-cover rounded"><div class="flex-1 min-w-0"><div class="flex items-center justify-between"><span class="text-[11px] text-gray-500">Cena '+(i+1)+'</span><div class="flex gap-1"><button title="Duplicar" data-act="dup" class="text-gray-400 hover:text-primary text-xs">+</button><button title="Remover" data-act="del" class="text-gray-400 hover:text-red-500 text-xs">x</button></div></div><div class="flex items-center gap-1 mt-1"><input type="number" step="0.5" min="0.5" value="'+(parseFloat(c.duracao)||3)+'" data-act="dur" class="w-14 px-1 py-0.5 border rounded text-[11px]"><span class="text-[10px] text-gray-400">s</span></div><select data-act="mov" class="w-full mt-1 px-1 py-0.5 border rounded text-[11px]">'+movOpts+'</select><select data-act="tr" class="w-full mt-1 px-1 py-0.5 border rounded text-[11px]">'+trOpts+'</select></div></div>';
        card.addEventListener('click',function(){VE.clipe=i;veRenderTudo();});
        card.querySelector('[data-act="del"]').addEventListener('click',function(e){e.stopPropagation();VE.estado.imagens.splice(i,1);if(VE.clipe>=VE.estado.imagens.length)VE.clipe=Math.max(0,VE.estado.imagens.length-1);veRenderTudo();});
        card.querySelector('[data-act="dup"]').addEventListener('click',function(e){e.stopPropagation();VE.estado.imagens.splice(i+1,0,Object.assign({},c));veRenderTudo();});
        card.querySelector('[data-act="dur"]').addEventListener('change',function(e){c.duracao=Math.max(0.5,parseFloat(e.target.value)||3);veRenderTimeline();veRenderPreview();});
        card.querySelector('[data-act="mov"]').addEventListener('change',function(e){c.movimento=e.target.value;});
        card.querySelector('[data-act="tr"]').addEventListener('change',function(e){c.transicao=e.target.value;});
        card.addEventListener('dragstart',function(e){e.dataTransfer.setData('idx',i);});
        card.addEventListener('dragover',function(e){e.preventDefault();});
        card.addEventListener('drop',function(e){e.preventDefault();var from=parseInt(e.dataTransfer.getData('idx'),10);if(isNaN(from)||from===i)return;var mv=VE.estado.imagens.splice(from,1)[0];VE.estado.imagens.splice(i,0,mv);VE.clipe=i;veRenderTudo();});
        wrap.appendChild(card);
    });
}
function veRenderProps(){
    var box=document.getElementById('props-clipe');if(!box)return;var c=VE.estado.imagens[VE.clipe];
    if(!c){box.innerHTML='Selecione uma imagem para editar.';return;}
    var movOpts=VE_MOVIMENTOS.map(function(m){return '<option value="'+m[0]+'"'+(c.movimento===m[0]?' selected':'')+'>'+m[1]+'</option>';}).join('');
    var trOpts=VE_TRANSICOES.map(function(t){return '<option value="'+t[0]+'"'+(c.transicao===t[0]?' selected':'')+'>'+t[1]+'</option>';}).join('');
    box.innerHTML='<p class="font-medium text-gray-700 mb-2">Cena '+(VE.clipe+1)+'</p><label class="block text-xs text-gray-500 mb-1">Duracao (s)</label><input type="number" step="0.5" min="0.5" id="prop-dur" value="'+(parseFloat(c.duracao)||3)+'" class="w-full px-3 py-2 border rounded text-sm mb-2"><button id="prop-dur-todas" class="text-xs px-3 py-1.5 border border-primary/40 text-primary rounded hover:bg-primary/5 mb-3">Aplicar duracao para todas</button><label class="block text-xs text-gray-500 mb-1">Movimento</label><select id="prop-mov" class="w-full px-3 py-2 border rounded text-sm mb-2">'+movOpts+'</select><label class="block text-xs text-gray-500 mb-1">Transicao</label><select id="prop-tr" class="w-full px-3 py-2 border rounded text-sm mb-2">'+trOpts+'</select><button id="prop-tr-todas" class="text-xs px-3 py-1.5 border border-primary/40 text-primary rounded hover:bg-primary/5">Aplicar esta transicao em todas</button>';
    box.querySelector('#prop-dur').addEventListener('change',function(e){c.duracao=Math.max(0.5,parseFloat(e.target.value)||3);veRenderBiblioteca();veRenderTimeline();});
    box.querySelector('#prop-dur-todas').addEventListener('click',function(){var d=c.duracao;VE.estado.imagens.forEach(function(x){x.duracao=d;});veRenderTudo();});
    box.querySelector('#prop-mov').addEventListener('change',function(e){c.movimento=e.target.value;veRenderBiblioteca();});
    box.querySelector('#prop-tr').addEventListener('change',function(e){c.transicao=e.target.value;veRenderBiblioteca();});
    box.querySelector('#prop-tr-todas').addEventListener('click',function(){var t=c.transicao;VE.estado.imagens.forEach(function(x){x.transicao=t;});veRenderBiblioteca();});
}
function veRenderTimeline(){
    var tr=document.getElementById('track-imagens');
    if(tr){var total=veDuracaoTotal()||1;tr.innerHTML='';VE.estado.imagens.forEach(function(c,i){var w=Math.max(24,((parseFloat(c.duracao)||3)/total)*100);var el=document.createElement('div');el.className='h-8 rounded overflow-hidden shrink-0 '+(i===VE.clipe?'ring-2 ring-primary':'');el.style.width=w+'%';el.innerHTML='<img src="'+veEsc(c.url)+'" class="w-full h-full object-cover">';el.addEventListener('click',function(){VE.clipe=i;veRenderTudo();});tr.appendChild(el);});}
    var tn=document.getElementById('track-narracao');if(tn)tn.textContent=VE.estado.narracao.url?'Narracao carregada':'-';
    var tm=document.getElementById('track-musica');if(tm)tm.textContent=VE.estado.musica.url?'Musica carregada':'-';
    var tt=document.getElementById('tempo-total');if(tt)tt.textContent=veDuracaoTotal().toFixed(1)+'s';
}
function veClipeNoTempo(t){var acc=0;for(var i=0;i<VE.estado.imagens.length;i++){var d=parseFloat(VE.estado.imagens[i].duracao)||3;if(t<acc+d)return{idx:i,prog:(t-acc)/d};acc+=d;}return{idx:VE.estado.imagens.length-1,prog:1};}
function veRenderPreview(){
    var canvas=document.getElementById('preview-canvas');if(!canvas||VE.estado.imagens.length===0)return;var ctx=canvas.getContext('2d');
    var r=veClipeNoTempo(VE.tempo);var c=VE.estado.imagens[r.idx];
    veCarregarImg(c.url).then(function(img){
        var W=canvas.width,H=canvas.height;ctx.fillStyle='#000';ctx.fillRect(0,0,W,H);
        if(img){var ir=img.width/img.height,cr=W/H,dw,dh;if(ir>cr){dh=H;dw=H*ir;}else{dw=W;dh=W/ir;}var mov=veMovimento(r.idx),scale=1,ox=0,oy=0,prog=r.prog;if(mov==='zoom_in')scale=1+0.15*prog;else if(mov==='zoom_out')scale=1.15-0.15*prog;else if(mov==='esquerda_direita')ox=-(dw-W)*prog;else if(mov==='direita_esquerda')ox=-(dw-W)*(1-prog);else if(mov==='cima_baixo')oy=-(dh-H)*prog;else if(mov==='baixo_cima')oy=-(dh-H)*(1-prog);var sw=dw*scale,sh=dh*scale;ctx.drawImage(img,(W-sw)/2+ox,(H-sh)/2+oy,sw,sh);var vel=VE.estado.transicao_velocidade||0.5,d=parseFloat(c.duracao)||3;if((c.transicao||'fade')!=='nenhuma'&&prog*d<vel){ctx.fillStyle='rgba(0,0,0,'+(1-(prog*d)/vel)+')';ctx.fillRect(0,0,W,H);}}
        (VE.estado.textos||[]).forEach(function(t){var alvos=(t.imagens||'').split(',').map(function(x){return parseInt(x.trim(),10)-1;}).filter(function(x){return !isNaN(x);});if(alvos.length>0&&alvos.indexOf(r.idx)===-1)return;ctx.fillStyle=t.cor||'#fff';ctx.font='bold '+((t.tamanho||48)*W/1080)+'px system-ui, Arial';ctx.textAlign='center';var y=t.posicao==='topo'?H*0.12:(t.posicao==='centro'?H*0.5:H*0.88);ctx.fillText(t.frase||'',W/2,y);});
        var ta=document.getElementById('tempo-atual');if(ta)ta.textContent=VE.tempo.toFixed(1)+'s';
        var bp=document.getElementById('barra-progresso');if(bp)bp.value=Math.round((VE.tempo/(veDuracaoTotal()||1))*1000);
    });
}
function veLoop(ts){if(!VE.tocando)return;if(!VE.ultimoTs)VE.ultimoTs=ts;VE.tempo+=(ts-VE.ultimoTs)/1000;VE.ultimoTs=ts;if(VE.tempo>=veDuracaoTotal()){VE.tempo=0;VE.tocando=false;veBtnPlay();}veRenderPreview();if(VE.tocando)requestAnimationFrame(veLoop);}
function veBtnPlay(){var b=document.getElementById('btn-play');if(b)b.textContent=VE.tocando?'II':'>';}
function togglePlay(){VE.tocando=!VE.tocando;VE.ultimoTs=0;veBtnPlay();if(VE.tocando)requestAnimationFrame(veLoop);}
function reiniciarPreview(){VE.tempo=0;veRenderPreview();}
function avancarPreview(){VE.tempo=Math.min(veDuracaoTotal(),VE.tempo+1);veRenderPreview();}
function voltarPreview(){VE.tempo=Math.max(0,VE.tempo-1);veRenderPreview();}
function buscarTempo(v){VE.tempo=(v/1000)*(veDuracaoTotal()||1);veRenderPreview();}
function toggleMovAuto(el){VE.estado.movimento_auto=el.checked;veRenderBiblioteca();}
function setTransicaoVel(v){VE.estado.transicao_velocidade=parseFloat(v)||0.5;}
function sincronizarComNarracao(){var a=document.getElementById('narracao-audio');if(!VE.estado.narracao.url||!a||!a.duration||!isFinite(a.duration)){alert('Carregue uma narracao primeiro (e aguarde carregar).');return;}var n=VE.estado.imagens.length;if(n===0)return;var d=Math.max(0.5,a.duration/n);VE.estado.imagens.forEach(function(c){c.duracao=Math.round(d*10)/10;});veRenderTudo();}
function adicionarImagem(input){if(!input.files||!input.files[0])return;var fd=new FormData();fd.append('csrf_token',VE.conf.csrf);fd.append('conteudo_id',VE.conf.conteudoId);fd.append('imagem',input.files[0]);fetch(VE.conf.base+'/maquina-de-conteudo/video/upload-imagem',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(d.sucesso&&d.url){VE.estado.imagens.push({url:d.url,duracao:3,transicao:'fade',movimento:'zoom_in'});veRenderTudo();}else alert(d.erro||'Falha no upload.');}).catch(function(){alert('Erro de conexao.');});input.value='';}
function uploadAudio(input,tipo){
    if(!input.files||!input.files[0])return;var arq=input.files[0];
    var infoEl=document.getElementById(tipo==='musica'?'musica-info':'narracao-info');if(infoEl)infoEl.textContent='Enviando '+arq.name+'...';
    var fd=new FormData();fd.append('csrf_token',VE.conf.csrf);fd.append('conteudo_id',VE.conf.conteudoId);fd.append('tipo',tipo);fd.append('audio',arq);
    fetch(VE.conf.base+'/maquina-de-conteudo/video/upload-audio',{method:'POST',body:fd}).then(function(r){return r.text().then(function(t){return{r:r,t:t};});}).then(function(o){var d;try{d=JSON.parse(o.t);}catch(e){var msg=o.r.status===413?'Arquivo muito grande para o servidor.':(o.r.status===403?'Sessao expirada. Recarregue a pagina.':('Erro do servidor (HTTP '+o.r.status+').'));if(infoEl)infoEl.textContent='Falha no envio.';alert(msg);input.value='';return;}if(d.sucesso&&d.url){if(tipo==='narracao'){VE.estado.narracao.url=d.url;veAplicarNarracao();}else{VE.estado.musica.url=d.url;veAplicarMusica();}veRenderTimeline();salvarProjeto(false);}else{if(infoEl)infoEl.textContent='Falha no envio.';alert(d.erro||'Falha no upload.');}}).catch(function(e){if(infoEl)infoEl.textContent='Falha no envio.';alert('Erro de conexao: '+(e.message||e));});
    input.value='';
}
function veAplicarNarracao(){var info=document.getElementById('narracao-info'),ctr=document.getElementById('narracao-controles'),au=document.getElementById('narracao-audio');if(VE.estado.narracao.url){if(info)info.textContent='Narracao carregada.';if(ctr)ctr.classList.remove('hidden');if(au)au.src=VE.estado.narracao.url;}else{if(info)info.textContent='Sem narracao.';if(ctr)ctr.classList.add('hidden');}}
function veAplicarMusica(){var info=document.getElementById('musica-info'),ctr=document.getElementById('musica-controles'),au=document.getElementById('musica-audio');if(VE.estado.musica.url){if(info)info.textContent='Musica carregada.';if(ctr)ctr.classList.remove('hidden');if(au)au.src=VE.estado.musica.url;}else{if(info)info.textContent='Sem musica.';if(ctr)ctr.classList.add('hidden');}}
function setNarrVol(v){VE.estado.narracao.volume=parseFloat(v);}
function setMusVol(v){VE.estado.musica.volume=parseFloat(v);}
function setMusLoop(v){VE.estado.musica.loop=v;}
function setMusReduz(v){VE.estado.musica.reduzir_na_narracao=v;}
function removerNarracao(){VE.estado.narracao.url='';veAplicarNarracao();veRenderTimeline();}
function removerMusica(){VE.estado.musica.url='';veAplicarMusica();veRenderTimeline();}
function abrirModalVoz(){var t=document.getElementById('voz-texto');if(t)t.value=VE.estado.narracao.texto||'';var m=document.getElementById('modal-voz');if(m)m.classList.remove('hidden');}
function fecharModalVoz(){var m=document.getElementById('modal-voz');if(m)m.classList.add('hidden');}
function carregarVozes(btn){if(btn)btn.disabled=true;fetch(VE.conf.base+'/maquina-de-conteudo/video/vozes').then(function(r){return r.json();}).then(function(d){var sel=document.getElementById('voz-select');if(d.sucesso&&sel){sel.innerHTML='<option value="">Padrao</option>'+(d.vozes||[]).map(function(v){return '<option value="'+v.voice_id+'">'+veEsc(v.name)+'</option>';}).join('');}else alert(d.erro||'Nao foi possivel carregar as vozes.');}).catch(function(){alert('Erro de conexao.');}).finally(function(){if(btn)btn.disabled=false;});}
function gerarVoz(btn){var texto=(document.getElementById('voz-texto').value||'').trim();var voz=document.getElementById('voz-select').value;if(!texto){alert('Escreva o texto da narracao.');return;}if(btn){btn.disabled=true;btn.textContent='Gerando...';}var fd=new FormData();fd.append('csrf_token',VE.conf.csrf);fd.append('conteudo_id',VE.conf.conteudoId);fd.append('texto',texto);fd.append('voz_id',voz);fetch(VE.conf.base+'/maquina-de-conteudo/video/gerar-narracao',{method:'POST',body:fd}).then(function(r){return r.text().then(function(t){return{r:r,t:t};});}).then(function(o){var d;try{d=JSON.parse(o.t);}catch(e){alert('Erro do servidor (HTTP '+o.r.status+').');return;}if(d.sucesso&&d.url){VE.estado.narracao.url=d.url;VE.estado.narracao.texto=texto;VE.estado.narracao.voz_id=voz;VE.estado.narracao.versoes=VE.estado.narracao.versoes||[];VE.estado.narracao.versoes.push(d.url);veAplicarNarracao();veRenderTimeline();fecharModalVoz();if(typeof Toast!=='undefined')Toast.sucesso('Narracao gerada!');}else alert(d.erro||'Falha ao gerar voz.');}).catch(function(){alert('Erro de conexao.');}).finally(function(){if(btn){btn.disabled=false;btn.textContent='Gerar';}});}
function adicionarTexto(){var frase=(document.getElementById('txt-frase').value||'').trim();if(!frase)return;VE.estado.textos.push({frase:frase,cor:document.getElementById('txt-cor').value,tamanho:parseInt(document.getElementById('txt-tamanho').value,10)||48,posicao:document.getElementById('txt-posicao').value,imagens:(document.getElementById('txt-imagens').value||'').trim()});document.getElementById('txt-frase').value='';veRenderTextos();veRenderPreview();}
function veRenderTextos(){var wrap=document.getElementById('lista-textos');if(!wrap)return;wrap.innerHTML=VE.estado.textos.map(function(t,i){return '<div class="flex items-center justify-between border rounded px-2 py-1"><span class="truncate">'+veEsc(t.frase)+'</span><button data-i="'+i+'" class="text-red-500">x</button></div>';}).join('');wrap.querySelectorAll('button[data-i]').forEach(function(b){b.addEventListener('click',function(){VE.estado.textos.splice(parseInt(b.dataset.i,10),1);veRenderTextos();veRenderPreview();});});}
function salvarProjeto(avisar){var fd=new FormData();fd.append('csrf_token',VE.conf.csrf);fd.append('conteudo_id',VE.conf.conteudoId);fd.append('estado',JSON.stringify(VE.estado));return fetch(VE.conf.base+'/maquina-de-conteudo/video/salvar',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(avisar){if(d.sucesso){if(typeof Toast!=='undefined')Toast.sucesso('Projeto salvo!');else alert('Projeto salvo!');}else alert(d.erro||'Erro ao salvar.');}return d.sucesso;}).catch(function(){if(avisar)alert('Erro de conexao.');return false;});}
function exportarVideo(btn){if(VE.estado.imagens.length===0){alert('Nenhuma imagem no video.');return;}if(btn){btn.disabled=true;btn.textContent='Enviando...';}var fd=new FormData();fd.append('csrf_token',VE.conf.csrf);fd.append('conteudo_id',VE.conf.conteudoId);fd.append('estado',JSON.stringify(VE.estado));fetch(VE.conf.base+'/maquina-de-conteudo/video/exportar',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(!d.sucesso){alert(d.erro||'Falha ao enfileirar.');return;}var box=document.getElementById('box-exportacao');if(box)box.classList.remove('hidden');veIniciarPolling();}).catch(function(){alert('Erro de conexao.');}).finally(function(){if(btn){btn.disabled=false;btn.textContent='Exportar Video';}});}
function veIniciarPolling(){if(VE.pollTimer)clearInterval(VE.pollTimer);VE.pollTimer=setInterval(function(){fetch(VE.conf.base+'/maquina-de-conteudo/video/processar-bg?_='+Date.now()).catch(function(){});fetch(VE.conf.base+'/maquina-de-conteudo/video/status?conteudo_id='+VE.conf.conteudoId+'&_='+Date.now()).then(function(r){return r.json();}).then(function(d){if(!d.sucesso||d.status==='nenhum')return;var barra=document.getElementById('exp-barra'),etapa=document.getElementById('exp-etapa');if(barra)barra.style.width=(d.progresso||0)+'%';if(etapa)etapa.textContent=(d.etapa||'')+' ('+(d.progresso||0)+'%)';if(d.status==='concluido'&&d.video_url){clearInterval(VE.pollTimer);VE.pollTimer=null;if(etapa)etapa.textContent='Concluido!';var res=document.getElementById('exp-resultado');if(res)res.classList.remove('hidden');var v=document.getElementById('exp-video');if(v)v.src=d.video_url;var dl=document.getElementById('exp-download');if(dl)dl.href=d.video_url;if(typeof Toast!=='undefined')Toast.sucesso('Video pronto!');}else if(d.status==='erro'){clearInterval(VE.pollTimer);VE.pollTimer=null;if(etapa)etapa.textContent='Erro: '+(d.mensagem||'falha na renderizacao');}}).catch(function(){});},3000);}
function veRenderTudo(){veRenderBiblioteca();veRenderProps();veRenderTimeline();veRenderTextos();veRenderPreview();}
(function veInit(){try{console.log('[VIDEO] editor iniciado. imagens=',(VE.estado.imagens||[]).length);veAplicarNarracao();veAplicarMusica();veRenderTudo();setInterval(function(){salvarProjeto(false);},20000);if(VE.conf.videoUrl){var box=document.getElementById('box-exportacao');if(box)box.classList.remove('hidden');var etapa=document.getElementById('exp-etapa');if(etapa)etapa.textContent='Ultima exportacao';var res=document.getElementById('exp-resultado');if(res)res.classList.remove('hidden');var v=document.getElementById('exp-video');if(v)v.src=VE.conf.videoUrl;var dl=document.getElementById('exp-download');if(dl)dl.href=VE.conf.videoUrl;}}catch(e){console.error('[VIDEO] erro na init:',e);}})();
</script>
<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
