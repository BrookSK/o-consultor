/**
 * O Consultor — JavaScript Principal (Polimento Final)
 * Toast, Loading, API, Modal, Utils, Accessibility
 */
'use strict';

// ===== TOAST (canto superior direito) =====
const Toast = {
    container: null,
    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed top-20 right-4 z-[9999] space-y-2';
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-label', 'Notificações');
            document.body.appendChild(this.container);
        }
    },
    show(msg, tipo = 'info', duracao = 4000) {
        if (!this.container) this.init();
        const cores = { sucesso:'bg-green-600', erro:'bg-red-600', aviso:'bg-yellow-600', info:'bg-blue-600' };
        const icones = { sucesso:'✓', erro:'✗', aviso:'⚠', info:'ℹ' };
        const el = document.createElement('div');
        el.className = `${cores[tipo]||cores.info} text-white px-4 py-3 rounded-lg shadow-lg text-sm max-w-sm toast-enter flex items-center gap-2`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `<span class="font-bold">${icones[tipo]||'ℹ'}</span><span class="flex-1">${msg}</span><button onclick="this.parentElement.remove()" class="text-white/70 hover:text-white text-lg ml-2" aria-label="Fechar">&times;</button>`;
        this.container.appendChild(el);
        setTimeout(() => { el.classList.replace('toast-enter','toast-exit'); setTimeout(() => el.remove(), 300); }, duracao);
    },
    sucesso(m) { this.show(m, 'sucesso'); },
    erro(m) { this.show(m, 'erro', 6000); },
    aviso(m) { this.show(m, 'aviso', 5000); },
    info(m) { this.show(m, 'info'); },
};

// ===== LOADING OVERLAY =====
const Loading = {
    overlay: null,
    show(mensagem = 'Carregando...', submensagem = '') {
        if (this.overlay) this.hide();
        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay';
        this.overlay.setAttribute('role', 'status');
        this.overlay.setAttribute('aria-live', 'polite');
        this.overlay.innerHTML = `<div class="spinner-lg"></div><p>${mensagem}</p>${submensagem ? '<p class="loading-sub">'+submensagem+'</p>' : ''}`;
        document.body.appendChild(this.overlay);
    },
    update(mensagem, submensagem = '') {
        if (this.overlay) {
            this.overlay.querySelector('p').textContent = mensagem;
            const sub = this.overlay.querySelector('.loading-sub');
            if (sub && submensagem) sub.textContent = submensagem;
        }
    },
    hide() {
        if (this.overlay) { this.overlay.remove(); this.overlay = null; }
    },
    // Skeleton para containers
    skeleton(elementId, linhas = 4) {
        const el = document.getElementById(elementId);
        if (el) {
            el.dataset.originalContent = el.innerHTML;
            el.innerHTML = Array(linhas).fill('<div class="skeleton h-4 w-full mb-3"></div>').join('');
        }
    },
    removeSkeleton(elementId) {
        const el = document.getElementById(elementId);
        if (el && el.dataset.originalContent) {
            el.innerHTML = el.dataset.originalContent;
            delete el.dataset.originalContent;
        }
    }
};

// ===== API HELPER (fetch com tratamento de erro) =====
const Api = {
    async post(url, dados, opcoes = {}) {
        const formData = dados instanceof FormData ? dados : this._toFormData(dados);
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            if (res.status === 403) { Toast.erro('Sessão expirada. Faça login novamente.'); setTimeout(() => window.location.href = '/login', 2000); return null; }
            if (res.status === 404) { Toast.erro('Recurso não encontrado.'); return null; }
            if (!res.ok) { Toast.erro('Erro no servidor. Tente novamente.'); return null; }
            return await res.json();
        } catch (e) {
            if (opcoes.silencioso) return null;
            Toast.erro('Erro de conexão. Verifique sua internet.');
            return null;
        }
    },
    async get(url) {
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return null;
            return await res.json();
        } catch (e) { return null; }
    },
    _toFormData(obj) {
        const fd = new FormData();
        for (const [k, v] of Object.entries(obj)) fd.append(k, v);
        return fd;
    }
};

// ===== MODAL HELPER =====
const Modal = {
    abrir(id) { const m = document.getElementById(id); if (m) { m.classList.remove('hidden'); m.setAttribute('aria-hidden','false'); } },
    fechar(id) { const m = document.getElementById(id); if (m) { m.classList.add('hidden'); m.setAttribute('aria-hidden','true'); } },
};

