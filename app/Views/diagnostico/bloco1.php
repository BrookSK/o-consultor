<?php $tituloPagina = 'Diagnóstico — Bloco 1 de 5'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F',700:'#162D4A'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco1()">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header com Progresso -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
                <p class="text-gray-500">Bloco 1 de 5 — Identificação da Empresa</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 20%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">20% concluído</div>
            </div>
        </div>
    </div>

    <!-- Formulário Bloco 1 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="1">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Seleção de Cliente (para ADMIN_HOLDING) -->
                <?php if (Auth::perfil() === 'ADMIN_HOLDING' && !empty($dados['empresas_disponiveis'])): ?>
                <div class="md:col-span-2">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cliente para Diagnóstico *</label>
                        <select name="cliente_selecionado" @change="selecionarEmpresa($event.target.value)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione o cliente ou crie um novo diagnóstico</option>
                            <?php foreach ($dados['empresas_disponiveis'] as $empresa): ?>
                                <option value="<?= $empresa['id'] ?>" 
                                        data-nome="<?= htmlspecialchars($empresa['nome']) ?>" 
                                        data-segmento="<?= htmlspecialchars($empresa['segmento']) ?>"
                                        <?= ($dados['rascunho']['empresa_id'] ?? 0) == $empresa['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($empresa['nome']) ?> - <?= htmlspecialchars($empresa['segmento']) ?>
                                    <?php if (!empty($empresa['responsavel_nome'])): ?>
                                        (<?= htmlspecialchars($empresa['responsavel_nome']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="novo">+ Criar diagnóstico para nova empresa</option>
                        </select>
                        <p class="text-xs text-amber-700 mt-2">
                            ⚠️ Selecione um cliente existente ou escolha "nova empresa" para preencher dados manualmente
                        </p>
                        <div x-show="empresaSelecionada" class="text-xs mt-1 text-green-600">
                            ✓ Dados do cliente carregados automaticamente
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Nome da Empresa -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome da Empresa *</label>
                    <input type="text" name="empresa_nome" x-model="form.empresa_nome" required
                           :readonly="empresaSelecionada"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           :class="{ 'bg-gray-100': empresaSelecionada }"
                           placeholder="Digite o nome completo da empresa"
                           value="<?= htmlspecialchars($dados['rascunho']['empresa_nome'] ?? '') ?>">
                    <p x-show="empresaSelecionada" class="text-xs text-gray-500 mt-1">
                        Campo preenchido automaticamente com dados do cliente selecionado
                    </p>
                </div>

                <!-- Setor -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Setor de Atuação *</label>
                    <select name="setor" x-model="form.setor" required
                            :disabled="empresaSelecionada"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            :class="{ 'bg-gray-100': empresaSelecionada }">
                        <option value="">Selecione o setor</option>
                        <?php foreach ($dados['opcoes']['setores'] as $setor): ?>
                            <option value="<?= $setor ?>" <?= ($dados['rascunho']['setor'] ?? '') === $setor ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p x-show="empresaSelecionada" class="text-xs text-gray-500 mt-1">
                        Campo preenchido automaticamente com dados do cliente selecionado
                    </p>
                </div>

                <!-- Tempo de Existência -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tempo de Existência</label>
                    <select name="tempo_existencia" x-model="form.tempo_existencia"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione</option>
                        <?php foreach ($dados['opcoes']['tempo_existencia'] as $tempo): ?>
                            <option value="<?= $tempo ?>" <?= ($dados['rascunho']['tempo_existencia'] ?? '') === $tempo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tempo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Estrutura Societária -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estrutura Societária</label>
                    <select name="estrutura_societaria" x-model="form.estrutura_societaria"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione</option>
                        <?php foreach ($dados['opcoes']['estrutura_societaria'] as $estrutura): ?>
                            <option value="<?= $estrutura ?>" <?= ($dados['rascunho']['estrutura_societaria'] ?? '') === $estrutura ? 'selected' : '' ?>>
                                <?= htmlspecialchars($estrutura) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Unidades/Filiais -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Unidades/Filiais</label>
                    <input type="number" name="unidades_filiais" x-model="form.unidades_filiais" min="1"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="1"
                           value="<?= $dados['rascunho']['unidades_filiais'] ?? 1 ?>">
                </div>

                <!-- Língua Principal -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Língua Principal</label>
                    <select name="lingua_principal" x-model="form.lingua_principal"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <?php foreach ($dados['opcoes']['linguas'] as $lingua): ?>
                            <option value="<?= $lingua ?>" <?= ($dados['rascunho']['lingua_principal'] ?? 'Português') === $lingua ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lingua) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Descrição -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Breve Descrição da Empresa</label>
                    <textarea name="descricao" x-model="form.descricao" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva brevemente a atividade principal da empresa, produtos/serviços oferecidos..."><?= htmlspecialchars($dados['rascunho']['descricao'] ?? '') ?></textarea>
                    <div class="mt-2">
                        <div class="microfone-container" data-textarea="descricao"></div>
                    </div>
                </div>
            </div>

            <!-- Sistema de Upload de Documentos -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
                    📄 Documentos da Empresa (Opcional)
                </h3>
                <p class="text-blue-700 mb-4">
                    Faça upload de documentos internos da empresa (processos, manuais, políticas, etc.) para que a IA 
                    possa personalizar o diagnóstico e SOPs com informações reais que já existem na empresa.
                </p>
                
                <?php if (!empty($dados['documentos_existentes'])): ?>
                <div class="mb-4">
                    <h4 class="font-medium text-blue-800 mb-2">Documentos já enviados:</h4>
                    <div class="space-y-2">
                        <?php foreach ($dados['documentos_existentes'] as $doc): ?>
                        <div class="flex items-center justify-between bg-white rounded-lg p-3 border">
                            <div>
                                <span class="font-medium"><?= htmlspecialchars($doc['nome_original']) ?></span>
                                <span class="text-xs text-gray-500 ml-2">
                                    <?= number_format($doc['tamanho_bytes']/1024, 1) ?>KB • 
                                    <?= date('d/m/Y', strtotime($doc['criado_em'])) ?> •
                                    <?= $doc['processado_ia'] ? '✅ Processado' : '⏳ Aguardando' ?>
                                </span>
                            </div>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                <?= htmlspecialchars($doc['tipo_documento']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div x-data="documentUpload()" class="space-y-4">
                    <div class="border-2 border-dashed border-blue-300 rounded-lg p-6 text-center" 
                         @drop.prevent="handleDrop($event)" 
                         @dragover.prevent 
                         @dragenter.prevent>
                        <div class="mb-4">
                            <svg class="mx-auto h-12 w-12 text-blue-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <p class="text-blue-600 font-medium">Arraste e solte arquivos aqui ou</p>
                        <input type="file" 
                               x-ref="fileInput"
                               multiple 
                               accept=".pdf,.doc,.docx,.txt,.rtf" 
                               @change="handleFileSelect($event)"
                               class="hidden">
                        <button type="button" 
                                @click="$refs.fileInput.click()"
                                class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Selecionar Arquivos
                        </button>
                        <button type="button" 
                                @click="clearUpload()"
                                x-show="selectedFiles.length > 0"
                                class="mt-2 ml-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Limpar Arquivos
                        </button>
                        <p class="text-xs text-gray-500 mt-2">
                            PDF, DOC, DOCX, TXT, RTF • Máximo 1GB por arquivo
                        </p>
                    </div>

                    <!-- Lista de arquivos selecionados -->
                    <div x-show="selectedFiles.length > 0" class="space-y-2">
                        <h4 class="font-medium text-gray-700">Arquivos selecionados:</h4>
                        <template x-for="(file, index) in selectedFiles" :key="index">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 border">
                                <div>
                                    <span class="font-medium" x-text="file.name"></span>
                                    <span class="text-xs text-gray-500 ml-2" x-text="formatFileSize(file.size)"></span>
                                </div>
                                <button type="button" 
                                        @click="removeFile(index)"
                                        class="text-red-600 hover:text-red-800 transition">
                                    ✕
                                </button>
                            </div>
                        </template>
                        
                        <button type="button" 
                                @click="uploadDocuments()"
                                :disabled="uploading"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
                                :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                            <span x-show="!uploading">📤 Enviar Documentos</span>
                            <span x-show="uploading" class="flex items-center gap-2">
                                <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                Enviando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t border-gray-100">
                <div class="flex gap-3">
                    <a href="<?= APP_URL ?>/diagnostico" 
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        ← Cancelar
                    </a>
                    
                    <button type="button" @click="limparRascunho()"
                            class="px-6 py-3 border border-red-300 rounded-lg text-red-700 hover:bg-red-50 transition">
                        🗑️ Limpar Draft
                    </button>
                    
                    <!-- Botão temporário para debug -->
                    <a href="<?= APP_URL ?>/diagnostico/debug" target="_blank"
                       class="px-4 py-3 text-xs border border-blue-300 rounded-lg text-blue-700 hover:bg-blue-50 transition">
                        🔍 Debug
                    </a>
                </div>
                
                <button type="submit" :disabled="loading"
                        class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold flex items-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }">
                    <span x-show="!loading">Próximo Bloco →</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Salvando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/public/assets/js/microfone-transcricao.js"></script>
<script>
function diagnosticoBloco1() {
    return {
        loading: false,
        empresaSelecionada: <?= !empty($dados['rascunho']['empresa_id']) ? 'true' : 'false' ?>,
        form: {
            empresa_nome: '<?= addslashes($dados['rascunho']['empresa_nome'] ?? '') ?>',
            setor: '<?= addslashes($dados['rascunho']['setor'] ?? '') ?>',
            descricao: '<?= addslashes($dados['rascunho']['descricao'] ?? '') ?>',
            tempo_existencia: '<?= addslashes($dados['rascunho']['tempo_existencia'] ?? '') ?>',
            estrutura_societaria: '<?= addslashes($dados['rascunho']['estrutura_societaria'] ?? '') ?>',
            unidades_filiais: '<?= $dados['rascunho']['unidades_filiais'] ?? 1 ?>',
            lingua_principal: '<?= addslashes($dados['rascunho']['lingua_principal'] ?? 'Português') ?>'
        },

        async selecionarEmpresa(empresaId) {
            console.log('selecionarEmpresa chamado com ID:', empresaId);
            
            if (!empresaId || empresaId === 'novo') {
                console.log('Limpando dados para nova empresa');
                this.empresaSelecionada = false;
                this.form.empresa_nome = '';
                this.form.setor = '';
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('empresa_id', empresaId);
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');

                const response = await fetch('<?= APP_URL ?>/diagnostico/selecionar-empresa', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Resultado selecionar empresa:', result);

                if (result.sucesso) {
                    this.empresaSelecionada = true;
                    this.form.empresa_nome = result.dados.empresa_nome;
                    this.form.setor = result.dados.setor;
                    console.log('Empresa selecionada com sucesso:', result.dados);
                } else {
                    alert(result.mensagem || 'Erro ao selecionar empresa');
                }
            } catch (error) {
                console.error('Erro ao selecionar empresa:', error);
                alert('Erro na conexão ao selecionar empresa');
            }
        },

        async salvarBloco() {
            console.log('salvarBloco iniciado');
            
            if (!this.form.empresa_nome.trim()) {
                alert('Nome da empresa é obrigatório');
                return;
            }

            if (!this.form.setor) {
                alert('Setor de atuação é obrigatório');
                return;
            }

            console.log('Validação passou, dados do form:', this.form);
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', '1');
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');
                
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });

                console.log('FormData preparado:', Object.fromEntries(formData));

                const response = await fetch('<?= APP_URL ?>/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Resultado recebido:', result);

                if (result.sucesso) {
                    console.log('Bloco salvo com sucesso');
                    console.log('Dados do resultado:', result);
                    
                    if (result.redirect) {
                        console.log('Usando redirect fornecido pelo servidor:', result.redirect);
                        window.location.href = result.redirect;
                    } else {
                        const defaultRedirect = '<?= APP_URL ?>/diagnostico/bloco/2?rascunho_id=<?= $dados['rascunho']['id'] ?>';
                        console.log('Usando redirecionamento padrão:', defaultRedirect);
                        window.location.href = defaultRedirect;
                    }
                } else {
                    console.error('Erro do servidor:', result.mensagem);
                    console.error('Debug info:', result.debug_info);
                    alert(result.mensagem || 'Erro ao salvar bloco');
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Erro na conexão: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        async limparRascunho() {
            if (!confirm('Tem certeza que deseja limpar todo o rascunho? Todos os dados preenchidos serão perdidos.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');

                const response = await fetch('<?= APP_URL ?>/diagnostico/limpar-rascunho', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    alert(result.mensagem);
                    window.location.href = '<?= APP_URL ?>/diagnostico/novo';
                } else {
                    alert(result.mensagem || 'Erro ao limpar rascunho');
                }
            } catch (error) {
                alert('Erro na conexão. Tente novamente.');
            }
        }
    };
}

// Componente para upload de documentos
function documentUpload() {
    return {
        selectedFiles: [],
        uploading: false,

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.addFiles(files);
        },

        handleDrop(event) {
            const files = Array.from(event.dataTransfer.files);
            this.addFiles(files);
        },

        addFiles(files) {
            for (const file of files) {
                // Validar tipo de arquivo
                const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.rtf'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(fileExtension)) {
                    alert(`Arquivo ${file.name} não é suportado. Use apenas: PDF, DOC, DOCX, TXT, RTF`);
                    continue;
                }

                // Validar tamanho (1GB)
                if (file.size > 1024 * 1024 * 1024) {
                    alert(`Arquivo ${file.name} é muito grande. Máximo 1GB por arquivo.`);
                    continue;
                }

                // Verificar se não é duplicado
                if (!this.selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    this.selectedFiles.push(file);
                }
            }
        },

        removeFile(index) {
            this.selectedFiles.splice(index, 1);
        },

        clearUpload() {
            this.selectedFiles = [];
            // Limpar input file
            const fileInput = this.$refs.fileInput;
            if (fileInput) {
                fileInput.value = '';
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        async uploadDocuments() {
            if (this.selectedFiles.length === 0) return;

            this.uploading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                
                for (let i = 0; i < this.selectedFiles.length; i++) {
                    formData.append('documentos[]', this.selectedFiles[i]);
                }

                const response = await fetch('<?= APP_URL ?>/diagnostico/upload-documentos', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    alert(result.mensagem);
                    this.selectedFiles = [];
                    // Recarregar a página para mostrar os documentos enviados
                    window.location.reload();
                } else {
                    alert(result.erro || 'Erro ao enviar documentos');
                }
            } catch (error) {
                alert('Erro na conexão. Tente novamente.');
            } finally {
                this.uploading = false;
            }
        }
    };
}

// Inicializar microfones
document.addEventListener('DOMContentLoaded', function() {
    initializeMicrophones();
});
</script>

</body>
</html>