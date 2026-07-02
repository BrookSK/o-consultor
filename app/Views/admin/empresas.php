<?php $tituloPagina = 'Empresas - Administração'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Empresas</li>
    </ol>
</nav>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Empresas</h1>
    <button id="btnNovaEmpresa" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition">
        + Nova Empresa
    </button>
</div>

<!-- Tabela de Empresas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Empresa
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        CNPJ
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Segmento
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Responsável
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Usuários
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($dados['empresas'])): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                                    <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm">Nenhuma empresa cadastrada</p>
                                <button onclick="novaEmpresa()" class="mt-2 text-primary hover:text-primary-dark text-sm font-medium">
                                    Cadastrar primeira empresa
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dados['empresas'] as $empresa): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($empresa['nome']) ?>
                                    </div>
                                    <?php if (!empty($empresa['telefone'])): ?>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($empresa['telefone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= !empty($empresa['cnpj']) ? 
                                    preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $empresa['cnpj']) 
                                    : '<span class="text-gray-400">—</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= !empty($empresa['segmento']) ? htmlspecialchars($empresa['segmento']) : '<span class="text-gray-400">—</span>' ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($empresa['responsavel_nome'])): ?>
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($empresa['responsavel_nome']) ?>
                                    </div>
                                    <?php if (!empty($empresa['responsavel_email'])): ?>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($empresa['responsavel_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $empresa['total_usuarios'] ?? 0 ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status = $empresa['status'] ?? 'ativo';
                                $statusClass = match($status) {
                                    'ativo' => 'bg-green-100 text-green-800',
                                    'pausado' => 'bg-yellow-100 text-yellow-800',
                                    'cancelado' => 'bg-red-100 text-red-800',
                                    'suspenso' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                $statusLabel = match($status) {
                                    'ativo' => 'Ativo',
                                    'pausado' => 'Pausado',
                                    'cancelado' => 'Cancelado',
                                    'suspenso' => 'Suspenso',
                                    default => 'Desconhecido'
                                };
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button onclick="visualizarEmpresa(<?= $empresa['id'] ?>)" 
                                        class="text-primary hover:text-primary-dark font-medium"
                                        title="Visualizar empresa">
                                    Ver
                                </button>
                                <button onclick="editarEmpresa(<?= $empresa['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium"
                                        title="Editar empresa">
                                    Editar
                                </button>
                                <?php if (($empresa['total_usuarios'] ?? 0) == 0): ?>
                                    <button onclick="excluirEmpresa(<?= $empresa['id'] ?>, '<?= htmlspecialchars($empresa['nome'], ENT_QUOTES) ?>')" 
                                            class="text-red-600 hover:text-red-800 font-medium"
                                            title="Excluir empresa">
                                        Excluir
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova/Editar Empresa -->
<div id="modalEmpresa" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-primary text-white px-6 py-4 rounded-t-lg">
            <h3 id="modalTitulo" class="text-lg font-semibold">Nova Empresa</h3>
        </div>
        
        <form id="formEmpresa" class="p-6 space-y-4">
            <input type="hidden" id="empresaId" name="id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                        Nome da Empresa <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nome" name="nome" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">
                        CNPJ <span class="text-sm text-gray-500">(opcional)</span>
                    </label>
                    <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="segmento" class="block text-sm font-medium text-gray-700 mb-1">
                        Segmento
                    </label>
                    <input type="text" id="segmento" name="segmento" placeholder="Ex: Varejo, Indústria..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefone
                    </label>
                    <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-9999"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                        Website
                    </label>
                    <input type="url" id="website" name="website" placeholder="https://www.empresa.com.br"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div class="md:col-span-2">
                    <label for="endereco" class="block text-sm font-medium text-gray-700 mb-1">
                        Endereço
                    </label>
                    <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">
                        Cidade
                    </label>
                    <input type="text" id="cidade" name="cidade" placeholder="Nome da cidade"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
                        Estado
                    </label>
                    <select id="estado" name="estado" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        <option value="">Selecione...</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>
                
                <div>
                    <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">
                        CEP
                    </label>
                    <input type="text" id="cep" name="cep" placeholder="00000-000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" onclick="fecharModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition">
                    <span id="btnSalvarTexto">Salvar Empresa</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Visualizar Empresa -->
<div id="modalVisualizarEmpresa" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="bg-primary text-white px-6 py-4 rounded-t-lg">
            <h3 class="text-lg font-semibold">Detalhes da Empresa</h3>
        </div>
        
        <div id="detalhesEmpresa" class="p-6">
            <!-- Conteúdo será preenchido via JavaScript -->
        </div>
        
        <div class="flex justify-end p-6 border-t">
            <button onclick="fecharModalVisualizar()" 
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
// Máscaras para campos
document.getElementById('cnpj').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    e.target.value = value;
});

document.getElementById('cep').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{5})(\d{3})/, '$1-$2');
    e.target.value = value;
});