// ===== UTILS =====
const Utils = {
    formatarData(str) { return new Date(str).toLocaleDateString('pt-BR'); },
    debounce(fn, ms = 300) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; },
    async copiar(texto) { try { await navigator.clipboard.writeText(texto); Toast.sucesso('Copiado!'); } catch { Toast.erro('Não foi possível copiar.'); } },
    // Contador animado para KPI
    animarContador(el, valorFinal, duracao = 800) {
        const inicio = performance.now();
        const valorInicial = 0;
        const isNumero = !isNaN(parseFloat(valorFinal));
        if (!isNumero) { el.textContent = valorFinal; return; }
        const num = parseFloat(valorFinal.replace(/[^\d.,]/g, ''));
        const prefixo = valorFinal.replace(/[\d.,]+.*/, '');
        const sufixo = valorFinal.replace(/.*[\d.,]+/, '');
        function frame(agora) {
            const progresso = Math.min((agora - inicio) / duracao, 1);
            const valor = Math.round(valorInicial + (num - valorInicial) * progresso);
            el.textContent = prefixo + valor.toLocaleString('pt-BR') + sufixo;
            if (progresso < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }
};

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    Toast.init();

    // Fechar modais com ESC
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-"]:not(.hidden)').forEach(m => m.classList.add('hidden'));
        }
    });

    // Auto-dismiss de flash messages
    document.querySelectorAll('[role="alert"]').forEach(el => {
        setTimeout(() => { el.style.transition = 'opacity 0.3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 5000);
    });

    // Animar barras de progresso
    document.querySelectorAll('[style*="width:"][class*="rounded-full"]').forEach(el => {
        el.classList.add('progress-animated');
    });

    // Lazy loading de imagens
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        if (img.complete) img.classList.add('loaded');
        else img.addEventListener('load', () => img.classList.add('loaded'));
    });

    // Badge pulse se alertas críticos
    const badge = document.querySelector('[x-text="notifCount"]');
    if (badge && parseInt(badge.textContent) > 0) {
        badge.parentElement?.classList.add('badge-pulse');
    }

    // Formulários: prevenção de double-submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('[type="submit"]');
            if (btn && !btn.dataset.allowMultiple) {
                btn.disabled = true;
                setTimeout(() => btn.disabled = false, 3000);
            }
        });
    });

    // Accessibility: announce page load
    const announcer = document.createElement('div');
    announcer.setAttribute('aria-live', 'polite');
    announcer.className = 'sr-only';
    announcer.textContent = 'Página carregada';
    document.body.appendChild(announcer);
    setTimeout(() => announcer.remove(), 1000);
});

// ===== LOADING ESPECÍFICO PARA MÓDULOS =====
const LoadingIA = {
    sop(nome, norma) { Loading.show(`Gerando SOP: ${nome}...`, `Aplicando padrão ${norma} para o seu setor...`); },
    noticias() { Loading.show('Buscando notícias do seu setor...', 'Via Perplexity AI em tempo real...'); },
    analise() { Loading.show('Analisando notícias...', 'Gerando insights com IA...'); },
    conteudo() { Loading.show('Gerando conteúdo do post...', 'Criando texto e estrutura...'); },
    imagem() { Loading.show('Gerando imagem com DALL-E...', 'Pode levar até 30 segundos...'); },
    diagnostico() { Loading.show('Analisando diagnóstico...', 'Calculando score de maturidade...'); },
};

// ===== EMPTY STATES (gerador) =====
function renderEmptyState(container, config) {
    const el = document.getElementById(container);
    if (!el) return;
    el.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">${config.icone}</div>
            <h3 class="empty-state-title">${config.titulo}</h3>
            <p class="empty-state-desc">${config.descricao}</p>
            ${config.botao ? `<a href="${config.link}" class="bg-accent text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition">${config.botao}</a>` : ''}
        </div>`;
}

// Estados vazios pré-definidos
const EmptyStates = {
    diagnostico: { icone:'📋', titulo:'Nenhum diagnóstico realizado', descricao:'O diagnóstico é o ponto de partida. Com base nele a IA gera automaticamente seu Plano de Ação e todos os SOPs no padrão do seu setor.', botao:'Realizar diagnóstico', link:'/diagnostico/novo' },
    sops: { icone:'📖', titulo:'SOPs prontos para serem gerados', descricao:'A IA já preparou a lista de SOPs para o seu setor. Clique em "Gerar" em qualquer SOP para receber o documento completo pronto para revisão.', botao:'Ver lista de SOPs', link:'/manual-operacional' },
    academy: { icone:'🎓', titulo:'Academy não vinculada', descricao:'Seus cursos estão prontos na My Academy. Vincule sua conta para acessar com um clique.', botao:'Vincular Academy', link:'/perfil' },
    noticias: { icone:'📰', titulo:'Nenhuma notícia disponível', descricao:'Configure as APIs de conteúdo para começar a receber notícias do seu setor automaticamente.', botao:'Configurar APIs', link:'/admin/configuracoes' },
    conteudo: { icone:'✨', titulo:'Nenhum conteúdo gerado', descricao:'Gere seu primeiro post ou carrossel. A IA usa o perfil da sua marca e as notícias do seu setor para criar conteúdo profissional.', botao:'Gerar conteúdo', link:'/maquina-de-conteudo' },
    plano: { icone:'🎯', titulo:'Nenhum plano de ação', descricao:'Crie o plano de ação a partir do diagnóstico. A IA sugere prioridades com base nos problemas identificados.', botao:'Criar plano', link:'/plano-de-acao/novo' },
};
