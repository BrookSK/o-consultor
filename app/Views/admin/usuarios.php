<?php $tituloPagina = 'Usuários'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Usuários</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gerenciar Usuários</h1>
    <button onclick="abrirModalUsuario()" 
            class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">
        + Novo Usuário
    </button>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-2 mb-4">
    <select id="filtro-perfil" onchange="filtrarUsuarios()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none">
        <option value="">Todos perfis</option>
        <option value="ADMIN_HOLDING">Admin Holding</option>
        <option value="CONSULTOR_INTERNO">Consultor Interno</option>
        <option value="CLIENTE">Cliente</option>
    </select>
    <select id="filtro-status" onchange="filtrarUsuarios()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none">
        <option value="">Todos status</option>
        <option value="1">Ativo</option>
        <option value="0">Inativo</option>
    </select>
    <input type="text" id="filtro-busca" onkeyup="filtrarUsuarios()" placeholder="Buscar por nome ou email..." 
           class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none flex-1 min-w-48">
</div>

<!-- Tabela -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Nome</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Email</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Perfil</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Empresa</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Último Login</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-usuarios" class="divide-y divide-gray-100">
                <?php if (isset($dados['usuarios']) && !empty($dados['usuarios'])): ?>
                    <?php foreach ($dados['usuarios'] as $u): ?>
                        <?php 
                        $perfilBadge = match($u['perfil']) { 
                            'ADMIN_HOLDING' => 'bg-red-100 text-red-700', 
                            'CONSULTOR_INTERNO' => 'bg-blue-100 text-blue-700', 
                            default => 'bg-green-100 text-green-700' 
                        };
                        $statusBadge = ($u['ativo'] ?? 1) == 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';
                        ?>
                        <tr class="hover:bg-gray-50" data-usuario-id="<?= $u['id'] ?>">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($u['nome'] ?? '') ?></td>
                            <td class="px-4 py-3 text-gray-600 text-xs"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $perfilBadge ?>">
                                    <?= $u['perfil'] ?? 'CLIENTE' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 text-xs">
                                <?= htmlspecialchars($u['empresa_nome'] ?? 'N/A') ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                    <?= ($u['ativo'] ?? 1) == 1 ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400 text-xs">
                                <?= isset($u['ultimo_login']) && $u['ultimo_login'] ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : 'Nunca' ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="visualizarUsuario(<?= $u['id'] ?>)" 
                                            class="text-xs text-blue-600 hover:underline">Ver</button>
                                    <button onclick="editarUsuario(<?= $u['id'] ?>)" 
                                            class="text-xs text-primary hover:underline">Editar</button>
                                    <button onclick="alternarStatusUsuario(<?= $u['id'] ?>, '<?= ($u['ativo'] ?? 1) == 1 ? '0' : '1' ?>')" 
                                            class="text-xs text-<?= ($u['ativo'] ?? 1) == 1 ? 'red' : 'green' ?>-600 hover:underline">
                                        <?= ($u['ativo'] ?? 1) == 1 ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Nenhum usuário encontrado.
                        </td>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Usuário -->
<div id="modal-usuario" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Novo Usuário</h3>
        <form id="form-usuario" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Nome *</label><input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Email *</label><input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Perfil *</label><select name="perfil" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option>CLIENTE</option><option>CONSULTOR_INTERNO</option><option>ADMIN_HOLDING</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Empresa vinculada</label><select name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option value="">Nenhuma</option><option>Tech Solutions</option><option>Varejo Express</option><option>FoodService</option></select></div>
            <div><label class="block text-xs font-medium text-gray-700 mb-1">Senha temporária *</label><input type="text" name="senha" required value="<?= bin2hex(random_bytes(4)) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary font-mono"></div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-usuario').classList.add('hidden')" class="flex-1 border border-gray-300 py-2 rounded-lg text-sm text-gray-700">Cancelar</button>
                <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm font-medium">Criar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('form-usuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    const res = await fetch('<?= APP_URL ?>/admin/usuarios/salvar', { method:'POST', body: new FormData(this) });
    const data = await res.json();
    if (data.sucesso) { document.getElementById('modal-usuario').classList.add('hidden'); alert(data.mensagem); location.reload(); }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal Criar/Editar Usuário -->