document.getElementById('telefone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length === 11) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (value.length === 10) {
        value = value.replace(/^(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    e.target.value = value;
});

// Abrir modal nova empresa
document.getElementById('btnNovaEmpresa').addEventListener('click', novaEmpresa);

function novaEmpresa() {
    document.getElementById('modalTitulo').textContent = 'Nova Empresa';
    document.getElementById('btnSalvarTexto').textContent = 'Salvar Empresa';
    document.getElementById('formEmpresa').reset();
    document.getElementById('empresaId').value = '';
    document.getElementById('modalEmpresa').classList.remove('hidden');
}

// Editar empresa
function editarEmpresa(id) {
    fetch(`<?= APP_URL ?>/admin/empresas/visualizar?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const empresa = data.empresa;
                document.getElementById('modalTitulo').textContent = 'Editar Empresa';
                document.getElementById('btnSalvarTexto').textContent = 'Atualizar Empresa';
                document.getElementById('empresaId').value = empresa.id;
                document.getElementById('nome').value = empresa.nome || '';
                document.getElementById('cnpj').value = empresa.cnpj ? empresa.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '';
                document.getElementById('segmento').value = empresa.segmento || '';
                document.getElementById('telefone').value = empresa.telefone || '';
                document.getElementById('website').value = empresa.website || '';
                document.getElementById('endereco').value = empresa.endereco || '';
                document.getElementById('cidade').value = empresa.cidade || '';
                document.getElementById('estado').value = empresa.estado || '';
                document.getElementById('cep').value = empresa.cep ? empresa.cep.replace(/(\d{5})(\d{3})/, '$1-$2') : '';
                document.getElementById('modalEmpresa').classList.remove('hidden');
            } else {
                alert('Erro: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados da empresa');
        });
}

// Visualizar empresa
function visualizarEmpresa(id) {
    fetch(`<?= APP_URL ?>/admin/empresas/visualizar?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const empresa = data.empresa;
                const html = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Empresa</label>
                            <p class="text-gray-900">${empresa.nome}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CNPJ</label>
                            <p class="text-gray-900">${empresa.cnpj ? empresa.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Segmento</label>
                            <p class="text-gray-900">${empresa.segmento || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <p class="text-gray-900">${empresa.telefone || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                            <p class="text-gray-900">${empresa.website ? `<a href="${empresa.website}" target="_blank" class="text-primary hover:underline">${empresa.website}</a>` : '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <p class="text-gray-900">${empresa.status || 'Ativo'}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                            <p class="text-gray-900">${empresa.endereco || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                            <p class="text-gray-900">${empresa.cidade || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <p class="text-gray-900">${empresa.estado || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <p class="text-gray-900">${empresa.cep ? empresa.cep.replace(/(\d{5})(\d{3})/, '$1-$2') : '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                            <p class="text-gray-900">${empresa.responsavel_nome || '—'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Criado em</label>
                            <p class="text-gray-900">${new Date(empresa.criado_em).toLocaleDateString('pt-BR')}</p>
                        </div>
                    </div>
                `;
                document.getElementById('detalhesEmpresa').innerHTML = html;
                document.getElementById('modalVisualizarEmpresa').classList.remove('hidden');
            } else {
                alert('Erro: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados da empresa');
        });
}

// Excluir empresa
function excluirEmpresa(id, nome) {
    if (confirm(`Tem certeza que deseja excluir a empresa "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch('<?= APP_URL ?>/admin/empresas/excluir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `empresa_id=${id}&csrf_token=${csrfToken}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert(data.mensagem);
                location.reload();
            } else {
                alert('Erro: ' + data.erro);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir empresa');
        });
    }
}

// Salvar empresa
document.getElementById('formEmpresa').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    formData.append('csrf_token', csrfToken);
    
    const empresaId = document.getElementById('empresaId').value;
    const url = empresaId ? '<?= APP_URL ?>/admin/empresas/atualizar' : '<?= APP_URL ?>/admin/empresas/criar';
    
    const submitBtn = document.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Salvando...';
    submitBtn.disabled = true;
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            alert(data.mensagem);
            fecharModal();
            location.reload();
        } else {
            alert('Erro: ' + data.erro);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar empresa');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Fechar modais
function fecharModal() {
    document.getElementById('modalEmpresa').classList.add('hidden');
}

function fecharModalVisualizar() {
    document.getElementById('modalVisualizarEmpresa').classList.add('hidden');
}

// Fechar modal ao clicar fora
document.getElementById('modalEmpresa').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});

document.getElementById('modalVisualizarEmpresa').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalVisualizar();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>