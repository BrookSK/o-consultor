<?php
/**
 * Router — Mapeamento de URLs para Controllers
 * O Consultor — Sistema Operacional Empresarial
 */

class Router
{
    private array $rotasGet = [];
    private array $rotasPost = [];

    public function __construct()
    {
        $this->registrarRotas();
    }

    /**
     * Registra todas as rotas da aplicação
     */
    private function registrarRotas(): void
    {
        // Autenticação
        $this->get('login', 'AuthController', 'showLogin');
        $this->post('login', 'AuthController', 'login');
        $this->get('cadastro', 'AuthController', 'showCadastro');
        $this->post('cadastro', 'AuthController', 'cadastro');
        $this->get('recuperar-senha', 'AuthController', 'showRecuperarSenha');
        $this->post('recuperar-senha', 'AuthController', 'recuperarSenha');
        $this->get('redefinir-senha', 'AuthController', 'showRedefinirSenha');
        $this->post('redefinir-senha', 'AuthController', 'redefinirSenha');
        $this->get('logout', 'AuthController', 'logout');

        // Dashboard
        $this->get('', 'DashboardController', 'index');
        $this->get('dashboard', 'DashboardController', 'index');

        // Diagnóstico
        $this->get('diagnostico', 'DiagnosticoController', 'index');
        $this->get('diagnostico/wizard', 'DiagnosticoController', 'wizard'); // Nova rota do wizard
        $this->get('diagnostico/novo', 'DiagnosticoController', 'novo');
        $this->get('diagnostico/bloco', 'DiagnosticoController', 'bloco');
        $this->post('diagnostico/salvar-bloco', 'DiagnosticoController', 'salvarBloco');
        $this->post('diagnostico/selecionar-empresa', 'DiagnosticoController', 'selecionarEmpresa');
        $this->post('diagnostico/limpar-rascunho', 'DiagnosticoController', 'limparRascunho');
        $this->post('diagnostico/upload-documentos', 'DiagnosticoController', 'uploadDocumentos');
        $this->get('diagnostico/debug', 'DiagnosticoController', 'debug');
        $this->post('diagnostico/gerar', 'DiagnosticoController', 'gerar');
        $this->post('diagnostico/reprocessar', 'DiagnosticoController', 'reprocessar');
        $this->post('diagnostico/salvar', 'DiagnosticoController', 'salvar');
        $this->get('diagnostico/resultado', 'DiagnosticoController', 'resultado');
        $this->get('diagnostico/editar', 'DiagnosticoController', 'editar');          // CRUD: editar empresa + respostas
        $this->post('diagnostico/atualizar', 'DiagnosticoController', 'atualizar');    // CRUD: salvar e regerar
        $this->post('diagnostico/excluir', 'DiagnosticoController', 'excluir');        // CRUD: excluir diagnóstico

        // Plano de Ação
        $this->get('plano-de-acao', 'PlanoController', 'index');
        $this->get('plano-de-acao/gerar-automatico', 'PlanoController', 'gerarAutomatico'); // Gera plano completo do diagnóstico
        $this->get('plano-de-acao/existe', 'PlanoController', 'planoExiste');               // Verifica se já existe plano do diagnóstico
        $this->post('plano-de-acao/excluir', 'PlanoController', 'excluir');                  // Excluir plano de ação
        $this->post('plano-de-acao/criar-tarefa', 'PlanoController', 'criarTarefaManual');   // Criar tarefa manual
        $this->post('plano-de-acao/criar-tarefa-ia', 'PlanoController', 'criarTarefaIA');     // Criar tarefa/compromisso por IA
        $this->post('plano-de-acao/criar-metrica', 'PlanoController', 'criarMetrica');        // Criar métrica/KPI
        $this->post('plano-de-acao/registrar-metrica', 'PlanoController', 'registrarMetrica'); // Registrar valor de métrica
        $this->get('plano-de-acao/novo', 'PlanoController', 'novo');
        $this->post('plano-de-acao/salvar-step1', 'PlanoController', 'salvarStep1');
        $this->get('plano-de-acao/prioridades', 'PlanoController', 'prioridades');
        $this->post('plano-de-acao/confirmar-prioridades', 'PlanoController', 'confirmarPrioridades');
        $this->get('plano-de-acao/tarefas', 'PlanoController', 'tarefas');
        $this->post('plano-de-acao/salvar-tarefas', 'PlanoController', 'salvarTarefas');
        $this->get('plano-de-acao/ver', 'PlanoController', 'ver');
        $this->post('plano-de-acao/mover-tarefa', 'PlanoController', 'moverTarefa');
        $this->post('plano-de-acao/liberar-tarefa', 'PlanoController', 'liberarTarefa'); // Liberar/recolher tarefa da fila para o Kanban
        $this->post('plano-de-acao/excluir-tarefa', 'PlanoController', 'excluirTarefa');  // Excluir tarefa/compromisso
        $this->get('plano-de-acao/tarefa-detalhe', 'PlanoController', 'tarefaDetalhe');       // Detalhe do card (JSON)
        $this->post('plano-de-acao/salvar-tarefa-detalhe', 'PlanoController', 'salvarTarefaDetalhe'); // Salvar campos do card
        $this->post('plano-de-acao/comentar-tarefa', 'PlanoController', 'comentarTarefa');    // Comentário no card
        $this->post('plano-de-acao/sugerir-tarefa-ia', 'PlanoController', 'sugerirTarefaIA'); // IA: sugere como fazer + checklist
        $this->post('plano-de-acao/upload-imagem-tarefa', 'PlanoController', 'uploadImagemTarefa'); // Colar/anexar imagem
        $this->post('plano-de-acao/reuniao', 'PlanoController', 'registrarReuniao');
        
        // F-12: Acionamento de Parceiros via Plano
        $this->post('plano/acionar-parceiro', 'PlanoController', 'acionarParceiro');
        $this->get('plano/listar-parceiros', 'PlanoController', 'listarParceiros');
        $this->get('plano/status-solicitacao-parceiro', 'PlanoController', 'statusSolicitacaoParceiro');
        
        // Plano legado
        $this->post('plano-de-acao/salvar', 'PlanoController', 'salvar');
        $this->post('plano-de-acao/tarefa-status', 'PlanoController', 'atualizarTarefaStatus');

        // Manual Operacional (SOP)
        $this->get('manual-operacional', 'SopController', 'index');
        $this->get('sop', 'SopController', 'index'); // Redirect alternativo para compatibilidade
        $this->get('sop/gerenciar', 'SopController', 'gerenciarSOPs');
        $this->post('sop/adicionar', 'SopController', 'adicionarSOP');
        $this->post('sop/remover', 'SopController', 'removerSOP');
        $this->post('sop/gerar', 'SopController', 'gerar');
        
        // NOVA ARQUITETURA: Manual Completo em 4 Etapas Detalhadas
        $this->post('sop/gerar-manual-completo', 'SopController', 'gerarManualCompleto');         // Etapa 1: Diagnóstico e Estrutura
        $this->get('sop/estrutura-existe', 'SopController', 'estruturaExiste');                   // Verifica se já há estrutura (recriar?)
        $this->get('sop/selecionar-servicos', 'SopController', 'selecionarServicos');             // Tela de seleção (draft) de serviços
        $this->post('sop/salvar-selecao-servicos', 'SopController', 'salvarSelecaoServicos');     // Salva seleção de serviços
        $this->post('sop/gerar-servicos-setor-voz', 'SopController', 'gerarServicosSetorPorVoz'); // IA: cria serviços de um setor por voz/texto
        // Geração conversacional de SOPs por voz (entrevista guiada por setor)
        $this->post('sop/classificar-conversa-setor', 'SopController', 'classificarConversaSetor'); // IA: classifica serviços do setor (identificado/sugerido/excluido) a partir da conversa
        $this->post('sop/gerar-selecionados-lote', 'SopController', 'gerarSelecionadosEmLote');      // Dispara geração em lote/paralela dos serviços marcados
        $this->get('sop/status-lote', 'SopController', 'statusLoteGeracao');                          // Polling do progresso do lote
        $this->post('sop/patch-sop-voz', 'SopController', 'aplicarPatchSopPorVoz');                   // Patch incremental de uma seção do SOP por voz
        $this->post('sop/ativar-servicos', 'SopController', 'ativarServicos');                    // Ativa serviços (aba setores inativos)
        $this->post('sop/ativar-setor', 'SopController', 'ativarSetorInteiro');                   // Ativa um setor inteiro sem precisar marcar serviços
        $this->post('sop/inativar-servicos', 'SopController', 'inativarServicos');                // Inativa serviços/setor (aba SOPs)
        $this->get('sop/mapear-servicos', 'SopController', 'mapearServicos');                     // Etapa 2A: View mapeamento
        $this->post('sop/executar-mapeamento-setor', 'SopController', 'executarMapeamentoSetor'); // Etapa 2A: AJAX por setor
        $this->post('sop/regenerar-csrf', 'SopController', 'regenerarTokenCSRF');               // Debug: Regenerar CSRF
        $this->post('sop/verificar-criar-tabelas', 'SopController', 'verificarCriarTabelas');   // Debug: Verificar/criar tabelas
        $this->post('sop/detalhar-servico-individual', 'SopController', 'detalharServicoIndividual'); // Novo: Detalhar serviço específico
        $this->post('sop/gerar-sop-individual', 'SopController', 'gerarSopIndividual');         // Novo: Gerar SOP específico
        $this->get('sop/ver-detalhamento-servico', 'SopController', 'verDetalhamentoServico'); // Novo: Ver detalhamento
        $this->get('sop/ver-sop-individual', 'SopController', 'verSopIndividual');             // Novo: Ver SOP individual
        $this->post('sop/regenerar-sop-individual', 'SopController', 'regenerarSopIndividual'); // Novo: Regenerar SOP individual
        $this->get('sop/debug-dados', 'SopController', 'debugSopDados');                      // Debug: Ver dados brutos do SOP
        $this->get('sop/debug-hierarquia', 'SopController', 'debugHierarquia');               // Debug: Ver dados da hierarquia completa
        $this->get('sop/debug-api', 'SopController', 'debugApi');                           // Debug: Testar configurações de API
        $this->get('sop/corrigir-referencias', 'SopController', 'corrigirReferencias');       // Debug: Corrigir referências quebradas
        $this->post('sop/adicionar-servico-manual', 'SopController', 'adicionarServicoManual'); // Novo: Adicionar serviço manual
        $this->get('sop/listar-servicos-manuais', 'SopController', 'listarServicosManuais');   // Novo: Listar serviços manuais
        $this->post('sop/excluir-servico-manual', 'SopController', 'excluirServicoManual');    // Novo: Excluir serviço manual
        $this->post('sop/transcrever-audio-whisper', 'SopController', 'transcreverAudioWhisper'); // Novo: Transcrever áudio com Whisper
        $this->post('sop/gerar-sop-por-transcricao', 'SopController', 'gerarSopPorTranscricao'); // Novo: Gerar SOP a partir de transcrição
        $this->post('sop/salvar-alteracoes-sop', 'SopController', 'salvarAlteracoesSop');       // Novo: Salvar edições do SOP
        $this->get('sop/detalhar-servicos', 'SopController', 'detalharServicos');                 // Etapa 2B: View detalhamento
        $this->post('sop/executar-detalhamento-servico', 'SopController', 'executarDetalhamentoServico'); // Etapa 2B: AJAX por serviço
        $this->get('sop/processar-sops', 'SopController', 'processarSOPs');                       // Etapa 3: View de processamento
        $this->post('sop/gerar-sop-individual', 'SopController', 'gerarSOPIndividual');           // Etapa 3: AJAX individual
        $this->post('sop/montar-manual-final', 'SopController', 'montarManualFinal');             // Etapa 4: Montagem final
        $this->get('sop/manual-completo', 'SopController', 'exibirManualCompleto');               // Exibir manual final
        
        // SISTEMA HIERÁRQUICO UNIFICADO: Setor > Serviços > SOPs
        $this->get('sop/gerenciar-hierarquia', 'SopController', 'gerenciarHierarquia');          // Interface principal de gerenciamento
        $this->post('sop/processar-servico-completo', 'SopController', 'processarServicoCompleto'); // Enfileira geração de SOP
        $this->get('sop/status-servico-sop', 'SopController', 'statusServicoSop');                // Polling do status da geração
        $this->get('sop/processar-fila', 'SopController', 'processarFilaHttp');                    // Processa 1 fase da fila (fallback sem cron)
        $this->get('sop/debug-fila', 'SopController', 'debugFila');                                // DEBUG: inspecionar estado da fila
        $this->post('sop/adicionar-servico-manual', 'SopController', 'adicionarServicoManual');  // Adicionar serviço manualmente
        $this->post('sop/criar-servico-inteligente', 'SopController', 'criarServicoInteligente'); // IA: cria serviço (nome/cat/crit) + gera SOP
        $this->post('sop/adicionar-servico-audio', 'SopController', 'adicionarServicoAudio');    // Adicionar serviço por áudio
        $this->get('sop/ver-detalhes-servico', 'SopController', 'verDetalhesServico');           // Ver detalhes completos do serviço
        $this->post('sop/editar-servico-manual', 'SopController', 'editarServicoManual');        // Editar serviço
        $this->post('sop/personalizar-servico', 'SopController', 'personalizarServico');         // Personalizar serviço (upload doc + regenerar SOP)
        $this->post('sop/gerar-scripts-comunicacao', 'SopController', 'gerarScriptsComunicacao'); // IA: gera scripts de comunicação a partir do SOP
        $this->post('sop/excluir-servico', 'SopController', 'excluirServico');                   // Excluir serviço
        $this->post('sop/excluir-servicos-lote', 'SopController', 'excluirServicosEmLote');      // Excluir vários serviços de uma vez
        
        $this->get('sop/ver', 'SopController', 'ver');
        $this->get('sop/debug-api', 'SopController', 'debugApiResponse'); // DEBUG TEMPORÁRIO
        $this->get('sop/debug-situacoes-criticas', 'SopController', 'debugSituacoesCriticas'); // DEBUG: Testar geração de situações críticas
        $this->get('sop/teste-rota', 'SopController', 'testeRota'); // TESTE: Verificar se roteamento funciona
        $this->get('sop/debug-gerenciar-hierarquia', 'SopController', 'debugGerenciarHierarquia'); // DEBUG: Testar gerenciarHierarquia sem auth
        $this->get('sop/debug-ver-detalhes-servico', 'SopController', 'debugVerDetalhesServico'); // DEBUG: Testar verDetalhesServico sem auth
        $this->get('sop/listar-por-diagnostico', 'SopController', 'listarSopsPorDiagnostico'); // Lista SOPs de um diagnóstico
        $this->get('sop/revisar', 'SopController', 'revisar');
        $this->post('sop/aprovar', 'SopController', 'aprovar');
        $this->post('sop/ajustar', 'SopController', 'ajustar');
        $this->post('sop/salvar-rascunho', 'SopController', 'salvarRascunho');
        $this->get('sop/contencao/{id}', 'SopController', 'contencao');
        $this->post('contencao/acionar', 'SopController', 'acionarContencao');
        $this->get('sop/exportar-pdf/{id}', 'SopController', 'exportarPdf');
        $this->get('sop/exportar-todos-zip', 'SopController', 'exportarTodosZip');
        $this->get('manual-operacional/raci', 'SopController', 'raci');
        $this->get('sop/raci-funcao', 'SopController', 'getRaciFuncao');
        $this->get('manual-operacional/kpis', 'KpiController', 'index');

        // KPI Management (F-07)
        $this->get('kpis/ver', 'KpiController', 'ver');
        $this->post('kpis/registrar', 'KpiController', 'registrar');
        $this->post('kpis/alerta/marcar-lido', 'KpiController', 'marcarAlertaLido');
        $this->post('kpi/processar-alerta', 'KpiController', 'processarAlerta');
        $this->post('sop/acionar-contencao', 'SopController', 'acionarContencao');

        // ===== BLOCO GESTÃO: AGENDA E FINANCEIRO =====
        // Agenda Pessoal
        $this->get('agenda', 'AgendaController', 'index');
        $this->post('agenda/adicionar', 'AgendaController', 'adicionarCompromisos');
        $this->post('agenda/atualizar-status', 'AgendaController', 'atualizarStatus');
        $this->get('agenda/calendario', 'AgendaController', 'calendario');
        $this->post('agenda/resolver-emergencia', 'AgendaController', 'resolverEmergencia');

        // Módulo Financeiro
        $this->get('financeiro', 'FinanceiroController', 'index');
        $this->post('financeiro/adicionar-transacao', 'FinanceiroController', 'adicionarTransacao');
        $this->post('financeiro/marcar-pago', 'FinanceiroController', 'marcarPago');
        $this->get('financeiro/relatorio', 'FinanceiroController', 'relatorio');
        $this->get('financeiro/projecao', 'FinanceiroController', 'projecao');

        // Central de Conteúdo
        $this->get('central-de-conteudo', 'ConteudoController', 'index');
        $this->get('central-de-conteudo/noticia', 'ConteudoController', 'noticiaDetalhe');
        $this->get('central-de-conteudo/noticias-pagina', 'ConteudoController', 'noticiasPagina');
        $this->get('central-de-conteudo/caso', 'ConteudoController', 'casoDetalhe');
        $this->post('central-de-conteudo/perfil-busca', 'ConteudoController', 'salvarPerfilBusca');
        $this->post('central-de-conteudo/buscar-agora', 'ConteudoController', 'buscarAgora');
        $this->get('central-de-conteudo/noticias-recentes', 'ConteudoController', 'noticiasRecentes');
        $this->post('central-de-conteudo/criar-conteudo', 'ConteudoController', 'criarConteudoDeNoticia');
        $this->post('central-de-conteudo/excluir-noticia', 'ConteudoController', 'excluirNoticia');
        $this->post('central-de-conteudo/limpar-noticias', 'ConteudoController', 'limparNoticias');
        $this->get('central-de-conteudo/biblioteca', 'ConteudoController', 'bibliotecaListar');
        $this->post('central-de-conteudo/biblioteca-upload', 'ConteudoController', 'bibliotecaUpload');
        $this->post('central-de-conteudo/biblioteca-excluir', 'ConteudoController', 'bibliotecaExcluir');
        $this->get('central-de-conteudo/admin', 'ConteudoController', 'admin');

        // Sistema de Notícias por IA (F-09)
        $this->get('noticias', 'NoticiasController', 'index');
        $this->get('noticias/admin', 'NoticiasController', 'admin');
        $this->get('noticias/detalhe', 'NoticiasController', 'detalhe');
        $this->post('noticias/buscar-agora', 'NoticiasController', 'buscarAgora');
        $this->post('noticias/gerar-analise', 'NoticiasController', 'gerarAnalise');
        $this->post('noticias/favoritar', 'NoticiasController', 'favoritar');
        $this->post('noticias/arquivar', 'NoticiasController', 'arquivar');
        $this->post('noticias/busca-global', 'NoticiasController', 'executarBuscaGlobal');
        $this->get('noticias/perfil', 'NoticiasController', 'perfil');
        $this->post('noticias/salvar-perfil', 'NoticiasController', 'salvarPerfil');
        $this->get('noticias/buscar', 'NoticiasController', 'buscar');
        $this->post('noticias/inicializar-perfil', 'NoticiasController', 'inicializarPerfil');
        $this->post('noticias/adicionar-site', 'NoticiasController', 'adicionarSite');
        $this->get('noticias/status-fila-busca', 'NoticiasController', 'statusBuscaFila');           // Polling do status da busca
        $this->get('noticias/processar-fila-busca', 'NoticiasController', 'processarFilaBuscaHttp'); // Processa 1 passo da fila (fallback sem cron)
        $this->get('noticias/preencher-imagens', 'NoticiasController', 'preencherImagensFaltantes');  // Backfill de og:image nas notícias salvas
        $this->post('noticias/remover-site', 'NoticiasController', 'removerSite');

        // Academy SSO
        $this->get('academy/sso', 'AcademyController', 'sso');
        $this->get('academy/logs', 'AcademyController', 'logs');
        $this->post('academy/desvincular', 'PerfilController', 'desvincularAcademy');

        // Máquina de Conteúdo (F-10 + F-11)
        $this->get('maquina-de-conteudo', 'MaquinaController', 'index');
        $this->get('maquina-de-conteudo/marca', 'MaquinaController', 'marca');
        $this->get('maquina-de-conteudo/nova-marca', 'MaquinaController', 'novaMarca');
        $this->get('maquina-de-conteudo/dados-empresa', 'MaquinaController', 'dadosEmpresa'); // Pré-preenchimento do wizard
        $this->post('maquina-de-conteudo/salvar-marca', 'MaquinaController', 'salvarMarca');
        $this->post('maquina-de-conteudo/excluir-marca', 'MaquinaController', 'excluirMarca');
        $this->post('maquina/gerar', 'MaquinaController', 'gerar');
        $this->get('maquina-de-conteudo/editar', 'MaquinaController', 'editar');
        $this->post('maquina-de-conteudo/aprovar', 'MaquinaController', 'aprovar');
        $this->post('maquina-de-conteudo/salvar-branding', 'MaquinaController', 'salvarBranding'); // Editar Brand Book
        $this->post('maquina-de-conteudo/upload-logo', 'MaquinaController', 'uploadLogo'); // Logo da marca
        $this->post('maquina-de-conteudo/upload-fechamento', 'MaquinaController', 'uploadFechamento'); // Imagem de fechamento do carrossel
        $this->post('maquina-de-conteudo/titulo-impactante', 'MaquinaController', 'tituloImpactante'); // Sugestão de título a partir da notícia
        $this->post('maquina-de-conteudo/salvar-imagem-editada', 'MaquinaController', 'salvarImagemEditada'); // Imagem base + logo posicionado

        // ===== Módulo Criador de Vídeos (Reels) =====
        $this->post('maquina-de-conteudo/video/salvar', 'VideoController', 'salvarProjeto');
        $this->post('maquina-de-conteudo/video/upload-audio', 'VideoController', 'uploadAudio');
        $this->post('maquina-de-conteudo/video/upload-imagem', 'VideoController', 'uploadImagem');
        $this->get('maquina-de-conteudo/video/vozes', 'VideoController', 'vozes');
        $this->post('maquina-de-conteudo/video/gerar-narracao', 'VideoController', 'gerarNarracao');
        $this->post('maquina-de-conteudo/video/exportar', 'VideoController', 'exportar');
        $this->get('maquina-de-conteudo/video/status', 'VideoController', 'statusExportacao');
        $this->get('maquina-de-conteudo/video/processar-bg', 'VideoController', 'processarFilaBackground');
        $this->post('maquina-de-conteudo/salvar-biblioteca', 'MaquinaController', 'salvarBiblioteca'); // "Terminar depois"
        $this->post('maquina-de-conteudo/excluir-conteudo', 'MaquinaController', 'excluirConteudo');
        $this->post('maquina-de-conteudo/excluir-conteudos', 'MaquinaController', 'excluirConteudos'); // Exclusão em massa
        $this->post('maquina-de-conteudo/regenerar-imagem', 'MaquinaController', 'regenerarImagem');
        $this->post('maquina-de-conteudo/gerar-imagem-slide', 'MaquinaController', 'gerarImagemSlide'); // Gera 1 imagem por vez (evita timeout)
        $this->get('maquina-de-conteudo/status-imagens', 'MaquinaController', 'statusImagensConteudo'); // Polling do status das imagens (background)
        $this->get('maquina-de-conteudo/processar-fila-imagens', 'MaquinaController', 'processarFilaImagensHttp'); // Fallback: processa 1 imagem via HTTP
        $this->get('maquina-de-conteudo/processar-imagens-bg', 'MaquinaController', 'processarFilaImagensBackground'); // Processa a fila em background (fecha conexão e continua)
        $this->post('maquina-de-conteudo/cancelar-imagem-slide', 'MaquinaController', 'cancelarImagemSlide'); // Cancela 1 imagem pendente
        $this->post('maquina-de-conteudo/cancelar-imagens', 'MaquinaController', 'cancelarImagens'); // Cancela todas pendentes
        $this->post('maquina-de-conteudo/upload-imagem', 'MaquinaController', 'uploadImagem');
        $this->post('maquina-de-conteudo/atualizar-slide', 'MaquinaController', 'atualizarSlide');
        $this->post('maquina-de-conteudo/upload-template', 'MaquinaController', 'uploadTemplate');
        $this->get('maquina-de-conteudo/templates', 'MaquinaController', 'listarTemplates');
        $this->post('maquina-de-conteudo/remover-template', 'MaquinaController', 'removerTemplate');
        $this->post('maquina-de-conteudo/recalcular-perfil-templates', 'MaquinaController', 'recalcularPerfilTemplates'); // Perfil visual consolidado da marca
        $this->post('maquina-de-conteudo/salvar-perfil-templates', 'MaquinaController', 'salvarPerfilTemplatesEndpoint'); // Salvar perfil editado à mão
        $this->post('maquina-de-conteudo/atualizar-categoria-template', 'MaquinaController', 'atualizarCategoriaTemplate'); // Categoria/objetivo do template
        
        // F-11: Publicação e Agendamento
        $this->post('maquina/agendar', 'MaquinaController', 'agendar');
        $this->get('maquina/download', 'MaquinaController', 'download');
        $this->post('maquina/marcar-publicado', 'MaquinaController', 'marcarPublicado');
        $this->get('maquina/calendario', 'MaquinaController', 'calendario');

        // Parceiros
        $this->get('parceiros', 'ParceirosController', 'index');
        $this->get('parceiros/perfil', 'ParceirosController', 'perfil');
        $this->post('parceiros/solicitar', 'ParceirosController', 'solicitar');
        $this->get('parceiros/admin', 'ParceirosController', 'admin');
        $this->post('parceiros/status', 'ParceirosController', 'atualizarStatus');
        
        // F-12: Admin de Solicitações de Parceiros
        $this->get('parceiros/solicitacoes', 'ParceirosController', 'solicitacoes');
        $this->post('parceiros/atualizar-status-solicitacao', 'ParceirosController', 'atualizarStatusSolicitacao');

        // Governança
        $this->get('governanca', 'GovernancaController', 'index');
        $this->post('governanca/reuniao', 'GovernancaController', 'salvarReuniao');
        $this->post('governanca/auditoria', 'GovernancaController', 'registrarAuditoria');

        // Admin
        $this->get('admin', 'AdminController', 'index');
        
        // Gestão de Usuários
        $this->get('admin/usuarios', 'AdminController', 'usuarios');
        $this->get('admin/usuarios/(\d+)', 'AdminController', 'visualizarUsuario');
        $this->post('admin/usuarios/salvar', 'AdminController', 'salvarUsuario');
        $this->post('admin/usuarios/criar', 'AdminController', 'criarUsuario');
        $this->post('admin/usuarios/atualizar', 'AdminController', 'atualizarUsuario');
        $this->post('admin/usuarios/alterar-status', 'AdminController', 'alterarStatusUsuario');
        
        // Gestão de Empresas
        $this->get('admin/empresas', 'AdminController', 'empresas');
        $this->get('admin/empresas/nova', 'AdminController', 'novaEmpresa');
        $this->post('admin/empresas/criar', 'AdminController', 'criarEmpresa');
        $this->get('admin/empresas/visualizar', 'AdminController', 'visualizarEmpresa');
        $this->post('admin/empresas/atualizar', 'AdminController', 'atualizarEmpresa');
        $this->post('admin/empresas/excluir', 'AdminController', 'excluirEmpresa');
        $this->get('admin/empresas/listar', 'AdminController', 'buscarEmpresas');
        
        // F-13: Gestão de Clientes
        $this->get('admin/clientes', 'AdminController', 'clientes');
        $this->get('admin/clientes/novo', 'AdminController', 'novoCliente');
        $this->post('admin/clientes/criar', 'AdminController', 'criarCliente');
        $this->get('admin/clientes/perfil', 'AdminController', 'perfilCliente');
        $this->post('admin/clientes/trocar-consultor', 'AdminController', 'trocarConsultor');
        $this->post('admin/clientes/alterar-status', 'AdminController', 'alterarStatusCliente');
        
        // Configurações Admin
        $this->get('admin/configuracoes', 'AdminController', 'configuracoes');
        $this->post('admin/testar-apis', 'AdminController', 'testarApis');
        $this->post('admin/testar-academy', 'AdminController', 'testarAcademy');
        $this->post('admin/testar-smtp', 'AdminController', 'testarSmtp');
        $this->post('admin/configuracoes/salvar', 'AdminController', 'salvarConfiguracoes');
        
        // F-14: Configuração de APIs de IA
        $this->post('admin/api/toggle', 'AdminController', 'toggleApi');
        $this->post('admin/api/salvar-chave', 'AdminController', 'salvarChaveApi');
        $this->post('admin/api/testar', 'AdminController', 'testarApiIndividual');
        $this->post('admin/api/status', 'AdminController', 'statusApi');
        $this->post('admin/smtp/salvar', 'AdminController', 'salvarSmtp');
        $this->post('admin/smtp/testar', 'AdminController', 'testarSmtp');
        
        // Logs e Relatórios
        $this->get('admin/logs', 'AdminController', 'logs');
        $this->get('admin/relatorios', 'AdminController', 'relatorios');
        
        // Utilitários Admin
        $this->post('admin/selecionar-empresa', 'AdminController', 'selecionarEmpresa');

        // Perfil
        $this->get('perfil', 'PerfilController', 'index');
        $this->post('perfil/salvar', 'PerfilController', 'salvar');
        $this->post('perfil/vincular-academy', 'PerfilController', 'vincularAcademy');
        $this->post('perfil/alterar-senha', 'PerfilController', 'alterarSenha');

        // Onboarding
        $this->get('onboarding', 'PerfilController', 'onboarding');
        $this->post('onboarding/step1', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step2', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step3', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step4', 'PerfilController', 'salvarStep');
        $this->post('onboarding/salvar-step', 'PerfilController', 'salvarStep');
        $this->post('onboarding/concluir', 'PerfilController', 'concluirOnboarding');
        $this->post('onboarding/vincular-academy', 'PerfilController', 'vincularAcademy');

        // Alertas e Notificações
        $this->get('alertas', 'AlertaController', 'index');
        $this->post('alertas/marcar-lido', 'AlertaController', 'marcarLido');
        $this->post('alertas/resolver', 'AlertaController', 'resolver');
        $this->get('alertas/recentes', 'AlertaController', 'recentes');
        $this->post('alertas/marcar-todos-lidos', 'AlertaController', 'marcarTodosLidos');
        $this->post('alertas/preferencias', 'AlertaController', 'salvarPreferencias');

        // API Interna
        $this->post('api/transcricao', 'ApiController', 'transcricao');
        $this->get('api/csrf-token', 'ApiController', 'csrfToken');
        $this->get('api/notificacoes', 'ApiController', 'listarNotificacoes');       // Notificações in-app (ex.: SOPs de um setor prontos)
        $this->post('api/notificacoes/ler', 'ApiController', 'marcarNotificacaoLida'); // Marca notificação como lida
    }

    /**
     * Registra uma rota GET
     */
    private function get(string $rota, string $controller, string $action): void
    {
        $this->rotasGet[$rota] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Registra uma rota POST
     */
    private function post(string $rota, string $controller, string $action): void
    {
        $this->rotasPost[$rota] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Despacha a requisição para o controller e action corretos
     */
    public function despachar(string $url): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];

        // Tratar rotas dinâmicas específicas
        if (preg_match('/^diagnostico\/resultado\/(\d+)$/', $url)) {
            $this->executarAction('DiagnosticoController', 'resultado');
            return;
        }
        
        if (preg_match('/^diagnostico\/bloco\/(\d+)$/', $url)) {
            $this->executarAction('DiagnosticoController', 'bloco');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/prioridades\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'prioridades');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/tarefas\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'tarefas');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'show');
            return;
        }

        // F-09: Detalhes de notícia dinâmico
        if (preg_match('/^noticias\/detalhe\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('NoticiasController', 'detalhe');
            return;
        }

        // F-13: Cliente perfil dinâmico
        if (preg_match('/^admin\/clientes\/perfil\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('AdminController', 'perfilCliente');
            return;
        }

        // CRUD Usuários - visualizar usuário
        if (preg_match('/^admin\/usuarios\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('AdminController', 'visualizarUsuario');
            return;
        }

        // Ver o prompt COMPLETO usado para gerar a imagem de um slide.
        // /maquina-de-conteudo/imagem/prompt/{conteudoId}/{slideIndex}
        if (preg_match('/^maquina-de-conteudo\/imagem\/prompt\/(\d+)\/(\d+)$/', $url, $matches)) {
            $_GET['conteudo_id'] = (int) $matches[1];
            $_GET['slide_index'] = (int) $matches[2];
            $this->executarAction('MaquinaController', 'verPromptImagem');
            return;
        }

        // Mini Editor de Vídeo (Reels) — abre pelo ID do post.
        if (preg_match('/^maquina-de-conteudo\/video\/(\d+)$/', $url, $matches)) {
            $_GET['conteudo_id'] = (int) $matches[1];
            $this->executarAction('VideoController', 'editor');
            return;
        }

        if (preg_match('/^maquina-de-conteudo\/editar\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'editar');
            return;
        }

        if (preg_match('/^maquina\/download\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'download');
            return;
        }

        if (preg_match('/^maquina\/marcar-publicado\/(\d+)$/', $url, $matches)) {
            $_POST['conteudo_id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'marcarPublicado');
            return;
        }

        if ($metodo === 'GET' && isset($this->rotasGet[$url])) {
            $rota = $this->rotasGet[$url];
        } elseif ($metodo === 'POST' && isset($this->rotasPost[$url])) {
            $rota = $this->rotasPost[$url];
        } else {
            $this->erro404();
            return;
        }

        $this->executarAction($rota['controller'], $rota['action']);
    }

    /**
     * Executa uma action específica
     */
    private function executarAction(string $controllerName, string $actionName): void
    {
        $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->erro404();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->erro404();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            $this->erro404();
            return;
        }

        $controller->$actionName();
    }

    /**
     * Exibe página de erro 404
     */
    private function erro404(): void
    {
        http_response_code(404);
        if (file_exists(VIEW_PATH . '/errors/404.php')) {
            require VIEW_PATH . '/errors/404.php';
        } else {
            echo '<h1>404 — Página não encontrada</h1>';
            echo '<p>A página que você procura não existe.</p>';
            echo '<a href="' . APP_URL . '/login">Voltar ao início</a>';
        }
    }
}