<div id="modal-usuario" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 id="modal-titulo" class="text-lg font-semibold">Novo Usuário</h3>
                <button onclick="fecharModalUsuario()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="form-usuario" class="p-4 space-y-4">
                <input type="hidden" id="usuario-id" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" id="usuario-nome" name="nome" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" id="usuario-email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Perfil *</label>
                    <select id="usuario-perfil" name="perfil" required onchange="toggleEmpresaField()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="CLIENTE">Cliente</option>
                        <option value="CONSULTOR_INTERNO">Consultor Interno</option>
                        <option value="ADMIN_HOLDING">Admin Holding</option>
                    </select>
                </div>
                
                <div id="campo-empresa">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                    <select id="usuario-empresa" name="empresa_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="">Selecione uma empresa</option>
                        <!-- As empresas serão carregadas via JS -->
                    </select>
                </div>
                
                <div id="campo-senha">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input type="password" id="usuario-senha" name="senha" 
                           placeholder="Deixe em branco para manter a senha atual"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="tel" id="usuario-telefone" name="telefone"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="usuario-ativo" name="ativo" value="1" checked
                               class="w-4 h-4 text-primary rounded border-gray-300">
                        <span class="text-sm text-gray-700">Usuário ativo</span>
                    </label>
                </div>
                
                <div class="flex gap-3 pt-4 border-t">
                    <button type="button" onclick="fecharModalUsuario()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Visualizar Usuário -->
<div id="modal-visualizar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">Detalhes do Usuário</h3>
                <button onclick="fecharModalVisualizacao()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="conteudo-visualizar" class="p-4">
                <!-- Conteúdo será preenchido via JS -->
            </div>
        </div>
    </div>
</div>

<script>
let empresasDisponiveis = [];

// Carregar empresas ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    carregarEmpresas();
});

async function carregarEmpresas() {
    try {
        const response = await fetch('<?= APP_URL ?>/admin/empresas/listar');
        if (response.ok) {
            const data = await response.json();
            empresasDisponiveis = data.empresas || [];
            preencherSelectEmpresas();
        }
    } catch (error) {
        console.error('Erro ao carregar empresas:', error);
    }
}

function preencherSelectEmpresas() {
    const select = document.getElementById('usuario-empresa');
    select.innerHTML = '<option value="">Selecione uma empresa</option>';
    
    empresasDisponiveis.forEach(empresa => {
        const option = document.createElement('option');
        option.value = empresa.id;
        option.textContent = empresa.nome;
        select.appendChild(option);
    });
}

function toggleEmpresaField() {
    const perfil = document.getElementById('usuario-perfil').value;
    const campoEmpresa = document.getElementById('campo-empresa');
    const selectEmpresa = document.getElementById('usuario-empresa');
    
    if (perfil === 'ADMIN_HOLDING') {
        campoEmpresa.style.display = 'none';
        selectEmpresa.required = false;
    } else {
        campoEmpresa.style.display = 'block';
        selectEmpresa.required = true;
    }
}

function abrirModalUsuario(usuarioId = null) {
    const modal = document.getElementById('modal-usuario');
    const titulo = document.getElementById('modal-titulo');
    const form = document.getElementById('form-usuario');
    
    if (usuarioId) {
        titulo.textContent = 'Editar Usuário';
        carregarDadosUsuario(usuarioId);
    } else {
        titulo.textContent = 'Novo Usuário';
        form.reset();
        document.getElementById('usuario-id').value = '';
        document.getElementById('usuario-ativo').checked = true;
        toggleEmpresaField();
    }
    
    modal.classList.remove('hidden');
}

function fecharModalUsuario() {
    document.getElementById('modal-usuario').classList.add('hidden');
}

function fecharModalVisualizacao() {
    document.getElementById('modal-visualizar').classList.add('hidden');
}
async function carregarDadosUsuario(usuarioId) {
    try {
        const response = await fetch(`<?= APP_URL ?>/admin/usuarios/${usuarioId}`);
        if (response.ok) {
            const data = await response.json();
            const usuario = data.usuario;
            
            document.getElementById('usuario-id').value = usuario.id;
            document.getElementById('usuario-nome').value = usuario.nome || '';
            document.getElementById('usuario-email').value = usuario.email || '';
            document.getElementById('usuario-perfil').value = usuario.perfil || 'CLIENTE';
            document.getElementById('usuario-empresa').value = usuario.empresa_id || '';
            document.getElementById('usuario-telefone').value = usuario.telefone || '';
            document.getElementById('usuario-ativo').checked = (usuario.ativo == 1);
            
            toggleEmpresaField();
        } else {
            showToast('Erro ao carregar dados do usuário', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}

async function visualizarUsuario(usuarioId) {
    try {
        const response = await fetch(`<?= APP_URL ?>/admin/usuarios/${usuarioId}`);
        if (response.ok) {
            const data = await response.json();
            const usuario = data.usuario;
            
            const conteudo = `
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Nome</label>
                            <p class="font-medium">${usuario.nome || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Email</label>
                            <p class="text-sm">${usuario.email || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Perfil</label>
                            <p class="text-sm">${usuario.perfil || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Status</label>
                            <p class="text-sm">${usuario.ativo == 1 ? 'Ativo' : 'Inativo'}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Empresa</label>
                            <p class="text-sm">${usuario.empresa_nome || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Telefone</label>
                            <p class="text-sm">${usuario.telefone || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Criado em</label>
                            <p class="text-sm">${usuario.criado_em ? new Date(usuario.criado_em).toLocaleDateString('pt-BR') : 'N/A'}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Último login</label>
                            <p class="text-sm">${usuario.ultimo_login ? new Date(usuario.ultimo_login).toLocaleString('pt-BR') : 'Nunca'}</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('conteudo-visualizar').innerHTML = conteudo;
            document.getElementById('modal-visualizar').classList.remove('hidden');
        } else {
            showToast('Erro ao carregar dados do usuário', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}

function editarUsuario(usuarioId) {
    abrirModalUsuario(usuarioId);
}
// Submissão do formulário
document.getElementById('form-usuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    
    const usuarioId = document.getElementById('usuario-id').value;
    const isEdit = usuarioId !== '';
    
    try {
        const url = isEdit ? 
            `<?= APP_URL ?>/admin/usuarios/atualizar` : 
            `<?= APP_URL ?>/admin/usuarios/criar`;
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast(data.mensagem, 'success');
            fecharModalUsuario();
            location.reload(); // Recarregar para atualizar a tabela
        } else {
            showToast(data.erro || 'Erro ao salvar usuário', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
});

async function alternarStatusUsuario(usuarioId, novoStatus) {
    const acao = novoStatus == '1' ? 'ativar' : 'desativar';
    
    if (!confirm(`Deseja ${acao} este usuário?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('usuario_id', usuarioId);
        formData.append('ativo', novoStatus);
        
        const response = await fetch('<?= APP_URL ?>/admin/usuarios/alterar-status', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast(data.mensagem, 'success');
            location.reload();
        } else {
            showToast(data.erro || 'Erro ao alterar status', 'error');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    }
}

function filtrarUsuarios() {
    const filtroNome = document.getElementById('filtro-busca').value.toLowerCase();
    const filtroPerfil = document.getElementById('filtro-perfil').value;
    const filtroStatus = document.getElementById('filtro-status').value;
    
    const linhas = document.querySelectorAll('#tabela-usuarios tr');
    
    linhas.forEach(linha => {
        if (linha.querySelector('td')) { // Pular header
            const nome = linha.children[0].textContent.toLowerCase();
            const email = linha.children[1].textContent.toLowerCase();
            const perfil = linha.children[2].textContent.trim();
            const status = linha.children[4].textContent.includes('Ativo') ? '1' : '0';
            
            const matchNome = !filtroNome || nome.includes(filtroNome) || email.includes(filtroNome);
            const matchPerfil = !filtroPerfil || perfil === filtroPerfil;
            const matchStatus = !filtroStatus || status === filtroStatus;
            
            if (matchNome && matchPerfil && matchStatus) {
                linha.style.display = '';
            } else {
                linha.style.display = 'none';
            }
        }
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm font-medium z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
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