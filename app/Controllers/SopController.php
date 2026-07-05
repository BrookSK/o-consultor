<?php
/**
 * SopController — Módulo de Manual Operacional com SOPs individuais
 * O Consultor — Sistema Operacional Empresarial
 *
 * PRINCÍPIO: Cada SOP é gerado INDIVIDUALMENTE com subtópicos específicos.
 * ACESSO: ADMIN/CONSULTOR (geram e editam), CLIENTE (revisa e aprova)
 */

class SopController
{
    /**
     * Tela principal — Cards por departamento (F-05 Implementation)
     */
    public function index(): void
    {
        Auth::proteger();
        
        $dados = [];

        // Verificar se veio de um diagnóstico específico
        $diagnosticoId = (int) (isset($_GET['diagnostico_id']) ? $_GET['diagnostico_id'] : 0);
        
        if ($diagnosticoId) {
            // VERIFICAÇÃO: Se já existem SOPs estruturados, redirecionar diretamente para listagem
            $sopsExistentes = Database::queryOne(
                "SELECT COUNT(*) as total FROM sops WHERE diagnostico_id = ? AND empresa_id = ?",
                [$diagnosticoId, Auth::empresa()]
            );
            
            // Se existem SOPs, verificar se há estrutura hierárquica
            if ($sopsExistentes['total'] > 0) {
                $estruturaExistente = Database::queryOne(
                    "SELECT id FROM estruturas_hierarquicas WHERE diagnostico_id = ? AND empresa_id = ?",
                    [$diagnosticoId, Auth::empresa()]
                );
                
                if ($estruturaExistente) {
                    Logger::info('REDIRECIONANDO PARA LISTAGEM - SOPs JÁ EXISTEM', [
                        'diagnostico_id' => $diagnosticoId,
                        'total_sops' => $sopsExistentes['total'],
                        'estrutura_id' => $estruturaExistente['id']
                    ]);
                    
                    // Redirecionar diretamente para a listagem hierárquica
                    header('Location: ' . APP_URL . '/sop/listar-por-diagnostico?diagnostico_id=' . $diagnosticoId);
                    exit;
                }
            }
            
            // Buscar diagnóstico para obter a empresa
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
            
            if (!$diagnostico) {
                Flash::set('erro', 'Diagnóstico não encontrado.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            // Verificar permissão
            if (Auth::perfil() !== 'ADMIN_HOLDING' && $diagnostico['usuario_id'] != Auth::id()) {
                Flash::set('erro', 'Sem permissão para acessar este diagnóstico.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            // CORREÇÃO: Usar empresa do diagnóstico - FORÇAR SELEÇÃO AUTOMÁTICA
            $empresaId = $diagnostico['empresa_id'];
            $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId, $diagnosticoId);
            $dados['diagnostico_especifico'] = $diagnostico;  // Adicionar diagnóstico completo
            $dados['diagnostico_id'] = $diagnosticoId;        // ID disponível na view
            
            $empresa = Empresa::buscarPorId($empresaId);
            if ($empresa) {
                // Carregar departamentos baseados NO DIAGNÓSTICO REAL, não template genérico
                $dados['departamentos'] = $this->getDepartamentosComBaseNoDiagnostico($empresa, $diagnostico, $empresaId);
                
                Logger::info('Empresa PRE-SELECIONADA via diagnóstico', [
                    'diagnostico_id' => $diagnosticoId,
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $empresa['nome'],
                    'total_departamentos' => count($dados['departamentos'])
                ]);
            } else {
                Flash::set('erro', 'Empresa do diagnóstico não encontrada.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            Logger::info('Acessando SOPs via diagnóstico - MODO ESPECÍFICO', [
                'diagnostico_id' => $diagnosticoId,
                'empresa_id' => $empresaId,
                'empresa_nome' => isset($empresa['nome']) ? $empresa['nome'] : 'N/A',
                'departamentos_diagnostico' => $this->extrairDepartamentos($diagnostico)
            ]);
            
        } else {
            // Fluxo original para quando não vem de diagnóstico
            
            // Para ADMIN_HOLDING, mostrar lista de empresas
            if (Auth::perfil() === 'ADMIN_HOLDING') {
                // Buscar todas as empresas
                $empresas = Database::query(
                    "SELECT e.*, 
                            COUNT(s.id) as total_sops,
                            COUNT(CASE WHEN s.status = 'aprovado' THEN 1 END) as aprovados
                     FROM empresas e
                     LEFT JOIN sops s ON e.id = s.empresa_id
                     GROUP BY e.id
                     ORDER BY e.nome"
                );
                
                $dados['empresas_disponiveis'] = $empresas;
                
                // Se há uma empresa na sessão, carregá-la
                if (isset($_SESSION['empresa_selecionada'])) {
                    $empresaId = $_SESSION['empresa_selecionada'];
                    $empresa = Empresa::buscarPorId($empresaId);
                    if ($empresa) {
                        $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
                        $dados['departamentos'] = $this->getDepartamentosPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia', $empresaId);
                    }
                }
            } else {
                // Para outros perfis, usar empresa do usuário
                $empresaId = Auth::garantirEmpresa();
                $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
                $empresa = Empresa::buscarPorId($empresaId);
                if ($empresa) {
                    $dados['departamentos'] = $this->getDepartamentosPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia', $empresaId);
                }
            }
        }

        require VIEW_PATH . '/sop/index.php';
    }
    
    /**
     * Carrega departamentos baseados NO DIAGNÓSTICO REAL da empresa, não templates genéricos
     */
    private function getDepartamentosComBaseNoDiagnostico(array $empresa, array $diagnostico, int $empresaId): array
    {
        Logger::info('Carregando departamentos com base no diagnóstico específico', [
            'empresa_id' => $empresaId,
            'diagnostico_id' => $diagnostico['id'],
            'empresa_nome' => $empresa['nome']
        ]);
        
        // 1. EXTRAIR DEPARTAMENTOS REAIS DO DIAGNÓSTICO COM MÉTODO APRIMORADO
        $departamentosReais = $this->extrairDepartamentosDetalhados($diagnostico);
        
        // Se não encontrou departamentos específicos, usar extração básica melhorada
        if (empty($departamentosReais)) {
            $departamentosTexto = $this->extrairDepartamentos($diagnostico);
            $departamentosArray = $this->parsearDepartamentos($departamentosTexto);
            
            foreach ($departamentosArray as $dept) {
                $departamentosReais[$dept] = [
                    'nome' => $dept,
                    'colaboradores' => ['Quantidade não especificada'],
                    'funcoes_principais' => $this->extrairFuncoesPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'ferramentas_usadas' => $this->extrairFerramentasPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'problemas_identificados' => $this->extrairProblemasPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'objetivos_especificos' => $this->extrairObjetivosPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'processos_principais' => $this->identificarProcessosPrincipais($dept, json_decode($diagnostico['respostas'], true) ?? []),
                    'nivel_maturidade' => $this->calcularMaturidadePorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept)
                ];
            }
        }
        
        // 2. BUSCAR SOPs EXISTENTES NO BANCO (AMBAS ARQUITETURAS)
        $sopsExistentes = $this->buscarSopsCompletos($empresaId);
        $sopsMap = [];
        
        foreach ($sopsExistentes as $sop) {
            // Para SOPs da nova arquitetura, usar o nome do serviço como chave
            if ($sop['origem'] === 'nova_arquitetura') {
                $chave = $sop['nome']; // Nome do serviço
                $sopsMap[$chave] = [
                    'id' => $sop['id'],
                    'nome' => $sop['nome'],
                    'status' => 'aprovado', // SOPs novos são considerados aprovados
                    'sop_codigo' => $sop['sop_codigo'],
                    'origem' => 'nova_arquitetura'
                ];
            } else {
                // Para SOPs da arquitetura antiga, usar sop_codigo
                $chave = $sop['sop_codigo'];
                $status = 'nao_gerado';
                switch($sop['status']) {
                    case 'ativo':
                        $status = 'aprovado';
                        break;
                    case 'rascunho':
                        $status = 'gerado';
                        break;
                    default:
                        $status = 'nao_gerado';
                        break;
                }
                
                $sopsMap[$chave] = [
                    'id' => $sop['id'],
                    'nome' => $sop['nome'],
                    'status' => $status,
                    'sop_codigo' => $sop['sop_codigo'],
                    'origem' => 'arquitetura_antiga'
                ];
            }
        }
        
        // 3. CRIAR SOPs ESPECÍFICOS PARA CADA DEPARTAMENTO REAL DA EMPRESA
        $departamentosEstruturados = [];
        
        foreach ($departamentosReais as $nomeDepartamento => $detalhes) {
            $iconeDept = $this->getIconePorDepartamento($nomeDepartamento);
            $sopsEspecificos = $this->criarSOPsEspecificosPorDepartamento($nomeDepartamento, $detalhes, $empresa, $diagnostico);
            
            // CORREÇÃO: Aplicar status dos SOPs existentes - verificar por código e por nome
            foreach ($sopsEspecificos as &$sop) {
                $codigoSOP = $sop['id']; // Este é o código SOP
                $nomeSOP = $sop['nome']; // Nome do SOP
                
                // Primeiro tentar mapear por código (arquitetura antiga)
                if (isset($sopsMap[$codigoSOP])) {
                    $sop['status'] = $sopsMap[$codigoSOP]['status'];
                    $sop['id'] = $sopsMap[$codigoSOP]['id'];
                    $sop['sop_codigo'] = $codigoSOP;
                    $sop['origem'] = $sopsMap[$codigoSOP]['origem'];
                }
                // Depois tentar mapear por nome (nova arquitetura)
                elseif (isset($sopsMap[$nomeSOP])) {
                    $sop['status'] = $sopsMap[$nomeSOP]['status'];
                    $sop['id'] = $sopsMap[$nomeSOP]['id'];
                    $sop['sop_codigo'] = $sopsMap[$nomeSOP]['sop_codigo'];
                    $sop['origem'] = $sopsMap[$nomeSOP]['origem'];
                }
                else {
                    $sop['status'] = 'nao_gerado';
                    $sop['sop_codigo'] = $codigoSOP;
                    $sop['origem'] = 'arquitetura_antiga'; // Default
                }
            }
            
            $departamentosEstruturados[] = [
                'nome' => $nomeDepartamento,
                'icone' => $iconeDept,
                'sops' => $sopsEspecificos,
                'contexto_real' => $detalhes,  // Informações reais do diagnóstico
                'total_sops' => count($sopsEspecificos)
            ];
        }
        
        Logger::info('Departamentos criados com base no diagnóstico', [
            'total_departamentos' => count($departamentosEstruturados),
            'departamentos' => array_column($departamentosEstruturados, 'nome'),
            'total_sops_mapeados' => count($sopsMap),
            'exemplo_mapeamento' => array_slice($sopsMap, 0, 3), // Alguns exemplos para debug
            'total_sops' => array_sum(array_column($departamentosEstruturados, 'total_sops'))
        ]);
        
        return $departamentosEstruturados;
    }
    
    /**
     * Extrai departamentos REAIS e DETALHADOS do diagnóstico - NOVA ESTRUTURA PROFISSIONAL
     */
    private function extrairDepartamentosDetalhados(array $diagnostico): array
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        if (!$respostas) {
            Logger::warning('Respostas do diagnóstico vazias ou inválidas', ['diagnostico_id' => $diagnostico['id']]);
            $respostas = [];
        }
        
        // 1. IDENTIFICAR O NICHO DA EMPRESA
        $nicho = $this->identificarNichoEmpresa($respostas, $diagnostico);
        
        // 2. ESTRUTURA BASE (10 SETORES OBRIGATÓRIOS) + SETORES ESPECÍFICOS DO NICHO
        $estruturaCompleta = $this->criarEstruturaCompletaPorNicho($nicho, $respostas);
        
        Logger::info('Estrutura empresarial COMPLETA criada', [
            'diagnostico_id' => $diagnostico['id'],
            'nicho_identificado' => $nicho,
            'setores_base' => count($estruturaCompleta['setores_base']),
            'setores_especificos' => count($estruturaCompleta['setores_especificos']),
            'total_setores' => count($estruturaCompleta['todos_setores'])
        ]);
        
        $departamentos = [];
        
        // 3. PROCESSAR TODOS OS SETORES (BASE + ESPECÍFICOS)
        foreach ($estruturaCompleta['todos_setores'] as $setor => $config) {
            $departamentos[$setor] = [
                'nome' => $setor,
                'tipo' => $config['tipo'], // 'base' ou 'especifico'
                'descricao' => $config['descricao'],
                'colaboradores' => $this->extrairColaboradoresPorSetor($respostas, $setor),
                'funcoes_principais' => $config['funcoes_principais'],
                'ferramentas_usadas' => $this->identificarFerramentasPorSetor($respostas, $setor),
                'problemas_identificados' => $this->identificarProblemasPorSetor($respostas, $setor),
                'objetivos_especificos' => $this->definirObjetivosPorSetor($setor, $respostas),
                'processos_principais' => $config['sops_padrao'], // SOPs específicos do setor
                'nivel_maturidade' => $this->calcularMaturidadePorSetor($respostas, $setor),
                'kpis_essenciais' => $config['kpis_essenciais'],
                'nicho_origem' => $nicho
            ];
            
            Logger::info('Setor processado', [
                'setor' => $setor,
                'tipo' => $config['tipo'],
                'total_sops' => count($config['sops_padrao']),
                'sops' => $config['sops_padrao']
            ]);
        }
        
        Logger::info('ESTRUTURA EMPRESARIAL COMPLETA processada', [
            'nicho' => $nicho,
            'total_setores' => count($departamentos),
            'setores_nomes' => array_keys($departamentos),
            'total_sops_geral' => array_sum(array_map(function($d) { return count($d['processos_principais']); }, $departamentos))
        ]);
        
        return $departamentos;
    }
    
    /**
     * Identifica o nicho da empresa baseado no diagnóstico
     */
    private function identificarNichoEmpresa(array $respostas, array $diagnostico): string
    {
        // 1. Primeiro, verificar se há campo direto de segmento
        if (isset($respostas['segmento']) && !empty($respostas['segmento'])) {
            $segmento = strtolower(trim($respostas['segmento']));
            $nichoMapeado = $this->mapearSegmentoParaNicho($segmento);
            if ($nichoMapeado) {
                Logger::info('Nicho identificado via segmento direto', ['segmento' => $segmento, 'nicho' => $nichoMapeado]);
                return $nichoMapeado;
            }
        }
        
        // 2. Analisar campos de texto para identificar o nicho
        $camposParaAnalise = [
            'atividade_principal', 'ramo_atividade', 'area_atuacao', 'produtos_servicos',
            'descricao_empresa', 'principal_produto', 'atividade_empresa', 'setor_empresa'
        ];
        
        $textoCompleto = '';
        foreach ($camposParaAnalise as $campo) {
            if (isset($respostas[$campo]) && is_string($respostas[$campo])) {
                $textoCompleto .= ' ' . strtolower($respostas[$campo]);
            }
        }
        
        // 3. Detectar nicho por palavras-chave
        $nichosDeteccao = [
            'construção' => ['construc', 'obra', 'engenharia', 'edificac', 'reforma', 'construtora', 'empreiteira'],
            'saúde' => ['saude', 'saúde', 'clinica', 'clínica', 'hospital', 'médic', 'medic', 'odonto', 'fisiotera', 'psicolog'],
            'ecommerce' => ['ecommerce', 'e-commerce', 'loja virtual', 'marketplace', 'vendas online', 'varejo online'],
            'educação' => ['educac', 'educação', 'escola', 'curso', 'ensino', 'treinamento', 'universidade', 'faculdade'],
            'alimentação' => ['restaurante', 'lanchonete', 'padaria', 'alimentac', 'comida', 'culinária', 'gastronomia'],
            'imobiliário' => ['imobil', 'corretora', 'imovel', 'imóvel', 'locacao', 'locação', 'venda casa'],
            'advocacia' => ['advocacia', 'advogad', 'juridic', 'jurídic', 'direito', 'escritório juríd'],
            'tecnologia' => ['software', 'tecnologia', 'sistema', 'desenvolvimento', 'programac', 'ti ', 'tech'],
            'beleza' => ['beleza', 'estética', 'salao', 'salão', 'barbearia', 'manicure', 'cabeleireiro'],
            'fitness' => ['academia', 'fitness', 'musculac', 'musculação', 'personal trainer', 'exercício'],
            'turismo' => ['turismo', 'hotel', 'pousada', 'viagem', 'hospitalidade', 'hotelaria'],
            'indústria' => ['industria', 'indústria', 'fabrica', 'fábrica', 'manufactura', 'produc industrial'],
            'logística' => ['logistic', 'logística', 'transporte', 'entrega', 'frete', 'distribuidora'],
            'consultoria' => ['consultoria', 'consultor', 'assessoria', 'serviços profissionais'],
            'financeiro' => ['financ', 'banco', 'credito', 'crédito', 'fintech', 'pagamento', 'cobrança'],
            'marketing' => ['marketing', 'agência', 'publicidade', 'propaganda', 'comunicac'],
            'automotivo' => ['automotiv', 'carro', 'veículo', 'oficina', 'mecânica', 'concessionária'],
            'agronegócio' => ['agro', 'fazenda', 'rural', 'agricola', 'agrícola', 'pecuária', 'campo'],
            'ong' => ['ong', 'social', 'beneficente', 'beneficiente', 'caridade', 'filantropia']
        ];
        
        foreach ($nichosDeteccao as $nicho => $palavrasChave) {
            foreach ($palavrasChave as $palavra) {
                if (strpos($textoCompleto, $palavra) !== false) {
                    Logger::info('Nicho identificado via análise de texto', [
                        'nicho' => $nicho,
                        'palavra_detectada' => $palavra,
                        'texto_analisado' => substr($textoCompleto, 0, 200)
                    ]);
                    return $nicho;
                }
            }
        }
        
        // 4. Fallback para tecnologia (mais comum)
        Logger::info('Nicho não identificado, usando fallback', ['nicho_fallback' => 'tecnologia']);
        return 'tecnologia';
    }
    
    /**
     * Mapeia segmento direto para nicho
     */
    private function mapearSegmentoParaNicho(string $segmento): ?string
    {
        $mapeamento = [
            'tecnologia' => 'tecnologia',
            'tech' => 'tecnologia', 
            'software' => 'tecnologia',
            'saúde' => 'saúde',
            'saude' => 'saúde',
            'medicina' => 'saúde',
            'construção' => 'construção',
            'construcao' => 'construção',
            'engenharia' => 'construção',
            'educação' => 'educação',
            'educacao' => 'educação',
            'ensino' => 'educação',
            'varejo' => 'ecommerce',
            'comércio' => 'ecommerce',
            'comercio' => 'ecommerce',
            'indústria' => 'indústria',
            'industria' => 'indústria',
            'serviços' => 'consultoria',
            'servicos' => 'consultoria'
        ];
        
        return $mapeamento[$segmento] ?? null;
    }
    
    /**
     * Cria SOPs específicos para um setor baseado na estrutura profissional
     */
    private function criarSOPsEspecificosPorDepartamento(string $setor, array $detalhes, array $empresa, array $diagnostico): array
    {
        $processos = $detalhes['processos_principais'];
        $sops = [];
        $contador = 1;
        
        // GERAR SOPs baseados nos processos PROFISSIONAIS do setor
        foreach ($processos as $processo) {
            $codigoSOP = $this->gerarCodigoSOPProfissional($empresa['segmento'], $setor, $processo, $contador);
            
            $sops[] = [
                'id' => $codigoSOP,  // Código SOP (será substituído por ID do banco se existir)
                'nome' => $processo,
                'status' => 'nao_gerado',
                'departamento' => $setor,
                'contexto_especifico' => [
                    'funcoes' => $detalhes['funcoes_principais'],
                    'ferramentas' => $detalhes['ferramentas_usadas'],
                    'problemas' => $detalhes['problemas_identificados'],
                    'objetivos' => $detalhes['objetivos_especificos'],
                    'maturidade' => $detalhes['nivel_maturidade'],
                    'kpis_essenciais' => $detalhes['kpis_essenciais'],
                    'tipo_setor' => $detalhes['tipo'], // 'base' ou 'especifico'
                    'nicho_origem' => $detalhes['nicho_origem']
                ],
                'customizado' => true,
                'origem' => 'estrutura_profissional',
                'setor_empresa' => $empresa['segmento'] ?? 'Geral',
                'tipo_setor' => $detalhes['tipo']
            ];
            $contador++;
        }
        
        Logger::info('SOPs profissionais criados para setor', [
            'setor' => $setor,
            'tipo' => $detalhes['tipo'],
            'total_sops' => count($sops),
            'processos' => $processos,
            'empresa_nicho' => $detalhes['nicho_origem'] ?? 'não identificado'
        ]);
        
        return $sops;
    }
    
    /**
     * Gera código SOP profissional baseado na nova estrutura
     */
    private function gerarCodigoSOPProfissional(string $segmento, string $setor, string $processo, int $contador): string
    {
        // Prefixo do segmento da empresa
        $prefixoSegmento = 'GER'; // default
        switch(strtolower($segmento)) {
            case 'tecnologia':
            case 'tech':
            case 'software':
                $prefixoSegmento = 'TEC';
                break;
            case 'saúde':
            case 'saude':
            case 'medicina':
                $prefixoSegmento = 'SAU';
                break;
            case 'construção':
            case 'construcao':
            case 'engenharia':
                $prefixoSegmento = 'CON';
                break;
            case 'educação':
            case 'educacao':
            case 'ensino':
                $prefixoSegmento = 'EDU';
                break;
            case 'varejo':
            case 'ecommerce':
            case 'comercio':
                $prefixoSegmento = 'VAR';
                break;
            case 'indústria':
            case 'industria':
            case 'fabrica':
                $prefixoSegmento = 'IND';
                break;
            case 'logística':
            case 'logistica':
            case 'transporte':
                $prefixoSegmento = 'LOG';
                break;
            case 'alimentação':
            case 'alimentacao':
            case 'restaurante':
                $prefixoSegmento = 'ALI';
                break;
            case 'imobiliário':
            case 'imobiliario':
                $prefixoSegmento = 'IMO';
                break;
            case 'advocacia':
            case 'juridico':
                $prefixoSegmento = 'ADV';
                break;
            case 'beleza':
            case 'estetica':
                $prefixoSegmento = 'BEL';
                break;
            case 'fitness':
            case 'academia':
                $prefixoSegmento = 'FIT';
                break;
            case 'consultoria':
            case 'servicos':
                $prefixoSegmento = 'CON';
                break;
        }
        
        // Código do setor (primeiras letras significativas)
        $codigoSetor = $this->gerarCodigoSetor($setor);
        
        // Código do processo
        $codigoProcesso = $this->gerarCodigoProcesso($processo);
        
        return sprintf('SOP-%s-%s-%s-%03d', $prefixoSegmento, $codigoSetor, $codigoProcesso, $contador);
    }
    
    /**
     * Gera código específico para o setor
     */
    private function gerarCodigoSetor(string $setor): string
    {
        // Mapear setores para códigos de 3 letras
        $codigosSetor = [
            // Setores Base
            'CAPTAÇÃO / MARKETING' => 'MKT',
            'COMERCIAL / VENDAS' => 'COM',
            'FINANCEIRO' => 'FIN',
            'ATENDIMENTO' => 'ATE',
            'SUPORTE' => 'SUP',
            'OPERACIONAL / PRODUÇÃO' => 'OPS',
            'RH / GESTÃO DE PESSOAS' => 'RH',
            'ADMINISTRATIVO' => 'ADM',
            'TI / INFRAESTRUTURA' => 'TI',
            'QUALIDADE / MELHORIA CONTÍNUA' => 'QUA',
            
            // Setores Específicos - Construção
            'ORÇAMENTAÇÃO E ENGENHARIA DE CUSTOS' => 'ORC',
            'SUPRIMENTOS / COMPRAS DE OBRA' => 'SUP',
            'GESTÃO DE OBRAS / CAMPO' => 'OBR',
            'SEGURANÇA DO TRABALHO (SESMT)' => 'SEG',
            
            // Setores Específicos - Saúde
            'AGENDAMENTO E RECEPÇÃO' => 'AGE',
            'PRONTUÁRIO E COMPLIANCE (LGPD)' => 'PRO',
            'CORPO CLÍNICO' => 'CLI',
            
            // Setores Específicos - E-commerce
            'GESTÃO DE MARKETPLACE' => 'MAR',
            'LOGÍSTICA E FULFILLMENT' => 'LOG',
            'PÓS-VENDA / TROCAS E DEVOLUÇÕES' => 'DEV',
            
            // Setores Específicos - Educação
            'PEDAGÓGICO / CONTEÚDO' => 'PED',
            'SECRETARIA ACADÊMICA' => 'SEC',
            
            // Setores Específicos - Tecnologia
            'PRODUTO' => 'PRD',
            'DESENVOLVIMENTO / ENGENHARIA' => 'DEV',
            'CUSTOMER SUCCESS' => 'CS'
        ];
        
        return $codigosSetor[$setor] ?? 'GEN';
    }
    
    /**
     * Gera código específico para o processo
     */
    private function gerarCodigoProcesso(string $processo): string
    {
        $processoLower = strtolower($processo);
        
        // Detectar tipo de processo pelas palavras-chave
        if (strpos($processoLower, 'lead') !== false || strpos($processoLower, 'prospec') !== false) {
            return 'LED';
        } elseif (strpos($processoLower, 'venda') !== false || strpos($processoLower, 'comercial') !== false) {
            return 'VEN';
        } elseif (strpos($processoLower, 'atend') !== false || strpos($processoLower, 'suporte') !== false) {
            return 'ATE';
        } elseif (strpos($processoLower, 'financ') !== false || strpos($processoLower, 'fluxo') !== false) {
            return 'FIN';
        } elseif (strpos($processoLower, 'qualidade') !== false || strpos($processoLower, 'controle') !== false) {
            return 'QUA';
        } elseif (strpos($processoLower, 'produto') !== false || strpos($processoLower, 'desenvolvimen') !== false) {
            return 'PRD';
        } elseif (strpos($processoLower, 'onboard') !== false || strpos($processoLower, 'integra') !== false) {
            return 'ONB';
        } elseif (strpos($processoLower, 'backup') !== false || strpos($processoLower, 'segur') !== false) {
            return 'SEC';
        } elseif (strpos($processoLower, 'recrutam') !== false || strpos($processoLower, 'seleç') !== false) {
            return 'REC';
        } elseif (strpos($processoLower, 'treina') !== false || strpos($processoLower, 'capaci') !== false) {
            return 'TRE';
        } else {
            // Código genérico baseado nas primeiras letras
            $palavras = explode(' ', $processo);
            $codigo = '';
            foreach ($palavras as $palavra) {
                if (strlen($palavra) > 2) {
                    $codigo .= strtoupper(substr($palavra, 0, 1));
                    if (strlen($codigo) >= 3) break;
                }
            }
            return strlen($codigo) >= 3 ? $codigo : 'GEN';
        }
    }
    
    /**
     * Extrai colaboradores por setor específico
     */
    private function extrairColaboradoresPorSetor(array $respostas, string $setor): array
    {
        // Tentar extrair informações específicas por setor
        $colaboradoresGerais = $respostas['colaboradores'] ?? $respostas['numero_colaboradores'] ?? 'Não informado';
        
        // Se há informação específica por departamento, usar
        $setorLower = strtolower($setor);
        if (isset($respostas['colaboradores_' . $setorLower])) {
            return $respostas['colaboradores_' . $setorLower];
        }
        
        return [$colaboradoresGerais];
    }
    
    /**
     * Identifica ferramentas por setor baseado no diagnóstico
     */
    private function identificarFerramentasPorSetor(array $respostas, string $setor): array
    {
        $ferramentasGerais = $respostas['ferramentas'] ?? $respostas['sistemas_utilizados'] ?? '';
        $ferramentasArray = array_map('trim', explode(',', $ferramentasGerais));
        
        // Filtrar ferramentas relevantes por setor
        $ferramentasSetor = [];
        $setorLower = strtolower($setor);
        
        foreach ($ferramentasArray as $ferramenta) {
            $ferramentaLower = strtolower($ferramenta);
            
            // Ferramentas gerais que se aplicam a qualquer setor
            if (strpos($ferramentaLower, 'email') !== false || 
                strpos($ferramentaLower, 'whatsapp') !== false ||
                strpos($ferramentaLower, 'excel') !== false ||
                strpos($ferramentaLower, 'google') !== false) {
                $ferramentasSetor[] = $ferramenta;
            }
            
            // Ferramentas específicas por setor
            if (strpos($setorLower, 'marketing') !== false || strpos($setorLower, 'captação') !== false) {
                if (strpos($ferramentaLower, 'facebook') !== false ||
                    strpos($ferramentaLower, 'instagram') !== false ||
                    strpos($ferramentaLower, 'ads') !== false ||
                    strpos($ferramentaLower, 'analytics') !== false) {
                    $ferramentasSetor[] = $ferramenta;
                }
            }
            
            if (strpos($setorLower, 'comercial') !== false || strpos($setorLower, 'vendas') !== false) {
                if (strpos($ferramentaLower, 'crm') !== false ||
                    strpos($ferramentaLower, 'pipeline') !== false ||
                    strpos($ferramentaLower, 'vendas') !== false) {
                    $ferramentasSetor[] = $ferramenta;
                }
            }
            
            if (strpos($setorLower, 'financeiro') !== false) {
                if (strpos($ferramentaLower, 'contabil') !== false ||
                    strpos($ferramentaLower, 'erp') !== false ||
                    strpos($ferramentaLower, 'banco') !== false) {
                    $ferramentasSetor[] = $ferramenta;
                }
            }
        }
        
        return !empty($ferramentasSetor) ? array_unique($ferramentasSetor) : ['Ferramentas básicas de escritório'];
    }
    
    /**
     * Identifica problemas específicos por setor
     */
    private function identificarProblemasPorSetor(array $respostas, string $setor): array
    {
        $problemasGerais = $respostas['pontos_melhoria'] ?? $respostas['principais_problemas'] ?? '';
        $problemasArray = array_map('trim', explode(',', $problemasGerais));
        
        // Problemas padrão por setor se não houver específicos
        $problemasSetor = [
            'CAPTAÇÃO / MARKETING' => 'Baixa geração de leads qualificados',
            'COMERCIAL / VENDAS' => 'Taxa de conversão baixa',
            'FINANCEIRO' => 'Controles manuais e fluxo de caixa',
            'ATENDIMENTO' => 'Tempo de resposta elevado',
            'SUPORTE' => 'Falta de base de conhecimento',
            'OPERACIONAL / PRODUÇÃO' => 'Processos não padronizados',
            'RH / GESTÃO DE PESSOAS' => 'Falta de treinamento estruturado',
            'ADMINISTRATIVO' => 'Documentação desorganizada',
            'TI / INFRAESTRUTURA' => 'Sistemas não integrados',
            'QUALIDADE / MELHORIA CONTÍNUA' => 'Falta de indicadores'
        ];
        
        $problemaPadrao = $problemasSetor[$setor] ?? 'Necessita padronização de processos';
        
        return array_filter(array_merge($problemasArray, [$problemaPadrao]));
    }
    
    /**
     * Define objetivos específicos por setor
     */
    private function definirObjetivosPorSetor(string $setor, array $respostas): array
    {
        $objetivoGeral = $respostas['objetivo_12_meses'] ?? 'Estruturar e otimizar processos';
        
        // Objetivos específicos por setor
        $objetivosSetor = [
            'CAPTAÇÃO / MARKETING' => ['Aumentar geração de leads em 50%', 'Melhorar ROI das campanhas'],
            'COMERCIAL / VENDAS' => ['Aumentar taxa de conversão', 'Reduzir ciclo de vendas'],
            'FINANCEIRO' => ['Automatizar controles financeiros', 'Reduzir inadimplência'],
            'ATENDIMENTO' => ['Melhorar satisfação do cliente', 'Reduzir tempo de resposta'],
            'SUPORTE' => ['Aumentar first call resolution', 'Criar base de conhecimento'],
            'OPERACIONAL / PRODUÇÃO' => ['Padronizar processos produtivos', 'Melhorar qualidade'],
            'RH / GESTÃO DE PESSOAS' => ['Estruturar processo seletivo', 'Reduzir turnover'],
            'ADMINISTRATIVO' => ['Digitalizar documentação', 'Otimizar rotinas administrativas'],
            'TI / INFRAESTRUTURA' => ['Modernizar infraestrutura', 'Implementar backup automático'],
            'QUALIDADE / MELHORIA CONTÍNUA' => ['Implementar sistema de qualidade', 'Criar indicadores']
        ];
        
        $objetivosEspecificos = $objetivosSetor[$setor] ?? ['Estruturar processos do setor'];
        
        return array_merge([$objetivoGeral], $objetivosEspecificos);
    }
    
    /**
     * Calcula maturidade específica por setor
     */
    private function calcularMaturidadePorSetor(array $respostas, string $setor): int
    {
        // Maturidade geral da empresa
        $maturidadeGeral = $respostas['maturidade_percebida'] ?? 2;
        
        // Ajustes por setor baseado em características típicas
        $ajustesSetor = [
            'TI / INFRAESTRUTURA' => 1,  // TI geralmente mais maduro
            'FINANCEIRO' => 0,           // Financeiro costuma ser estruturado
            'CAPTAÇÃO / MARKETING' => -1, // Marketing pode ser menos estruturado
            'QUALIDADE / MELHORIA CONTÍNUA' => -1, // Qualidade vem depois
        ];
        
        $ajuste = $ajustesSetor[$setor] ?? 0;
        $maturidadeSetor = $maturidadeGeral + $ajuste;
        
        return max(1, min(4, $maturidadeSetor));
    }
    
    /**
     * Método legado mantido para compatibilidade
     */
    private function identificarProcessosPrincipais(string $dept, array $respostas): array
    {
        // Este método agora é usado apenas para compatibilidade
        // A nova estrutura usa os SOPs definidos na estrutura profissional
        return ['Processo Operacional Padrão'];
    }
    
    /**
     * Métodos auxiliares para extração de dados específicos
     */
    private function extrairColaboradoresPorDepartamento(array $respostas, string $dept): array
    {
        // Extrair colaboradores específicos por departamento baseado nas respostas
        return isset($respostas['colaboradores_' . strtolower($dept)]) ? $respostas['colaboradores_' . strtolower($dept)] : ['Quantidade não especificada'];
    }
    
    private function extrairFuncoesPorDepartamento(array $respostas, string $dept): array
    {
        $funcoesPadrao = [
            'Comercial' => ['Prospecção', 'Vendas', 'Relacionamento com clientes'],
            'TI' => ['Suporte técnico', 'Desenvolvimento', 'Infraestrutura'],
            'Operações' => ['Produção', 'Qualidade', 'Logística'],
            'Financeiro' => ['Contas a pagar/receber', 'Fluxo de caixa', 'Controles'],
            'RH' => ['Recrutamento', 'Treinamento', 'Gestão de pessoas']
        ];
        
        // Tentar extrair do diagnóstico, senão usar padrão
        $funcoes = isset($respostas['funcoes_' . strtolower($dept)]) ? $respostas['funcoes_' . strtolower($dept)] : null;
        if ($funcoes) {
            return $funcoes;
        }
        return isset($funcoesPadrao[$dept]) ? $funcoesPadrao[$dept] : ['Funções operacionais'];
    }
    
    private function extrairFerramentasPorDepartamento(array $respostas, string $dept): array
    {
        $ferramentasGerais = explode(',', $this->extrairFerramentas(['respostas' => json_encode($respostas)]));
        return array_map('trim', $ferramentasGerais);
    }
    
    private function extrairProblemasPorDepartamento(array $respostas, string $dept): array
    {
        $problemasGerais = $this->extrairProblemas(['respostas' => json_encode($respostas)]);
        return explode(',', $problemasGerais);
    }
    
    private function extrairObjetivosPorDepartamento(array $respostas, string $dept): array
    {
        $objetivosGerais = $this->extrairObjetivos(['respostas' => json_encode($respostas)]);
        return explode(',', $objetivosGerais);
    }
    
    /**
     * Cria estrutura empresarial COMPLETA: 10 setores base + setores específicos do nicho
     */
    private function criarEstruturaCompletaPorNicho(string $nicho, array $respostas): array
    {
        // 1. ESTRUTURA BASE (10 SETORES OBRIGATÓRIOS - TODA EMPRESA TEM)
        $setoresBase = [
            'CAPTAÇÃO / MARKETING' => [
                'tipo' => 'base',
                'descricao' => 'Geração de leads, tráfego, prospecção, branding',
                'funcoes_principais' => ['Geração de leads', 'Gestão de tráfego', 'Prospecção ativa', 'Branding e posicionamento'],
                'sops_padrao' => [
                    'Geração e Qualificação de Leads Inbound',
                    'Gestão de Campanhas Digitais Pagas',
                    'Prospecção Ativa Outbound B2B',
                    'Prospecção Ativa Outbound B2C',
                    'Construção e Gestão de Brand Awareness',
                    'Análise e Otimização de Métricas de Marketing',
                    'Gestão de Conteúdo para Redes Sociais',
                    'Planejamento e Execução de Email Marketing',
                    'SEO - Otimização para Mecanismos de Busca',
                    'Gestão de Marketing de Influenciadores',
                    'Criação e Gestão de Landing Pages',
                    'Automação de Marketing e Nutrição de Leads',
                    'Pesquisa de Mercado e Análise de Concorrência',
                    'Gestão de Eventos e Webinars',
                    'Marketing de Relacionamento e CRM',
                    'Planejamento de Campanhas Sazonais',
                    'Gestão de Parcerias e Co-marketing',
                    'Análise de ROI e Attribution Modeling'
                ],
                'kpis_essenciais' => ['Taxa de conversão de leads', 'CAC (Custo de Aquisição)', 'ROI de campanhas']
            ],
            'COMERCIAL / VENDAS' => [
                'tipo' => 'base',
                'descricao' => 'Qualificação, negociação, fechamento',
                'funcoes_principais' => ['Qualificação de leads', 'Negociação comercial', 'Fechamento de vendas', 'Gestão de pipeline'],
                'sops_padrao' => [
                    'Qualificação e Descoberta de Necessidades',
                    'Follow-up Estruturado de Leads',
                    'Apresentação de Propostas Comerciais',
                    'Negociação de Preços e Condições',
                    'Fechamento e Assinatura de Contratos',
                    'Gestão de Pipeline de Vendas',
                    'Onboarding de Novos Clientes',
                    'Gestão de Relacionamento com Prospects',
                    'Tratamento de Objeções Comerciais',
                    'Upselling e Cross-selling',
                    'Renovação de Contratos Existentes',
                    'Gestão de Vendas Consultivas Complexas',
                    'Prospecção e Qualificação BANT',
                    'Gestão de Ciclo de Vendas B2B',
                    'Vendas por Telefone (Inside Sales)',
                    'Vendas Presenciais (Outside Sales)',
                    'Gestão de Propostas e RFPs',
                    'Análise de Performance de Vendas',
                    'Gestão de Territorial e Cotas',
                    'Treinamento e Capacitação de Vendedores'
                ],
                'kpis_essenciais' => ['Taxa de conversão', 'Ticket médio', 'Ciclo de vendas']
            ],
            'FINANCEIRO' => [
                'tipo' => 'base', 
                'descricao' => 'Contas a pagar/receber, fluxo de caixa, cobrança, fiscal',
                'funcoes_principais' => ['Controle de fluxo de caixa', 'Contas a pagar/receber', 'Cobrança', 'Controle fiscal'],
                'sops_padrao' => [
                    'Controle de Fluxo de Caixa Diário',
                    'Gestão de Contas a Pagar',
                    'Gestão de Contas a Receber',
                    'Processo de Cobrança Preventiva',
                    'Tratamento de Inadimplência e Negociação',
                    'Conciliação Bancária e Financeira',
                    'Controle e Apuração Fiscal',
                    'Elaboração de Demonstrativos Financeiros',
                    'Análise de Crédito de Clientes',
                    'Gestão de Orçamento e Budget',
                    'Controle de Custos e Despesas',
                    'Análise de Rentabilidade por Produto/Serviço',
                    'Gestão de Investimentos e Aplicações',
                    'Planejamento Tributário',
                    'Auditoria Interna Financeira',
                    'Gestão de Relacionamento Bancário',
                    'Controle de Margem e Markup',
                    'Análise de Viabilidade de Projetos',
                    'Gestão de Capital de Giro',
                    'Emissão e Controle de Notas Fiscais'
                ],
                'kpis_essenciais' => ['DRE mensal', 'Inadimplência (%)', 'Prazo médio recebimento']
            ],
            'ATENDIMENTO' => [
                'tipo' => 'base',
                'descricao' => 'Pré e pós-venda, relacionamento com cliente',
                'funcoes_principais' => ['Atendimento pré-venda', 'Suporte pós-venda', 'Relacionamento com clientes'],
                'sops_padrao' => [
                    'Atendimento Receptivo e Ativo',
                    'Suporte Técnico Especializado',
                    'Gestão de Relacionamento Pós-Venda',
                    'Tratamento de Reclamações e Conflitos',
                    'Processo de Retenção de Clientes',
                    'Pesquisa de Satisfação e NPS',
                    'Atendimento Multicanal (Telefone, Chat, Email)',
                    'Gestão de Base de Conhecimento',
                    'Escalação de Problemas Complexos',
                    'Programas de Fidelização',
                    'Atendimento VIP e Clientes Estratégicos',
                    'Gestão de Expectativas do Cliente',
                    'Comunicação Proativa com Clientes',
                    'Atendimento de Urgências e Emergências',
                    'Recuperação de Clientes Insatisfeitos',
                    'Gestão de Feedback e Melhorias',
                    'Atendimento Personalizado por Perfil',
                    'Gestão de SLA de Atendimento',
                    'Treinamento em Excelência no Atendimento'
                ],
                'kpis_essenciais' => ['NPS', 'Tempo de resposta', 'Taxa de retenção']
            ],
            'SUPORTE' => [
                'tipo' => 'base',
                'descricao' => 'Resolução de problemas, dúvidas técnicas, SAC',
                'funcoes_principais' => ['Resolução de problemas técnicos', 'Suporte especializado', 'Base de conhecimento'],
                'sops_padrao' => [
                    'Atendimento de Chamados Técnicos Nível 1',
                    'Atendimento de Chamados Técnicos Nível 2',
                    'Escalação de Problemas Complexos',
                    'Gestão de Base de Conhecimento Técnico',
                    'Suporte Remoto e Acesso Seguro',
                    'Suporte Presencial e On-site',
                    'Controle e Cumprimento de SLA',
                    'Diagnóstico e Troubleshooting Avançado',
                    'Gestão de Incidentes Críticos',
                    'Suporte a Integrações e APIs',
                    'Documentação Técnica de Soluções',
                    'Treinamento de Usuários Finais',
                    'Monitoramento Preventivo de Sistemas',
                    'Gestão de Patches e Atualizações',
                    'Backup e Recovery de Dados',
                    'Suporte a Migrações de Sistema',
                    'Análise de Performance e Otimização',
                    'Gestão de Ambiente de Desenvolvimento',
                    'Suporte a Ferramentas de Colaboração'
                ],
                'kpis_essenciais' => ['Tempo médio de resolução', 'First Call Resolution', 'Satisfação suporte']
            ],
            'OPERACIONAL / PRODUÇÃO' => [
                'tipo' => 'base',
                'descricao' => 'Entrega do produto/serviço em si',
                'funcoes_principais' => ['Produção/execução', 'Controle de qualidade', 'Entrega final'],
                'sops_padrao' => [
                    'Planejamento e Programação de Produção',
                    'Execução e Controle de Processos Produtivos',
                    'Controle de Qualidade e Inspeções',
                    'Gestão de Projetos e Entregas',
                    'Processo de Melhoria Contínua',
                    'Gestão de Recursos e Capacidade Produtiva',
                    'Controle de Estoque de Matéria-Prima',
                    'Gestão de Fornecedores e Terceirizados',
                    'Manutenção Preventiva e Corretiva',
                    'Controle de Custos de Produção',
                    'Gestão de Cronogramas e Prazos',
                    'Controle de Desperdícios e Perdas',
                    'Padronização de Processos Operacionais',
                    'Gestão de Equipes de Produção',
                    'Controle de Segurança Operacional',
                    'Gestão de Indicadores de Performance',
                    'Otimização de Layout e Fluxos',
                    'Controle de Documentação Técnica',
                    'Gestão de Capacitação Operacional'
                ],
                'kpis_essenciais' => ['Tempo de entrega', 'Índice de qualidade', 'Produtividade']
            ],
            'RH / GESTÃO DE PESSOAS' => [
                'tipo' => 'base',
                'descricao' => 'Recrutamento, treinamento, cultura',
                'funcoes_principais' => ['Recrutamento e seleção', 'Treinamento', 'Gestão de performance', 'Cultura organizacional'],
                'sops_padrao' => [
                    'Processo de Recrutamento e Seleção',
                    'Onboarding de Novos Colaboradores',
                    'Gestão de Performance e Avaliações',
                    'Treinamento e Desenvolvimento Profissional',
                    'Gestão de Clima e Cultura Organizacional',
                    'Administração de Pessoal e Folha de Pagamento',
                    'Gestão de Benefícios e Compensação',
                    'Controle de Frequência e Ponto Eletrônico',
                    'Gestão de Férias e Ausências',
                    'Processo Disciplinar e Desligamentos',
                    'Desenvolvimento de Lideranças',
                    'Gestão de Carreiras e Sucessão',
                    'Pesquisas de Clima e Engajamento',
                    'Gestão de Conflitos Interpessoais',
                    'Compliance Trabalhista e Legal',
                    'Gestão de Saúde e Segurança do Trabalho',
                    'Programas de Qualidade de Vida',
                    'Gestão de Diversidade e Inclusão',
                    'Comunicação Interna e Endomarketing',
                    'Gestão de Banco de Talentos'
                ],
                'kpis_essenciais' => ['Turnover (%)', 'Tempo de contratação', 'Satisfação colaboradores']
            ],
            'ADMINISTRATIVO' => [
                'tipo' => 'base',
                'descricao' => 'Compras, contratos, documentação, jurídico básico',
                'funcoes_principais' => ['Gestão de contratos', 'Compras e suprimentos', 'Documentação legal', 'Suporte administrativo'],
                'sops_padrao' => [
                    'Gestão de Contratos e Documentação Legal',
                    'Processo de Compras e Cotações',
                    'Gestão de Suprimentos e Fornecedores',
                    'Controle de Protocolo e Arquivos',
                    'Suporte Jurídico e Compliance Básico',
                    'Gestão de Facilities e Infraestrutura',
                    'Controle de Patrimônio e Ativos',
                    'Gestão de Seguros Empresariais',
                    'Administração de Viagens Corporativas',
                    'Gestão de Correspondências e Malote',
                    'Controle de Acesso e Segurança Predial',
                    'Gestão de Licenças e Alvarás',
                    'Administração de Frotas de Veículos',
                    'Gestão de Telefonia e Comunicações',
                    'Controle de Material de Escritório',
                    'Gestão de Eventos Corporativos',
                    'Administração de Contratos de Locação',
                    'Gestão de Utilities (Água, Luz, Internet)',
                    'Controle de Gastos Administrativos',
                    'Gestão de Arquivo Morto e Digitalização'
                ],
                'kpis_essenciais' => ['Prazo de contratos', 'Economia em compras', 'Compliance documental']
            ],
            'TI / INFRAESTRUTURA' => [
                'tipo' => 'base',
                'descricao' => 'Sistemas, ferramentas, dados',
                'funcoes_principais' => ['Gestão de sistemas', 'Infraestrutura tecnológica', 'Segurança de dados', 'Suporte técnico'],
                'sops_padrao' => [
                    'Gestão de Infraestrutura de Servidores',
                    'Backup e Recuperação de Dados',
                    'Suporte Técnico Interno (Help Desk)',
                    'Gestão de Usuários e Controle de Acessos',
                    'Manutenção Preventiva de Sistemas',
                    'Segurança da Informação e Compliance',
                    'Gestão de Redes e Conectividade',
                    'Monitoramento de Performance de Sistemas',
                    'Gestão de Banco de Dados',
                    'Controle de Patches e Atualizações',
                    'Gestão de Licenças de Software',
                    'Implementação de Novos Sistemas',
                    'Gestão de Cloud Computing',
                    'Disaster Recovery e Continuidade de Negócio',
                    'Gestão de Dispositivos Móveis (MDM)',
                    'Controle de Antivírus e Anti-malware',
                    'Gestão de Firewall e Segurança de Rede',
                    'Auditoria e Logs de Sistemas',
                    'Gestão de Telefonia IP e VoIP',
                    'Gestão de Videoconferência e Colaboração'
                ],
                'kpis_essenciais' => ['Uptime dos sistemas', 'Tempo resolução TI', 'Incidentes de segurança']
            ],
            'QUALIDADE / MELHORIA CONTÍNUA' => [
                'tipo' => 'base',
                'descricao' => 'SOPs, indicadores, auditoria interna',
                'funcoes_principais' => ['Gestão de processos', 'Auditoria interna', 'Indicadores de qualidade', 'Melhoria contínua'],
                'sops_padrao' => [
                    'Auditoria Interna de Processos',
                    'Gestão de Indicadores e Dashboards de KPIs',
                    'Tratamento de Não Conformidades',
                    'Processo de Melhoria Contínua (Kaizen)',
                    'Padronização e Documentação de Procedimentos',
                    'Gestão de Certificações e Normas (ISO)',
                    'Controle Estatístico de Processos',
                    'Gestão de Reclamações e Feedback Interno',
                    'Benchmarking e Análise de Concorrência',
                    'Gestão de Projetos de Melhoria',
                    'Treinamento em Ferramentas de Qualidade',
                    'Gestão de Satisfação de Clientes Internos',
                    'Análise de Causa Raiz e 5 Porquês',
                    'Gestão de Riscos Operacionais',
                    'Implementação de Metodologias Lean',
                    'Controle de Documentos e Procedimentos',
                    'Gestão de Indicadores de Produtividade',
                    'Auditorias de Fornecedores e Terceiros',
                    'Gestão de Planos de Ação Corretivos',
                    'Controle de Calibração e Metrologia'
                ],
                'kpis_essenciais' => ['Não conformidades', 'Processos auditados', 'Melhorias implementadas']
            ]
        ];
        
        // 2. SETORES ESPECÍFICOS POR NICHO
        $setoresEspecificos = $this->getSetoresEspecificosPorNicho($nicho);
        
        // 3. COMBINAR ESTRUTURA FINAL
        $todosSetores = array_merge($setoresBase, $setoresEspecificos);
        
        Logger::info('Estrutura empresarial COMPLETA criada', [
            'nicho' => $nicho,
            'setores_base' => count($setoresBase),
            'setores_especificos' => count($setoresEspecificos),
            'total_setores' => count($todosSetores),
            'nomes_setores' => array_keys($todosSetores)
        ]);
        
        // CORRIGIR: Garantir que sempre retorna 'setores' como chave principal
        return [
            'setores_base' => $setoresBase,
            'setores_especificos' => $setoresEspecificos,
            'setores' => $todosSetores, // CHAVE PRINCIPAL - sempre usar esta
            'nicho' => $nicho,
            'macro_categoria' => $this->obterMacroCategoriaPorNicho($nicho)
        ];
    }
    
    /**
     * Identifica processos principais por departamento baseado em empresas reais
     */
    private function identificarProcessosPrincipaisDetalhados(string $dept, array $respostas): array
    {
        // PROCESSOS ESPECÍFICOS POR DEPARTAMENTO - baseados em empresas reais
        $processosPorDepartamento = [
            'Comercial' => [
                'Prospecção e Qualificação de Leads',
                'Apresentação de Propostas Comerciais', 
                'Negociação e Fechamento de Vendas',
                'Onboarding de Novos Clientes',
                'Gestão de Relacionamento Pós-Venda',
                'Controle de Pipeline Comercial',
                'Análise de Concorrência'
            ],
            'TI' => [
                'Atendimento de Chamados Técnicos',
                'Gestão de Backup e Segurança',
                'Manutenção de Sistemas e Infraestrutura',
                'Desenvolvimento e Deploy de Soluções',
                'Monitoramento de Performance',
                'Gestão de Usuários e Acessos',
                'Implementação de Novas Tecnologias'
            ],
            'Operações' => [
                'Recebimento e Processamento de Pedidos',
                'Planejamento e Execução da Produção',
                'Controle de Qualidade e Conformidade',
                'Gestão de Estoque e Suprimentos',
                'Expedição e Logística de Entrega',
                'Melhoria Contínua de Processos',
                'Gestão de Fornecedores'
            ],
            'Financeiro' => [
                'Controle de Fluxo de Caixa Diário',
                'Gestão de Contas a Pagar',
                'Gestão de Contas a Receber',
                'Conciliação Bancária',
                'Elaboração de Relatórios Gerenciais',
                'Controle Orçamentário',
                'Análise de Inadimplência'
            ],
            'RH' => [
                'Processo de Recrutamento e Seleção',
                'Onboarding de Novos Colaboradores',
                'Gestão de Performance e Avaliações',
                'Treinamento e Desenvolvimento',
                'Controle de Ponto e Folha de Pagamento',
                'Gestão de Benefícios',
                'Clima Organizacional e Engajamento'
            ],
            'Marketing' => [
                'Planejamento de Campanhas',
                'Gestão de Mídias Sociais',
                'Criação de Conteúdo',
                'Análise de Métricas e ROI',
                'Gestão de Eventos e Webinars',
                'Relacionamento com Influenciadores',
                'Automação de Marketing'
            ],
            'Jurídico' => [
                'Análise e Elaboração de Contratos',
                'Gestão de LGPD e Compliance',
                'Acompanhamento de Processos Judiciais',
                'Consultoria Jurídica Interna',
                'Gestão de Propriedade Intelectual',
                'Due Diligence em Parcerias',
                'Política de Governança Corporativa'
            ],
            'Administrativo' => [
                'Gestão de Documentos e Arquivos',
                'Controle de Protocolo e Correspondência',
                'Gestão de Contratos de Serviços',
                'Organização de Reuniões e Agenda',
                'Controle de Patrimônio',
                'Gestão de Facilities',
                'Suporte Administrativo Geral'
            ],
            'Compras' => [
                'Cotação e Seleção de Fornecedores',
                'Negociação de Contratos de Compras',
                'Gestão de Pedidos de Compra',
                'Controle de Qualidade de Fornecedores',
                'Gestão de Relacionamento com Parceiros',
                'Análise de Custos e Economia',
                'Compliance em Procurement'
            ],
            'Logística' => [
                'Planejamento de Rotas de Entrega',
                'Gestão de Transportadoras',
                'Controle de Estoque em Trânsito',
                'Processo de Embalagem e Expedição',
                'Gestão de Devoluções',
                'Rastreamento de Entregas',
                'Otimização de Custos Logísticos'
            ],
            'Qualidade' => [
                'Implementação de Sistema de Qualidade',
                'Auditoria Interna de Processos',
                'Tratamento de Não Conformidades',
                'Calibração de Equipamentos',
                'Treinamento em Qualidade',
                'Análise de Indicadores de Qualidade',
                'Certificações e Normas'
            ],
            'Atendimento' => [
                'Atendimento ao Cliente Multicanal',
                'Gestão de Reclamações e SAC',
                'Processo de Suporte Técnico',
                'Follow-up de Satisfação',
                'Gestão de Base de Conhecimento',
                'Escalação de Casos Complexos',
                'Métricas de Satisfação'
            ],
            'Vendas' => [
                'Prospecção Ativa de Clientes',
                'Demonstrações e Apresentações',
                'Gestão de Propostas Comerciais',
                'Follow-up de Oportunidades',
                'Gestão de Territory e Contas',
                'Relacionamento com Key Accounts',
                'Análise de Performance de Vendas'
            ]
        ];
        
        // ADAPTAR processos baseado nas RESPOSTAS DO DIAGNÓSTICO
        $departamento = $dept;
        $processosBase = $processosPorDepartamento[$departamento] ?? [
            'Processo Operacional Principal',
            'Controle e Monitoramento',
            'Melhoria Contínua',
            'Relacionamento Interno',
            'Gestão de Recursos'
        ];
        
        // Analisar respostas para personalizar processos
        if (!empty($respostas)) {
            $processosPersonalizados = [];
            
            // Verificar ferramentas específicas mencionadas
            if (isset($respostas['ferramentas']) && is_string($respostas['ferramentas'])) {
                $ferramentas = strtolower($respostas['ferramentas']);
                
                // Adaptar processos baseado nas ferramentas
                if (strpos($ferramentas, 'crm') !== false || strpos($ferramentas, 'salesforce') !== false) {
                    $processosPersonalizados[] = 'Gestão de CRM e Pipeline';
                }
                if (strpos($ferramentas, 'erp') !== false || strpos($ferramentas, 'sap') !== false) {
                    $processosPersonalizados[] = 'Operação de Sistema ERP';
                }
                if (strpos($ferramentas, 'whatsapp') !== false) {
                    $processosPersonalizados[] = 'Atendimento via WhatsApp Business';
                }
                if (strpos($ferramentas, 'excel') !== false) {
                    $processosPersonalizados[] = 'Controles Planilhados';
                }
            }
            
            // Verificar problemas específicos para criar processos corretivos
            if (isset($respostas['pontos_melhoria']) && is_string($respostas['pontos_melhoria'])) {
                $problemas = strtolower($respostas['pontos_melhoria']);
                
                if (strpos($problemas, 'comunicac') !== false) {
                    $processosPersonalizados[] = 'Melhoria da Comunicação Interna';
                }
                if (strpos($problemas, 'document') !== false || strpos($problemas, 'padroniz') !== false) {
                    $processosPersonalizados[] = 'Padronização e Documentação';
                }
                if (strpos($problemas, 'atraso') !== false || strpos($problemas, 'prazo') !== false) {
                    $processosPersonalizados[] = 'Gestão de Prazos e Entregas';
                }
            }
            
            // Combinar processos base com personalizados
            if (!empty($processosPersonalizados)) {
                // Manter primeiros 3 processos base + adicionar personalizados
                $processosFinais = array_slice($processosBase, 0, 3);
                $processosFinais = array_merge($processosFinais, $processosPersonalizados);
                return array_slice($processosFinais, 0, 7); // Máximo 7 processos
            }
        }
        
        // Retornar processos padrão para o departamento (máximo 5 para não sobrecarregar)
        return array_slice($processosBase, 0, 5);
    }
    
    private function calcularMaturidadePorDepartamento(array $respostas, string $dept): int
    {
        // Calcular maturidade específica baseada nas respostas do diagnóstico
        $score = isset($respostas['maturidade_percebida']) ? $respostas['maturidade_percebida'] : 2;
        return max(1, min(4, $score));
    }
    
    private function gerarCodigoSOPEspecifico(string $setor, string $departamento, string $processo, int $contador): string
    {
        // PREFIXOS POR SETOR mais específicos
        switch(strtolower($setor)) {
            case 'tecnologia':
            case 'tech':
            case 'software':
                $prefixoSetor = 'TEC';
                break;
            case 'saúde':
            case 'saude':
            case 'hospital':
            case 'clinica':
                $prefixoSetor = 'SAU';
                break;
            case 'educação':
            case 'educacao':
            case 'escola':
            case 'universidade':
                $prefixoSetor = 'EDU';
                break;
            case 'varejo':
            case 'loja':
            case 'comercio':
                $prefixoSetor = 'VAR';
                break;
            case 'indústria':
            case 'industria':
            case 'fabrica':
            case 'manufatura':
                $prefixoSetor = 'IND';
                break;
            case 'serviços':
            case 'servicos':
            case 'consultoria':
                $prefixoSetor = 'SER';
                break;
            case 'financeiro':
            case 'banco':
            case 'fintech':
                $prefixoSetor = 'FIN';
                break;
            case 'logística':
            case 'logistica':
            case 'transporte':
                $prefixoSetor = 'LOG';
                break;
            case 'alimentação':
            case 'alimentacao':
            case 'restaurante':
                $prefixoSetor = 'ALI';
                break;
            default:
                $prefixoSetor = 'GER';
                break;
        }
        
        // PREFIXOS POR DEPARTAMENTO mais específicos
        $prefixoDept = strtoupper(substr($departamento, 0, 3)); // default
        switch(strtolower($departamento)) {
            case 'comercial':
            case 'vendas':
                $prefixoDept = 'COM';
                break;
            case 'ti':
            case 'tecnologia':
            case 'tech':
                $prefixoDept = 'TI';
                break;
            case 'operações':
            case 'operacoes':
            case 'produção':
            case 'producao':
                $prefixoDept = 'OPS';
                break;
            case 'financeiro':
            case 'contabilidade':
                $prefixoDept = 'FIN';
                break;
            case 'rh':
            case 'recursos humanos':
            case 'pessoas':
                $prefixoDept = 'RH';
                break;
            case 'marketing':
            case 'mkt':
                $prefixoDept = 'MKT';
                break;
            case 'jurídico':
            case 'juridico':
            case 'legal':
                $prefixoDept = 'JUR';
                break;
            case 'administrativo':
            case 'admin':
                $prefixoDept = 'ADM';
                break;
            case 'compras':
            case 'procurement':
                $prefixoDept = 'CPR';
                break;
            case 'logística':
            case 'logistica':
                $prefixoDept = 'LOG';
                break;
            case 'qualidade':
            case 'qa':
            case 'qc':
                $prefixoDept = 'QUA';
                break;
            case 'atendimento':
            case 'sac':
            case 'suporte':
                $prefixoDept = 'ATE';
                break;
        }
        
        // CÓDIGO baseado no TIPO DE PROCESSO (mais inteligente)
        $sufixoProcesso = 'GER'; // Padrão geral
        
        $processoLower = strtolower($processo);
        if (strpos($processoLower, 'prospec') !== false || strpos($processoLower, 'lead') !== false) {
            $sufixoProcesso = 'PRO'; // Prospecção
        } elseif (strpos($processoLower, 'atend') !== false || strpos($processoLower, 'chamado') !== false) {
            $sufixoProcesso = 'ATE'; // Atendimento
        } elseif (strpos($processoLower, 'backup') !== false || strpos($processoLower, 'segur') !== false) {
            $sufixoProcesso = 'SEC'; // Segurança
        } elseif (strpos($processoLower, 'financ') !== false || strpos($processoLower, 'fluxo') !== false) {
            $sufixoProcesso = 'FLX'; // Fluxo
        } elseif (strpos($processoLower, 'recruit') !== false || strpos($processoLower, 'seleç') !== false) {
            $sufixoProcesso = 'REC'; // Recrutamento
        } elseif (strpos($processoLower, 'onboard') !== false) {
            $sufixoProcesso = 'ONB'; // Onboarding
        } elseif (strpos($processoLower, 'qualidad') !== false || strpos($processoLower, 'control') !== false) {
            $sufixoProcesso = 'QUA'; // Qualidade
        } elseif (strpos($processoLower, 'entrega') !== false || strpos($processoLower, 'expedi') !== false) {
            $sufixoProcesso = 'ENT'; // Entrega
        } elseif (strpos($processoLower, 'estoque') !== false) {
            $sufixoProcesso = 'EST'; // Estoque
        } elseif (strpos($processoLower, 'treina') !== false || strpos($processoLower, 'capaci') !== false) {
            $sufixoProcesso = 'TRE'; // Treinamento
        }
        
        return sprintf('SOP-%s-%s-%s-%03d', $prefixoSetor, $prefixoDept, $sufixoProcesso, $contador);
    }
    
    private function getIconePorDepartamento(string $dept): string
    {
        switch(strtolower($dept)) {
            case 'comercial':
                return '💼';
            case 'ti':
                return '💻';
            case 'operações':
            case 'operacoes':
                return '⚙️';
            case 'financeiro':
                return '💰';
            case 'rh':
                return '👥';
            case 'marketing':
                return '📢';
            case 'juridico':
            case 'jurídico':
                return '⚖️';
            default:
                return '📋';
        }
    }
    
    /**
     * Extrai dados completos da empresa do diagnóstico para a Chamada 1
     */
    private function extrairDadosEmpresaCompletos(array $empresa, array $diagnostico, array $respostas): array
    {
        $nicho = $this->identificarNichoEmpresa($respostas, $diagnostico);
        
        return [
            'nome' => $empresa['nome'],
            'nicho' => $nicho,
            'macro_categoria' => $this->obterMacroCategoriaPorNicho($nicho),
            'subnicho' => $respostas['subnicho'] ?? '',
            'porte' => $this->determinarPorteEmpresa($respostas),
            'modelo_negocio' => $respostas['modelo_negocio'] ?? $respostas['atividade_principal'] ?? 'Não especificado',
            'produtos_servicos' => $respostas['produtos_servicos'] ?? $respostas['principal_produto'] ?? 'Não especificado',
            'publico_alvo' => $respostas['publico_alvo'] ?? $respostas['clientes_principais'] ?? 'Não especificado',
            'num_funcionarios' => $respostas['colaboradores'] ?? $respostas['numero_colaboradores'] ?? 'Não informado',
            'faturamento' => $respostas['faturamento'] ?? $respostas['receita_mensal'] ?? 'Não informado',
            'localizacao' => $respostas['localizacao'] ?? $respostas['cidade'] ?? 'Brasil',
            'canais_venda' => $respostas['canais_venda'] ?? $respostas['como_vende'] ?? 'Não especificado',
            'dores_desafios' => $respostas['pontos_melhoria'] ?? $respostas['principais_problemas'] ?? 'Não relatado',
            'ferramentas_atuais' => $respostas['ferramentas'] ?? $respostas['sistemas_utilizados'] ?? 'Não especificado',
            'estagio' => $this->determinarEstagioEmpresa($respostas)
        ];
    }

    /**
     * Obter macro categoria baseada no nicho da empresa
     */
    private function obterMacroCategoriaPorNicho(string $nicho): string
    {
        $macrocategorias = [
            'tecnologia' => 'Tecnologia e Inovação',
            'saúde' => 'Saúde e Bem-estar',
            'construção' => 'Construção e Infraestrutura',
            'educação' => 'Educação e Treinamento',
            'ecommerce' => 'Varejo e E-commerce',
            'indústria' => 'Indústria e Manufatura',
            'logística' => 'Logística e Transporte',
            'alimentação' => 'Alimentação e Gastronomia',
            'imobiliário' => 'Mercado Imobiliário',
            'advocacia' => 'Serviços Jurídicos',
            'beleza' => 'Beleza e Estética',
            'fitness' => 'Fitness e Bem-estar',
            'turismo' => 'Turismo e Hospitalidade',
            'consultoria' => 'Consultoria e Serviços Profissionais',
            'financeiro' => 'Serviços Financeiros',
            'marketing' => 'Marketing e Comunicação',
            'automotivo' => 'Setor Automotivo',
            'agronegócio' => 'Agronegócio e Rural',
            'ong' => 'Terceiro Setor e ONGs'
        ];
        
        return $macrocategorias[$nicho] ?? 'Serviços Gerais';
    }

    /**
     * Determina o porte da empresa baseado nos dados
     */
    private function determinarPorteEmpresa(array $respostas): string
    {
        $colaboradores = $respostas['colaboradores'] ?? $respostas['numero_colaboradores'] ?? '';
        
        if (is_numeric($colaboradores)) {
            $num = (int) $colaboradores;
            if ($num <= 5) return 'micro';
            if ($num <= 20) return 'pequena';
            if ($num <= 100) return 'media';
            return 'grande';
        }
        
        // Fallback baseado em faturamento ou outras pistas
        $faturamento = strtolower($respostas['faturamento'] ?? '');
        if (strpos($faturamento, 'milhão') !== false || strpos($faturamento, 'milhões') !== false) {
            return 'grande';
        }
        
        return 'pequena'; // Padrão
    }

    /**
     * Determina o estágio da empresa
     */
    private function determinarEstagioEmpresa(array $respostas): string
    {
        $maturidade = $respostas['maturidade_percebida'] ?? 2;
        
        switch($maturidade) {
            case 1: return 'inicial';
            case 2: return 'em_estruturacao';
            case 3: return 'em_crescimento';
            case 4: return 'consolidada';
            default: return 'em_estruturacao';
        }
    }

    /**
     * Conta total de SOPs na estrutura
     */
    private function contarTotalSOPs(array $setores): int
    {
        $total = 0;
        foreach ($setores as $setor) {
            $total += count($setor['sops'] ?? []);
        }
        return $total;
    }

    /**
     * Salva estrutura temporariamente para processamento em etapas
     */
    /**
     * Atualizar timestamp da estrutura temporária para manter viva durante processo longo
     */
    private function manterEstruturaViva(int $estruturaId): void
    {
        try {
            Database::execute(
                "UPDATE estruturas_temporarias SET atualizado_em = NOW() WHERE id = ?",
                [$estruturaId]
            );
            
            Logger::info('Estrutura temporária mantida viva', [
                'estrutura_id' => $estruturaId,
                'timestamp_atualizado' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Logger::error('Erro ao manter estrutura viva', [
                'estrutura_id' => $estruturaId,
                'erro' => $e->getMessage()
            ]);
        }
    }

    private function salvarEstruturaTemporaria(int $diagnosticoId, array $estrutura): int
    {
        $sucesso = Database::execute(
            "INSERT INTO estruturas_temporarias (diagnostico_id, estrutura_json, criado_em, atualizado_em) VALUES (?, ?, NOW(), NOW())",
            [$diagnosticoId, json_encode($estrutura, JSON_UNESCAPED_UNICODE)]
        );
        
        if (!$sucesso) {
            throw new Exception('Erro ao salvar estrutura temporária');
        }
        
        $estruturaId = (int) Database::lastInsertId();
        
        Logger::info('Estrutura temporária salva', [
            'estrutura_id' => $estruturaId,
            'diagnostico_id' => $diagnosticoId,
            'estrutura_size' => strlen(json_encode($estrutura, JSON_UNESCAPED_UNICODE))
        ]);
        
        return $estruturaId;
    }
    
    /**
     * Salvar estrutura organizacional permanente
     */
    private function salvarEstruturaOrganizacional(int $diagnosticoId, array $empresa, array $estruturaCompleta): int
    {
        try {
            Logger::info('SALVANDO ESTRUTURA ORGANIZACIONAL PERMANENTE', [
                'diagnostico_id' => $diagnosticoId,
                'empresa_id' => $empresa['id'],
                'total_setores' => count($estruturaCompleta['todos_setores'] ?? [])
            ]);

            // Salvar estrutura hierárquica principal
            $estruturaId = Database::execute(
                "INSERT INTO estruturas_hierarquicas (
                    diagnostico_id, empresa_id, nicho, setores_base, setores_especificos, 
                    total_setores, criado_em, sistema
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'hierarquico')",
                [
                    $diagnosticoId,
                    $empresa['id'],
                    $estruturaCompleta['nicho_identificado'] ?? 'geral',
                    json_encode($estruturaCompleta['setores_base'] ?? []),
                    json_encode($estruturaCompleta['setores_especificos'] ?? []),
                    count($estruturaCompleta['todos_setores'] ?? [])
                ]
            );

            if (!$estruturaId) {
                Logger::error('FALHA AO SALVAR ESTRUTURA HIERÁRQUICA');
                return 0;
            }

            // Salvar setores organizacionais
            foreach ($estruturaCompleta['todos_setores'] as $nomeSetor => $configSetor) {
                $setorId = Database::execute(
                    "INSERT INTO setores_organizacionais (
                        estrutura_id, nome_setor, tipo_setor, descricao, 
                        funcoes_principais, sops_padrao, kpis_essenciais, criado_em
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $estruturaId,
                        $nomeSetor,
                        $configSetor['tipo'],
                        $configSetor['descricao'],
                        json_encode($configSetor['funcoes_principais'] ?? []),
                        json_encode($configSetor['sops_padrao'] ?? []),
                        json_encode($configSetor['kpis_essenciais'] ?? [])
                    ]
                );

                // Mapear serviços básicos para cada setor
                foreach ($configSetor['sops_padrao'] as $index => $nomeServico) {
                    Database::execute(
                        "INSERT INTO servicos_mapeados (
                            estrutura_id, setor_id, empresa_id, nome_servico, codigo_servico,
                            categoria, criticidade, frequencia, complexidade, origem,
                            status, criado_em
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'automatico', 'mapeado', NOW())",
                        [
                            $estruturaId,
                            $setorId,
                            $empresa['id'],
                            $nomeServico,
                            $this->gerarCodigoServico($nomeSetor, $nomeServico, $index + 1),
                            $this->determinarCategoriaServico($nomeServico),
                            $this->determinarCriticidadeServico($nomeServico),
                            $this->determinarComplexidadeServico($nomeServico)
                        ]
                    );
                }

                Logger::info('SETOR SALVO COM SERVIÇOS', [
                    'setor' => $nomeSetor,
                    'setor_id' => $setorId,
                    'total_servicos' => count($configSetor['sops_padrao'] ?? [])
                ]);
            }

            Logger::info('ESTRUTURA ORGANIZACIONAL SALVA COM SUCESSO', [
                'estrutura_id' => $estruturaId,
                'total_setores' => count($estruturaCompleta['todos_setores'])
            ]);

            return $estruturaId;

        } catch (Exception $e) {
            Logger::error('ERRO AO SALVAR ESTRUTURA ORGANIZACIONAL', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    /**
     * Métodos auxiliares para determinação de propriedades dos serviços
     */
    private function gerarCodigoServico(string $setor, string $servico, int $index): string
    {
        $prefixoSetor = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $setor), 0, 3));
        $prefixoServico = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $servico), 0, 3));
        return sprintf('SRV-%s-%s-%03d', $prefixoSetor, $prefixoServico, $index);
    }

    private function determinarCategoriaServico(string $nomeServico): string
    {
        $nomeServiceLower = strtolower($nomeServico);
        
        if (strpos($nomeServiceLower, 'estrategi') !== false || strpos($nomeServiceLower, 'planejam') !== false) {
            return 'estrategico';
        } elseif (strpos($nomeServiceLower, 'core') !== false || strpos($nomeServiceLower, 'principal') !== false) {
            return 'core';
        } elseif (strpos($nomeServiceLower, 'crise') !== false || strpos($nomeServiceLower, 'emergenc') !== false) {
            return 'crise';
        } else {
            return 'operacional';
        }
    }

    private function determinarCriticidadeServico(string $nomeServico): string
    {
        $nomeServiceLower = strtolower($nomeServico);
        
        if (strpos($nomeServiceLower, 'critico') !== false || strpos($nomeServiceLower, 'emergenc') !== false) {
            return 'alta';
        } elseif (strpos($nomeServiceLower, 'importante') !== false || strpos($nomeServiceLower, 'essencial') !== false) {
            return 'media';
        } else {
            return 'baixa';
        }
    }

    private function determinarFrequenciaServico(string $nomeServico): string
    {
        $nomeServiceLower = strtolower($nomeServico);
        
        if (strpos($nomeServiceLower, 'diaria') !== false || strpos($nomeServiceLower, 'diario') !== false) {
            return 'diaria';
        } elseif (strpos($nomeServiceLower, 'semanal') !== false) {
            return 'semanal';
        } elseif (strpos($nomeServiceLower, 'mensal') !== false) {
            return 'mensal';
        } elseif (strpos($nomeServiceLower, 'anual') !== false) {
            return 'anual';
        } else {
            return 'sob_demanda';
        }
    }

    private function determinarComplexidadeServico(string $nomeServico): string
    {
        $nomeServiceLower = strtolower($nomeServico);
        
        if (strpos($nomeServiceLower, 'simples') !== false || strpos($nomeServiceLower, 'basico') !== false) {
            return 'baixa';
        } elseif (strpos($nomeServiceLower, 'complexo') !== false || strpos($nomeServiceLower, 'avancad') !== false) {
            return 'alta';
        } else {
            return 'media';
        }
    }
    
    /**
     * Função de recuperação: buscar dados mesmo se estrutura temporária expirou
     */
    private function recuperarEstruturaSeNecessario(int $estruturaId): ?array
    {
        // Primeiro tentar busca normal
        $estrutura = $this->buscarEstruturaTemporaria($estruturaId);
        
        if ($estrutura) {
            return $estrutura;
        }
        
        // Se não encontrou, tentar recuperação por diagnóstico
        Logger::warning('Tentando recuperação de estrutura perdida', ['estrutura_id' => $estruturaId]);
        
        // Buscar se existe algum serviço mapeado para esta estrutura
        $servicoExistente = Database::queryOne(
            "SELECT * FROM servicos_mapeados WHERE estrutura_id = ? LIMIT 1",
            [$estruturaId]
        );
        
        if ($servicoExistente) {
            // Buscar a estrutura original mesmo que expirada
            $estruturaOriginal = Database::queryOne(
                "SELECT * FROM estruturas_temporarias WHERE id = ?",
                [$estruturaId]
            );
            
            if ($estruturaOriginal) {
                Logger::info('Estrutura recuperada com sucesso', [
                    'estrutura_id' => $estruturaId,
                    'diagnostico_id' => $estruturaOriginal['diagnostico_id']
                ]);
                
                return [
                    'diagnostico_id' => $estruturaOriginal['diagnostico_id'],
                    'estrutura' => json_decode($estruturaOriginal['estrutura_json'], true)
                ];
            }
        }
        
        Logger::error('Não foi possível recuperar a estrutura perdida', ['estrutura_id' => $estruturaId]);
        return null;
    }
    
    /**
     * Validar integridade dos dados antes do processamento
     */
    private function validarIntegridadeDados(int $estruturaId, array $dadosEmpresa, string $setorNome): void
    {
        $erros = [];
        
        // Validar dados essenciais
        if (empty($dadosEmpresa['nome'])) {
            $erros[] = 'Nome da empresa não encontrado';
        }
        
        if (empty($dadosEmpresa['nicho'])) {
            $erros[] = 'Nicho da empresa não definido';
        }
        
        if (empty($setorNome)) {
            $erros[] = 'Nome do setor não informado';
        }
        
        // Validar se não há processamento duplicado
        $jaProcessado = Database::queryOne(
            "SELECT id FROM servicos_mapeados WHERE estrutura_id = ? AND setor_nome = ?",
            [$estruturaId, $setorNome]
        );
        
        if ($jaProcessado) {
            Logger::info('Setor já foi processado anteriormente', [
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'servico_mapeado_id' => $jaProcessado['id']
            ]);
        }
        
        // Se há erros críticos, interromper
        if (!empty($erros)) {
            Logger::error('Validação de integridade falhou', [
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'erros' => $erros,
                'dados_empresa' => $dadosEmpresa
            ]);
            
            throw new Exception('Validação falhou: ' . implode(', ', $erros));
        }
        
        Logger::info('Validação de integridade passou', [
            'estrutura_id' => $estruturaId,
            'setor_nome' => $setorNome,
            'dados_validos' => true
        ]);
    }

    /**
     * Busca estrutura temporária salva
     */
    private function buscarEstruturaTemporaria(int $estruturaId): ?array
    {
        // CORREÇÃO: Remover limite de tempo de 2 horas para evitar perda de dados durante processo longo
        $resultado = Database::queryOne(
            "SELECT * FROM estruturas_temporarias WHERE id = ?",
            [$estruturaId]
        );
        
        if (!$resultado) {
            Logger::warning('Estrutura temporária não encontrada', [
                'estrutura_id' => $estruturaId,
                'consulta_executada' => true
            ]);
            return null;
        }
        
        Logger::info('Estrutura temporária encontrada', [
            'estrutura_id' => $estruturaId,
            'diagnostico_id' => $resultado['diagnostico_id'],
            'criado_em' => $resultado['criado_em'],
            'tempo_desde_criacao' => $this->calcularTempoDecorrido($resultado['criado_em'])
        ]);
        
        return [
            'diagnostico_id' => $resultado['diagnostico_id'],
            'estrutura' => json_decode($resultado['estrutura_json'], true)
        ];
    }
    
    /**
     * Calcular tempo decorrido desde a criação (para debug)
     */
    private function calcularTempoDecorrido(string $criadoEm): string
    {
        $agora = new DateTime();
        $criacao = new DateTime($criadoEm);
        $intervalo = $agora->diff($criacao);
        
        return sprintf('%d dias, %d horas, %d minutos', 
            $intervalo->days, 
            $intervalo->h, 
            $intervalo->i
        );
    }

    /**
     * Extrai contextos da empresa para geração de SOPs
     */
    private function extrairContextosEmpresa(array $empresa, array $diagnostico, array $respostas, array $diagnosticoEstrutura): array
    {
        return [
            'nome' => $empresa['nome'],
            'nicho' => $diagnosticoEstrutura['macro_categoria'],
            'macro_categoria' => $diagnosticoEstrutura['macro_categoria'],
            'porte' => $this->determinarPorteEmpresa($respostas),
            'estagio' => $this->determinarEstagioEmpresa($respostas),
            'modelo_negocio' => $respostas['modelo_negocio'] ?? $respostas['atividade_principal'] ?? 'Não especificado',
            'produtos_servicos' => $respostas['produtos_servicos'] ?? $respostas['principal_produto'] ?? 'Não especificado',
            'publico_alvo' => $respostas['publico_alvo'] ?? $respostas['clientes_principais'] ?? 'Não especificado',
            'ferramentas_atuais' => $respostas['ferramentas'] ?? $respostas['sistemas_utilizados'] ?? 'Não especificado',
            'nivel_maturidade' => $diagnosticoEstrutura['diagnostico']['nivel_maturidade']
        ];
    }

    /**
     * Encontra um SOP específico por índice na estrutura
     */
    private function encontrarSOPPorIndex(array $setores, int $sopIndex): ?array
    {
        $indiceAtual = 0;
        
        foreach ($setores as $setor) {
            foreach ($setor['sops'] as $sop) {
                if ($indiceAtual === $sopIndex) {
                    return array_merge($sop, [
                        'nome_setor' => $setor['nome_setor'],
                        'responsavel_sugerido' => $setor['responsavel_sugerido']
                    ]);
                }
                $indiceAtual++;
            }
        }
        
        return null;
    }

    /**
     * Salva SOP gerado individualmente
     */
    private function salvarSOPGerado(int $empresaId, int $diagnosticoId, array $sopData, string $conteudo): int
    {
        return Sop::criar([
            'empresa_id' => $empresaId,
            'diagnostico_id' => $diagnosticoId,
            'sop_codigo' => $sopData['id_sop'],
            'titulo' => $sopData['nome_sop'],
            'departamento' => $sopData['nome_setor'],
            'conteudo' => substr($conteudo, 0, 1000), // Resumo
            'conteudo_completo' => $conteudo, // Conteúdo completo em Markdown
            'versao' => '1.0',
            'status' => 'rascunho',
            'gerado_por_ia' => 1,
            'criticidade' => $sopData['criticidade'],
            'formato' => 'markdown_n3' // Nova estrutura N1/N2/N3
        ]);
    }

    /**
     * Busca SOPs gerados para uma empresa/diagnóstico
     */
    private function buscarSOPsGerados(int $empresaId, int $diagnosticoId): array
    {
        return Database::query(
            "SELECT * FROM sops WHERE empresa_id = ? AND diagnostico_id = ? ORDER BY departamento, titulo",
            [$empresaId, $diagnosticoId]
        );
    }

    /**
     * Consolida conteúdo de todos os SOPs
     */
    private function consolidarConteudoSOPs(array $sopsGerados): string
    {
        $conteudoCompleto = '';
        $setorAtual = '';
        
        foreach ($sopsGerados as $sop) {
            if ($sop['departamento'] !== $setorAtual) {
                $setorAtual = $sop['departamento'];
                $conteudoCompleto .= "\n\n# SETOR: " . strtoupper($setorAtual) . "\n\n";
            }
            
            $conteudoCompleto .= $sop['conteudo_completo'] . "\n\n---\n\n";
        }
        
        return $conteudoCompleto;
    }

    /**
     * Salva manual completo final
     */
    private function salvarManualCompleto(int $empresaId, int $diagnosticoId, string $conteudo): int
    {
        $sucesso = Database::execute(
            "INSERT INTO manuais_completos (empresa_id, diagnostico_id, conteudo_completo, versao, criado_em) VALUES (?, ?, ?, '1.0', NOW())",
            [$empresaId, $diagnosticoId, $conteudo]
        );
        
        if (!$sucesso) {
            throw new Exception('Erro ao salvar manual completo');
        }
        
        return (int) Database::lastInsertId();
    }

    /**
     * Exibe o manual completo gerado
     */
    public function exibirManualCompleto(): void
    {
        Auth::proteger();
        
        $manualId = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
        if (!$manualId) {
            Flash::set('erro', 'Manual não especificado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        $manual = Database::queryOne(
            "SELECT * FROM manuais_completos WHERE id = ?",
            [$manualId]
        );

        if (!$manual || !Auth::podeAcessarEmpresa($manual['empresa_id'])) {
            Flash::set('erro', 'Manual não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        $empresa = Empresa::buscarPorId($manual['empresa_id']);
        $diagnostico = Diagnostico::buscarPorId($manual['diagnostico_id']);

        $dados = [
            'manual' => $manual,
            'empresa' => $empresa,
            'diagnostico' => $diagnostico
        ];

        require VIEW_PATH . '/sop/manual-completo.php';
    }

    /**
     * Cria mapeamento empresarial completo baseado no diagnóstico
     */
    private function criarMapeamentoEmpresarial(array $empresa, ?array $diagnostico): array
    {
        // Extrair dados do diagnóstico se existir
        $respostas = [];
        if ($diagnostico && !empty($diagnostico['respostas'])) {
            $respostasJson = json_decode($diagnostico['respostas'], true);
            $respostas = $respostasJson ? $respostasJson : [];
        }
        
        // 1. MAPEAMENTO DE DEPARTAMENTOS
        $departamentosBasicos = $this->extrairDepartamentos($diagnostico);
        $departamentosArray = $this->parsearDepartamentos($departamentosBasicos);
        
        // 2. MAPEAMENTO DETALHADO POR DEPARTAMENTO
        $departamentosDetalhados = [];
        foreach ($departamentosArray as $dept) {
            $departamentosDetalhados[$dept] = $this->mapearDepartamento($dept, $empresa['segmento'], $respostas);
        }
        
        // 3. PROCEDIMENTOS PADRÃO DO MERCADO
        $procedimentosMercado = $this->obterProcedimentosMercado($empresa['segmento'], $departamentosArray);
        
        // 4. MAPEAMENTO DETALHADO UNIFICADO
        $mapeamentoDetalhado = $this->criarMapeamentoDetalhado($departamentosDetalhados, $procedimentosMercado);
        
        return [
            'colaboradores' => $this->extrairColaboradores($diagnostico),
            'faturamento' => $this->extrairFaturamento($diagnostico),
            'maturidade' => $empresa['score_maturidade'] ?? 2,
            'departamentos_texto' => $departamentosBasicos,
            'departamentos_array' => $departamentosArray,
            'departamentos_detalhados' => $departamentosDetalhados,
            'ferramentas' => $this->extrairFerramentas($diagnostico),
            'problemas' => $this->extrairProblemas($diagnostico),
            'objetivos' => $this->extrairObjetivos($diagnostico),
            'procedimentos_mercado' => $procedimentosMercado,
            'mapeamento_detalhado' => $mapeamentoDetalhado
        ];
    }
    
    /**
     * Mapeia um departamento específico com suas funções e responsabilidades
     */
    private function mapearDepartamento(string $departamento, string $setor, array $respostas): array
    {
        $mapeamentos = [
            'Comercial' => [
                'funcoes_principais' => [
                    'Prospecção e qualificação de leads',
                    'Negociação e fechamento de vendas', 
                    'Gestão do relacionamento com clientes',
                    'Elaboração de propostas comerciais',
                    'Controle de pipeline de vendas'
                ],
                'responsabilidades' => [
                    'Atingir metas de vendas mensais',
                    'Manter CRM atualizado',
                    'Realizar follow-up com prospects',
                    'Participar de reuniões comerciais',
                    'Elaborar relatórios de vendas'
                ],
                'kpis_essenciais' => [
                    'Taxa de conversão de leads',
                    'Ticket médio de vendas',
                    'Tempo médio de ciclo de vendas',
                    'CAC (Custo de Aquisição de Clientes)'
                ]
            ],
            'Financeiro' => [
                'funcoes_principais' => [
                    'Controle de fluxo de caixa',
                    'Contas a pagar e receber',
                    'Análise financeira e planejamento',
                    'Controle de custos e despesas',
                    'Relatórios gerenciais'
                ],
                'responsabilidades' => [
                    'Manter fluxo de caixa atualizado diariamente',
                    'Negociar prazos com fornecedores',
                    'Controlar inadimplência',
                    'Elaborar demonstrativos financeiros',
                    'Apoiar tomada de decisões estratégicas'
                ],
                'kpis_essenciais' => [
                    'Margem de lucro bruto',
                    'Inadimplência (%)',
                    'Prazo médio de recebimento',
                    'Controle orçamentário'
                ]
            ],
            'Operações' => [
                'funcoes_principais' => [
                    'Gestão da produção/entrega',
                    'Controle de qualidade',
                    'Gestão de estoque',
                    'Otimização de processos',
                    'Atendimento ao cliente'
                ],
                'responsabilidades' => [
                    'Garantir qualidade dos produtos/serviços',
                    'Otimizar custos operacionais',
                    'Manter padrões de atendimento',
                    'Gerenciar fornecedores',
                    'Implementar melhorias contínuas'
                ],
                'kpis_essenciais' => [
                    'Tempo médio de entrega',
                    'Índice de qualidade',
                    'Produtividade por colaborador',
                    'Satisfação do cliente'
                ]
            ],
            'TI' => [
                'funcoes_principais' => [
                    'Manutenção de infraestrutura',
                    'Desenvolvimento de sistemas',
                    'Suporte técnico interno',
                    'Segurança da informação',
                    'Automação de processos'
                ],
                'responsabilidades' => [
                    'Manter sistemas funcionando',
                    'Realizar backups regulares',
                    'Implementar medidas de segurança',
                    'Treinar usuários em ferramentas',
                    'Avaliar novas tecnologias'
                ],
                'kpis_essenciais' => [
                    'Uptime dos sistemas (%)',
                    'Tempo médio de resolução',
                    'Número de incidentes',
                    'Satisfação dos usuários internos'
                ]
            ],
            'RH' => [
                'funcoes_principais' => [
                    'Recrutamento e seleção',
                    'Gestão de pessoas',
                    'Treinamento e desenvolvimento',
                    'Controle de ponto e folha',
                    'Clima organizacional'
                ],
                'responsabilidades' => [
                    'Manter equipe motivada',
                    'Controlar turnover',
                    'Garantir compliance trabalhista',
                    'Desenvolver talentos internos',
                    'Mediar conflitos'
                ],
                'kpis_essenciais' => [
                    'Taxa de turnover',
                    'Tempo médio de contratação',
                    'Satisfação dos colaboradores',
                    'Horas de treinamento per capita'
                ]
            ]
        ];
        
        // Retornar mapeamento específico ou genérico
        return isset($mapeamentos[$departamento]) ? $mapeamentos[$departamento] : [
            'funcoes_principais' => ['Executar atividades do departamento', 'Apoiar objetivos estratégicos'],
            'responsabilidades' => ['Cumprir metas estabelecidas', 'Manter processos organizados'],
            'kpis_essenciais' => ['Produtividade', 'Qualidade dos entregáveis']
        ];
    }
    
    /**
     * Obtém procedimentos padrão do mercado por setor
     */
    private function obterProcedimentosMercado(string $setor, array $departamentos): array
    {
        $procedimentosBase = [
            'Tecnologia' => [
                'Comercial' => [
                    'Metodologia SPIN Selling para descoberta',
                    'Pipeline em CRM com 5 estágios mínimos',
                    'Follow-up estruturado com cadência definida',
                    'Proposta técnica + comercial padronizada'
                ],
                'Operações' => [
                    'Metodologia ágil (Scrum/Kanban)',
                    'Controle de qualidade com code review',
                    'Deploy automatizado com CI/CD',
                    'Monitoramento de performance'
                ],
                'TI' => [
                    'Backup 3-2-1 (3 cópias, 2 mídias, 1 offsite)',
                    'Monitoramento 24/7 de infraestrutura',
                    'Patch management mensal',
                    'Política de segurança ISO 27001'
                ]
            ],
            'Varejo' => [
                'Comercial' => [
                    'Técnicas de merchandising visual',
                    'Gestão de relacionamento pós-venda',
                    'Programa de fidelidade estruturado'
                ],
                'Operações' => [
                    'Controle de estoque Just-in-Time',
                    'Gestão de fornecedores qualificados',
                    'Padrão de atendimento ao cliente'
                ]
            ]
        ];
        
        $resultado = [];
        foreach ($departamentos as $dept) {
            if (isset($procedimentosBase[$setor][$dept])) {
                $resultado[$dept] = $procedimentosBase[$setor][$dept];
            } else {
                $resultado[$dept] = ['Procedimentos padrão da indústria', 'Boas práticas do mercado'];
            }
        }
        
        return $resultado;
    }
    
    /**
     * Cria mapeamento detalhado unificado
     */
    private function criarMapeamentoDetalhado(array $departamentosDetalhados, array $procedimentosMercado): string
    {
        $mapeamento = "=== MAPEAMENTO EMPRESARIAL COMPLETO ===\n\n";
        
        foreach ($departamentosDetalhados as $dept => $detalhes) {
            $mapeamento .= "DEPARTAMENTO: {$dept}\n";
            $mapeamento .= "Funções Principais:\n";
            foreach ($detalhes['funcoes_principais'] as $funcao) {
                $mapeamento .= "• {$funcao}\n";
            }
            
            $mapeamento .= "\nResponsabilidades:\n";
            foreach ($detalhes['responsabilidades'] as $resp) {
                $mapeamento .= "• {$resp}\n";
            }
            
            $mapeamento .= "\nKPIs Essenciais:\n";
            foreach ($detalhes['kpis_essenciais'] as $kpi) {
                $mapeamento .= "• {$kpi}\n";
            }
            
            if (isset($procedimentosMercado[$dept])) {
                $mapeamento .= "\nProcedimentos Padrão do Mercado:\n";
                foreach ($procedimentosMercado[$dept] as $proc) {
                    $mapeamento .= "• {$proc}\n";
                }
            }
            
            $mapeamento .= "\n" . str_repeat("-", 50) . "\n\n";
        }
        
        return $mapeamento;
    }
    
    /**
     * Converte string de departamentos em array
     */
    private function parsearDepartamentos(string $departamentos): array
    {
        // Limpar e separar departamentos
        $deps = array_map('trim', explode(',', $departamentos));
        $deps = array_filter($deps); // Remove vazios
        
        // Padronizar nomes
        $padronizacao = [
            'comercial' => 'Comercial',
            'vendas' => 'Comercial', 
            'financeiro' => 'Financeiro',
            'ti' => 'TI',
            'tecnologia' => 'TI',
            'operacoes' => 'Operações',
            'operações' => 'Operações',
            'rh' => 'RH',
            'recursos humanos' => 'RH',
            'marketing' => 'Marketing',
            'administrativo' => 'Administrativo'
        ];
        
        $resultado = [];
        foreach ($deps as $dep) {
            $depLower = strtolower($dep);
            if (isset($padronizacao[$depLower])) {
                $resultado[] = $padronizacao[$depLower];
            } else {
                $resultado[] = ucfirst($dep);
            }
        }
        
        return array_unique($resultado);
    }
    
    /**
     * Buscar SOPs de ambas as arquiteturas (antiga + nova)
     */
    private function buscarSopsCompletos(int $empresaId): array
    {
        // 1. Buscar SOPs da arquitetura antiga
        $sopsAntigos = Sop::buscarPorEmpresa($empresaId);
        
        // 2. Buscar SOPs da nova arquitetura (através de servicos_setor + sops)
        $sopsNovos = Database::query(
            "SELECT 
                s.id,
                s.titulo,
                s.departamento,
                s.conteudo,
                s.criado_em,
                s.atualizado_em,
                'nova_arquitetura' as origem,
                s.status,
                ss.codigo_servico,
                ss.nome_servico,
                ss.nome_setor
             FROM sops s
             INNER JOIN servicos_setor ss ON s.id = ss.sop_id
             WHERE s.empresa_id = :empresa_id AND ss.tem_sop = 1
             ORDER BY s.criado_em DESC",
            ['empresa_id' => $empresaId]
        );
        
        // 3. Combinar e normalizar os dados
        $sopsCompletos = [];
        
        // Adicionar SOPs antigos
        foreach ($sopsAntigos as $sop) {
            $sopsCompletos[] = [
                'id' => $sop['id'],
                'sop_codigo' => $sop['sop_codigo'] ?? 'SOP-' . $sop['id'],
                'nome' => $sop['titulo'],
                'departamento' => $sop['departamento'],
                'status' => $sop['status'],
                'origem' => 'arquitetura_antiga',
                'criado_em' => $sop['criado_em'],
                'customizado' => false
            ];
        }
        
        // Adicionar SOPs novos
        foreach ($sopsNovos as $sop) {
            $sopsCompletos[] = [
                'id' => 'nova_' . $sop['id'], // Prefixo para distinguir
                'sop_codigo' => 'SOP-NOVA-' . $sop['id'],
                'nome' => $sop['titulo'],
                'departamento' => $sop['departamento'],
                'status' => 'aprovado', // SOPs da nova arquitetura são considerados aprovados
                'origem' => 'nova_arquitetura',
                'criado_em' => $sop['criado_em'],
                'customizado' => false,
                'estrutura_id' => $sop['estrutura_id'] ?? null
            ];
        }
        
        // Ordenar por data de criação (mais recentes primeiro)
        usort($sopsCompletos, function($a, $b) {
            return strtotime($b['criado_em']) - strtotime($a['criado_em']);
        });
        
        Logger::info('SOPs completos carregados', [
            'empresa_id' => $empresaId,
            'sops_antigos' => count($sopsAntigos),
            'sops_novos' => count($sopsNovos),
            'total_combinado' => count($sopsCompletos),
            'exemplo_sops' => array_slice($sopsCompletos, 0, 3) // Primeiros 3 para debug
        ]);
        
        return $sopsCompletos;
    }

    /**
     * Calcular estatísticas de SOPs de ambas as arquiteturas
     */
    private function calcularEstatisticasCompletas(int $empresaId): array
    {
        // 1. Estatísticas da arquitetura antiga
        $statsAntiga = Sop::estatisticas($empresaId);
        
        // 2. Estatísticas da nova arquitetura (SOPs gerados via servicos_setor)
        $statsNova = Database::queryOne(
            "SELECT 
                COUNT(*) as total,
                COUNT(*) as aprovados
             FROM sops s
             INNER JOIN servicos_setor ss ON s.id = ss.sop_id
             WHERE s.empresa_id = :empresa_id AND ss.tem_sop = 1",
            ['empresa_id' => $empresaId]
        );
        
        return [
            'total' => (int)($statsAntiga['total'] ?? 0) + (int)($statsNova['total'] ?? 0),
            'aprovados' => (int)($statsAntiga['aprovados'] ?? 0) + (int)($statsNova['aprovados'] ?? 0),
            'rascunhos' => (int)($statsAntiga['rascunhos'] ?? 0), // SOPs novos não têm rascunhos
            'gerados_ia' => (int)($statsAntiga['gerados_ia'] ?? 0) + (int)($statsNova['total'] ?? 0), // Todos os novos são IA
        ];
    }

    private function carregarDadosEmpresa(int $empresaId, ?int $diagnosticoEspecifico = null): array
    {
        $empresa = Empresa::buscarPorId($empresaId);
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        $stats = $this->calcularEstatisticasCompletas($empresaId);
        
        // Se foi passado um diagnóstico específico, usar ele; senão buscar o último
        if ($diagnosticoEspecifico) {
            $diagnostico = Diagnostico::buscarPorId($diagnosticoEspecifico);
        } else {
            $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
        }
        
        return [
            'id' => $empresa['id'],
            'nome' => $empresa['nome'],
            'segmento' => isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia',
            'maturidade' => isset($empresa['score_maturidade']) ? $empresa['score_maturidade'] : 2,
            'norma' => ApiHelper::getNormasPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia'),
            'total_sops' => $stats['total'],
            'aprovados' => $stats['aprovados'],
            'diagnostico_id' => isset($diagnostico['id']) ? $diagnostico['id'] : null,
        ];
    }

    /**
     * Interface para criar/gerenciar SOPs personalizados
     */
    public function gerenciarSOPs(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        $empresaId = (int) ($_GET['empresa_id'] ?? Auth::empresa());
        
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não especificada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar empresa
        $empresa = Database::queryOne(
            "SELECT * FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );
        
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar SOPs customizados da empresa
        $sopsCustomizados = Database::query(
            "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id ORDER BY departamento, nome",
            ['empresa_id' => $empresaId]
        );
        
        $dados = [
            'empresa' => $empresa,
            'sops_customizados' => $sopsCustomizados,
            'setores_disponiveis' => $this->getSetoresDisponiveis(),
            'icone_helper' => function($icone) { return $this->converterIconeParaEmoji($icone); }
        ];
        
        require VIEW_PATH . '/sop/gerenciar.php';
    }

    /**
     * Adicionar novo SOP personalizado
     */
    public function adicionarSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $sopCodigo = strtoupper(trim($_POST['sop_codigo'] ?? ''));
        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $departamento = htmlspecialchars(trim($_POST['departamento'] ?? ''));
        $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
        
        // Validações
        if (!$empresaId || empty($sopCodigo) || empty($nome) || empty($departamento)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos são obrigatórios.']);
            exit;
        }
        
        // Verificar se código já existe
        $existe = Database::queryOne(
            "SELECT id FROM sops_customizados WHERE empresa_id = :empresa_id AND sop_codigo = :codigo",
            ['empresa_id' => $empresaId, 'codigo' => $sopCodigo]
        );
        
        if ($existe) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Código SOP já existe para esta empresa.']);
            exit;
        }
        
        try {
            // Inserir SOP customizado
            Database::execute(
                "INSERT INTO sops_customizados (empresa_id, sop_codigo, nome, departamento, descricao, criado_por, criado_em) 
                 VALUES (:empresa_id, :codigo, :nome, :depto, :descricao, :user_id, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'codigo' => $sopCodigo,
                    'nome' => $nome,
                    'depto' => $departamento,
                    'descricao' => $descricao,
                    'user_id' => Auth::id()
                ]
            );
            
            Logger::acao('SOP personalizado criado', [
                'empresa_id' => $empresaId,
                'sop_codigo' => $sopCodigo,
                'nome' => $nome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP personalizado criado com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar SOP personalizado: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao criar SOP.']);
        }
        exit;
    }

    /**
     * Remover SOP personalizado
     */
    public function removerSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }
        
        try {
            // Verificar se SOP existe e buscar dados
            $sop = Database::queryOne(
                "SELECT * FROM sops_customizados WHERE id = :id",
                ['id' => $sopId]
            );
            
            if (!$sop) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado.']);
                exit;
            }
            
            // Remover SOP customizado
            Database::execute("DELETE FROM sops_customizados WHERE id = :id", ['id' => $sopId]);
            
            // Remover SOP gerado se existir
            Database::execute(
                "DELETE FROM sops WHERE sop_codigo = :codigo AND empresa_id = :empresa_id",
                ['codigo' => $sop['sop_codigo'], 'empresa_id' => $sop['empresa_id']]
            );
            
            Logger::acao('SOP personalizado removido', [
                'sop_id' => $sopId,
                'sop_codigo' => $sop['sop_codigo']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP removido com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao remover SOP: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna lista de setores disponíveis
     */
    private function getSetoresDisponiveis(): array
    {
        return [
            'Construção Civil' => 'Construção Civil',
            'Tecnologia' => 'Tecnologia',
            'Saúde' => 'Saúde',
            'Educação' => 'Educação',
            'Consultoria' => 'Consultoria',
            'Varejo' => 'Varejo',
            'Indústria' => 'Indústria',
            'Alimentício' => 'Alimentício',
            'Geral' => 'Outros Setores'
        ];
    }

    /**
     * Converte ícones de texto para emojis para exibição
     */
    private function converterIconeParaEmoji(string $icone): string
    {
        $mapeamento = [
            'documento' => '📋',
            'pacote' => '📦',
            'chave' => '🔑',
            'comercial' => '💼',
            'operacional' => '⚙️',
            'administrativo' => '📊',
            'financeiro' => '💰',
            'rh' => '👥',
            'juridico' => '⚖️',
            'ti' => '💻',
            'vendas' => '🛒',
            'producao' => '🏭',
            'saude' => '🏥',
            'educacao' => '🎓',
            'construcao' => '🚧',
            'alimenticio' => '🍽️',
            'consultoria' => '📈',
            'seguranca' => '🔒',
            'logistica' => '🚚'
        ];
        
        return $mapeamento[strtolower($icone)] ?? '📋';
    }

    /**
     * NOVA ARQUITETURA: Gera manual completo em 3 etapas
     * Etapa 1: Diagnóstico e estrutura organizacional
     */
    public function gerarManualCompleto(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $diagnosticoIdPost = (int) (isset($_POST['diagnostico_id']) ? $_POST['diagnostico_id'] : 0);
        
        if (!$diagnosticoIdPost) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Diagnóstico não informado.']);
            exit;
        }

        $diagnostico = Diagnostico::buscarPorId($diagnosticoIdPost);
        if (!$diagnostico) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Diagnóstico não encontrado.']);
            exit;
        }

        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
        if (!$empresa) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        try {
            // ETAPA 1: Extrair dados da empresa do diagnóstico
            $respostas = json_decode($diagnostico['respostas'], true) ?? [];
            $dadosEmpresa = $this->extrairDadosEmpresaCompletos($empresa, $diagnostico, $respostas);
            
            Logger::info('Iniciando geração de estrutura hierárquica - SISTEMA UNIFICADO', [
                'empresa_id' => $empresa['id'],
                'diagnostico_id' => $diagnosticoIdPost,
                'empresa_nome' => $empresa['nome'],
                'nicho_detectado' => $dadosEmpresa['nicho']
            ]);

            // CHAMADA 1: Diagnóstico e Estrutura Organizacional usando a nova estrutura profissional
            $estruturaCompleta = $this->criarEstruturaCompletaPorNicho($dadosEmpresa['nicho'], $respostas);
            
            // SALVAR NA ESTRUTURA HIERÁRQUICA PERMANENTE
            $estruturaId = $this->salvarEstruturaOrganizacional($diagnosticoIdPost, $empresa, $estruturaCompleta);
            
            if (!$estruturaId) {
                throw new Exception('Erro ao salvar estrutura organizacional');
            }
            
            // DEBUG: Verificar se setores foram realmente criados
            $setoresSalvos = Database::query(
                "SELECT id, nome_setor, total_servicos FROM setores_empresa WHERE estrutura_id = ?",
                [$estruturaId]
            );
            
            Logger::info('Estrutura hierárquica criada com sucesso - DEBUG', [
                'estrutura_id' => $estruturaId,
                'total_setores_esperados' => count($estruturaCompleta['setores'] ?? []),
                'total_setores_salvos' => count($setoresSalvos),
                'setores_salvos' => array_column($setoresSalvos, 'nome_setor'),
                'sistema' => 'hierarquico_permanente'
            ]);
            
            // RESPOSTA DE SUCESSO - Redirecionar para gerenciamento hierárquico
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'estrutura_id' => $estruturaId,
                'total_setores' => count($estruturaCompleta['setores'] ?? []),
                'total_sops' => $this->contarTotalSOPs($estruturaCompleta['setores'] ?? []),
                'sistema' => 'hierarquico',
                'redirect' => APP_URL . '/sop/gerenciar-hierarquia?estrutura_id=' . $estruturaId . '&diagnostico_id=' . $diagnosticoIdPost
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro na geração da estrutura hierárquica', [
                'diagnostico_id' => $diagnosticoIdPost,
                'erro' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Erro interno: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    // ===== NOVA ARQUITETURA DETALHADA - ETAPA 2A =====

    /**
     * ETAPA 2A: Mapear todos os serviços possíveis por setor
     * Interface para mostrar progresso e executar mapeamento
     */
    public function mapearServicos(): void
    {
        Auth::proteger();
        
        $estruturaId = (int) (isset($_GET['estrutura_id']) ? $_GET['estrutura_id'] : 0);
        if (!$estruturaId) {
            Flash::set('erro', 'Estrutura não encontrada.');
            header('Location: ' . APP_URL . '/sop');
            exit;
        }

        try {
            // Buscar estrutura salva com função de recuperação
            $estruturaData = $this->recuperarEstruturaSeNecessario($estruturaId);
            if (!$estruturaData) {
                Flash::set('erro', 'Dados da estrutura não encontrados.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }

            // Verificar se as tabelas da nova arquitetura existem
            $this->verificarTabelasNovaArquitetura();

            $diagnosticoEstrutura = $estruturaData['estrutura'];
            $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
            
            if (!$diagnostico) {
                Flash::set('erro', 'Diagnóstico não encontrado.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }
            
            $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
            if (!$empresa) {
                Flash::set('erro', 'Empresa não encontrada.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }

            // Inicializar progresso se não existir (com tratamento de erro)
            try {
                $this->inicializarProgressoManual($estruturaId, $estruturaData);
            } catch (Exception $e) {
                Logger::error('Erro ao inicializar progresso', ['erro' => $e->getMessage()]);
                // Continuar mesmo com erro no progresso
            }
            
            $dados = [
                'estrutura_id' => $estruturaId,
                'empresa' => $empresa,
                'diagnostico' => $diagnostico,
                'setores' => $diagnosticoEstrutura['setores'] ?? [],
                'progresso' => $this->buscarProgressoManual($estruturaId) ?? [
                    'progresso_percentual' => 5,
                    'etapa_atual' => 'etapa2a'
                ]
            ];

            require VIEW_PATH . '/sop/mapear-servicos.php';
            
        } catch (Exception $e) {
            Logger::error('Erro na tela de mapeamento de serviços', [
                'erro' => $e->getMessage(),
                'estrutura_id' => $estruturaId
            ]);
            
            Flash::set('erro', 'Erro interno: ' . $e->getMessage());
            header('Location: ' . APP_URL . '/sop');
            exit;
        }
    }

    /**
     * ETAPA 2A: Executar mapeamento de um setor específico via AJAX
     */
    public function executarMapeamentoSetor(): void
    {
        Logger::info('Método executarMapeamentoSetor chamado', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'post_data' => $_POST
        ]);
        
        $inicioProcesso = microtime(true);
        
        Auth::proteger();
        
        Logger::info('=== INICIANDO MAPEAMENTO DE SETOR ===', [
            'timestamp' => date('Y-m-d H:i:s'),
            'usuario_id' => Auth::id(),
            'perfil' => Auth::perfil(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Verificar e criar tabelas necessárias se não existirem
        try {
            $this->verificarTabelasNovaArquitetura();
            Logger::info('Tabelas da nova arquitetura verificadas/criadas com sucesso');
        } catch (Exception $e) {
            Logger::error('Erro ao verificar/criar tabelas da nova arquitetura', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Erro na infraestrutura do banco de dados: ' . $e->getMessage(),
                'codigo_erro' => 'DATABASE_SETUP_ERROR'
            ]);
            exit;
        }
        
        // Validação CSRF customizada para API
        if (!Csrf::validar()) {
            Logger::warning('Tentativa de acesso com CSRF inválido', [
                'csrf_enviado' => $_POST['csrf_token'] ?? 'ausente',
                'csrf_sessao' => Session::get('csrf_token') ? 'presente' : 'ausente',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Token CSRF inválido. Recarregue a página e tente novamente.',
                'codigo_erro' => 'CSRF_INVALID'
            ]);
            exit;
        }

        $estruturaId = (int) (isset($_POST['estrutura_id']) ? $_POST['estrutura_id'] : 0);
        $setorNome = isset($_POST['setor_nome']) ? trim($_POST['setor_nome']) : '';
        
        Logger::info('Dados recebidos do formulário', [
            'estrutura_id' => $estruturaId,
            'setor_nome' => $setorNome,
            'post_data_keys' => array_keys($_POST),
            'post_data_sizes' => array_map(function($v) { return is_string($v) ? strlen($v) : gettype($v); }, $_POST)
        ]);
        
        if (!$estruturaId || !$setorNome) {
            Logger::error('Dados incompletos no formulário', [
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'estrutura_id_valido' => $estruturaId > 0,
                'setor_nome_valido' => !empty($setorNome)
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }

        // Buscar dados necessários com função de recuperação
        Logger::info('Buscando estrutura temporária', ['estrutura_id' => $estruturaId]);
        $estruturaData = $this->recuperarEstruturaSeNecessario($estruturaId);
        if (!$estruturaData) {
            Logger::error('Estrutura temporária não encontrada', [
                'estrutura_id' => $estruturaId,
                'busca_realizada' => true
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Estrutura não encontrada.']);
            exit;
        }

        // GARANTIR PERSISTÊNCIA: Manter estrutura viva durante processamento
        $this->manterEstruturaViva($estruturaId);

        Logger::info('Estrutura temporária encontrada', [
            'estrutura_id' => $estruturaId,
            'diagnostico_id' => $estruturaData['diagnostico_id'],
            'estrutura_keys' => array_keys($estruturaData['estrutura'] ?? []),
            'estrutura_size' => strlen(json_encode($estruturaData['estrutura'] ?? []))
        ]);

        // Inicializar progresso se não existir
        try {
            $this->inicializarProgressoManual($estruturaId, $estruturaData);
            Logger::info('Progresso manual inicializado/verificado', ['estrutura_id' => $estruturaId]);
        } catch (Exception $e) {
            Logger::error('Erro ao inicializar progresso no mapeamento', [
                'estrutura_id' => $estruturaId,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Buscar diagnóstico e empresa
        Logger::info('Buscando dados do diagnóstico', ['diagnostico_id' => $estruturaData['diagnostico_id']]);
        $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
        if (!$diagnostico) {
            Logger::error('Diagnóstico não encontrado', [
                'diagnostico_id' => $estruturaData['diagnostico_id'],
                'estrutura_id' => $estruturaId
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Diagnóstico não encontrado.']);
            exit;
        }

        Logger::info('Diagnóstico encontrado', [
            'diagnostico_id' => $diagnostico['id'],
            'empresa_id' => $diagnostico['empresa_id'],
            'usuario_id' => $diagnostico['usuario_id'],
            'respostas_size' => strlen($diagnostico['respostas'] ?? ''),
            'criado_em' => $diagnostico['criado_em']
        ]);

        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
        if (!$empresa) {
            Logger::error('Empresa não encontrada', [
                'empresa_id' => $diagnostico['empresa_id'],
                'diagnostico_id' => $diagnostico['id']
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        Logger::info('Empresa encontrada', [
            'empresa_id' => $empresa['id'],
            'empresa_nome' => $empresa['nome'],
            'segmento' => $empresa['segmento'] ?? 'não informado'
        ]);

        // Preparar dados para o prompt
        $respostas = json_decode($diagnostico['respostas'], true) ?? [];
        Logger::info('Respostas do diagnóstico processadas', [
            'respostas_count' => count($respostas),
            'respostas_keys' => array_keys($respostas),
            'json_decode_success' => $respostas !== null
        ]);

        $dadosEmpresa = $this->extrairDadosEmpresaCompletos($empresa, $diagnostico, $respostas);
        Logger::info('Dados da empresa extraídos', [
            'dados_keys' => array_keys($dadosEmpresa),
            'nome' => $dadosEmpresa['nome'] ?? 'não definido',
            'nicho' => $dadosEmpresa['nicho'] ?? 'não definido',
            'macro_categoria' => $dadosEmpresa['macro_categoria'] ?? 'não definido',
            'porte' => $dadosEmpresa['porte'] ?? 'não definido'
        ]);

        // GARANTIR INTEGRIDADE: Validar dados completos antes de prosseguir
        $this->validarIntegridadeDados($estruturaId, $dadosEmpresa, $setorNome);

        // Validar se todos os dados necessários estão presentes
        $camposObrigatorios = ['nome', 'nicho', 'macro_categoria', 'porte', 'modelo_negocio'];
        foreach ($camposObrigatorios as $campo) {
            if (!isset($dadosEmpresa[$campo]) || empty($dadosEmpresa[$campo])) {
                Logger::error('Campo obrigatório ausente nos dados da empresa', [
                    'campo_ausente' => $campo,
                    'dados_empresa_completos' => $dadosEmpresa,
                    'respostas_diagnostico' => $respostas,
                    'empresa_data' => $empresa
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false, 
                    'erro' => "Dados da empresa incompletos. Campo '{$campo}' não encontrado.",
                    'codigo_erro' => 'DADOS_INCOMPLETOS'
                ]);
                exit;
            }
        }

        Logger::info('Todos os campos obrigatórios validados com sucesso', [
            'estrutura_id' => $estruturaId,
            'setor' => $setorNome,
            'empresa' => $dadosEmpresa['nome'],
            'nicho' => $dadosEmpresa['nicho'],
            'macro_categoria' => $dadosEmpresa['macro_categoria']
        ]);

        // CHAMADA API: Mapear todos os serviços do setor
        Logger::info('Preparando chamada para a API de mapeamento', [
            'setor' => $setorNome,
            'prompt_dados' => [
                'empresa_nome' => $dadosEmpresa['nome'],
                'nicho' => $dadosEmpresa['nicho'],
                'macro_categoria' => $dadosEmpresa['macro_categoria'],
                'porte' => $dadosEmpresa['porte']
            ]
        ]);

        try {
            $inicioAPI = microtime(true);
            $prompt = ApiHelper::buildPromptListagemServicos($setorNome, $dadosEmpresa);
            $tempoPrompt = microtime(true) - $inicioAPI;
            
            Logger::info('Prompt construído com sucesso', [
                'setor' => $setorNome,
                'prompt_size' => strlen($prompt),
                'tempo_construcao_ms' => round($tempoPrompt * 1000, 2),
                'prompt_preview' => substr($prompt, 0, 200) . '...'
            ]);

            $inicioChamadaAPI = microtime(true);
            $resultado = ApiHelper::chamarAnalise($prompt, true);
            $tempoChamadaAPI = microtime(true) - $inicioChamadaAPI;
            
            Logger::info('Resposta da API recebida', [
                'setor' => $setorNome,
                'tempo_chamada_ms' => round($tempoChamadaAPI * 1000, 2),
                'resultado_sucesso' => $resultado['sucesso'] ?? false,
                'resultado_keys' => array_keys($resultado ?? []),
                'conteudo_type' => gettype($resultado['conteudo'] ?? null),
                'conteudo_size' => is_array($resultado['conteudo'] ?? null) ? count($resultado['conteudo']) : 0
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao construir prompt ou chamar API', [
                'setor' => $setorNome,
                'erro_mensagem' => $e->getMessage(),
                'erro_codigo' => $e->getCode(),
                'erro_arquivo' => $e->getFile(),
                'erro_linha' => $e->getLine(),
                'dados_empresa_keys' => array_keys($dadosEmpresa),
                'trace' => $e->getTraceAsString()
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Erro interno: ' . $e->getMessage(),
                'codigo_erro' => 'API_ERROR'
            ]);
            exit;
        }

        if (!$resultado['sucesso'] || !is_array($resultado['conteudo'])) {
            Logger::error('API retornou erro ou conteúdo inválido', [
                'setor' => $setorNome,
                'resultado_completo' => $resultado,
                'conteudo_type' => gettype($resultado['conteudo'] ?? null),
                'erro_api' => $resultado['erro'] ?? 'não informado'
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro no mapeamento: ' . ($resultado['erro'] ?? 'Resposta inválida')]);
            exit;
        }

        $servicosMapeados = $resultado['conteudo'];
        Logger::info('Serviços mapeados pela API', [
            'setor' => $setorNome,
            'total_servicos' => count($servicosMapeados['servicos'] ?? []),
            'servicos_nomes' => array_map(function($s) { return $s['nome'] ?? 'sem nome'; }, $servicosMapeados['servicos'] ?? []),
            'funcao_principal' => $servicosMapeados['funcao_principal'] ?? 'não definida'
        ]);
        
        // Salvar no banco
        Logger::info('Salvando serviços mapeados no banco de dados', [
            'estrutura_id' => $estruturaId,
            'setor' => $setorNome,
            'total_servicos' => count($servicosMapeados['servicos'] ?? [])
        ]);

        try {
            $servicoMapeadoId = $this->salvarServicosMapeados($estruturaId, $setorNome, $servicosMapeados);
            Logger::info('Serviços salvos com sucesso', [
                'servico_mapeado_id' => $servicoMapeadoId,
                'estrutura_id' => $estruturaId,
                'setor' => $setorNome
            ]);
            
            // GARANTIR PERSISTÊNCIA: Manter estrutura viva após salvamento
            $this->manterEstruturaViva($estruturaId);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar serviços mapeados', [
                'estrutura_id' => $estruturaId,
                'setor' => $setorNome,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dados_para_salvar' => $servicosMapeados
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Erro ao salvar dados: ' . $e->getMessage(),
                'codigo_erro' => 'SAVE_ERROR'
            ]);
            exit;
        }
        
        // Atualizar progresso
        Logger::info('Atualizando progresso da Etapa 2A', ['estrutura_id' => $estruturaId]);
        try {
            $this->atualizarProgressoEtapa2A($estruturaId);
            Logger::info('Progresso atualizado com sucesso', ['estrutura_id' => $estruturaId]);
        } catch (Exception $e) {
            Logger::error('Erro ao atualizar progresso', [
                'estrutura_id' => $estruturaId,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        $tempoTotal = microtime(true) - $inicioProcesso;
        
        Logger::info('=== MAPEAMENTO DE SETOR CONCLUÍDO COM SUCESSO ===', [
            'setor' => $setorNome,
            'estrutura_id' => $estruturaId,
            'total_servicos' => count($servicosMapeados['servicos'] ?? []),
            'servico_mapeado_id' => $servicoMapeadoId,
            'tempo_total_segundos' => round($tempoTotal, 2),
            'performance' => [
                'tempo_prompt_ms' => round($tempoPrompt * 1000, 2),
                'tempo_api_ms' => round($tempoChamadaAPI * 1000, 2),
                'tempo_total_ms' => round($tempoTotal * 1000, 2)
            ]
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'setor' => $setorNome,
            'total_servicos' => count($servicosMapeados['servicos'] ?? []),
            'servicos' => $servicosMapeados['servicos'] ?? [],
            'funcao_principal' => $servicosMapeados['funcao_principal'] ?? '',
            'servico_mapeado_id' => $servicoMapeadoId,
            'tempo_processamento' => round($tempoTotal, 2)
        ]);
        exit;
    }

    // ===== NOVA ARQUITETURA DETALHADA - ETAPA 2B =====

    /**
     * ETAPA 2B: Detalhar todos os serviços individualmente
     * Interface para mostrar progresso e executar detalhamento
     */
    public function detalharServicos(): void
    {
        Auth::proteger();
        
        $estruturaId = (int) (isset($_GET['estrutura_id']) ? $_GET['estrutura_id'] : 0);
        if (!$estruturaId) {
            Flash::set('erro', 'Estrutura não encontrada.');
            header('Location: ' . APP_URL . '/sop');
            exit;
        }

        // Verificar se Etapa 2A foi concluída
        if (!$this->verificarEtapa2AConcluida($estruturaId)) {
            Flash::set('erro', 'É necessário completar o mapeamento de serviços primeiro.');
            header('Location: ' . APP_URL . '/sop/mapear-servicos?estrutura_id=' . $estruturaId);
            exit;
        }

        // Buscar todos os serviços mapeados
        $servicosMapeados = $this->buscarServicosMapeados($estruturaId);
        $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
        $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
        
        $dados = [
            'estrutura_id' => $estruturaId,
            'empresa' => $empresa,
            'diagnostico' => $diagnostico,
            'servicos_mapeados' => $servicosMapeados,
            'progresso' => $this->buscarProgressoManual($estruturaId)
        ];

        require VIEW_PATH . '/sop/detalhar-servicos.php';
    }

    /**
     * ETAPA 2B: Executar detalhamento de um serviço específico via AJAX
     */
    public function executarDetalhamentoServico(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $estruturaId = (int) (isset($_POST['estrutura_id']) ? $_POST['estrutura_id'] : 0);
        $servicoNome = isset($_POST['servico_nome']) ? trim($_POST['servico_nome']) : '';
        $servicoCodigo = isset($_POST['servico_codigo']) ? trim($_POST['servico_codigo']) : '';
        $setorNome = isset($_POST['setor_nome']) ? trim($_POST['setor_nome']) : '';
        
        if (!$estruturaId || !$servicoNome || !$servicoCodigo) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }

        // Buscar dados do serviço mapeado
        $servicoMapeado = $this->buscarDadosServicoMapeado($estruturaId, $servicoCodigo, $setorNome);
        if (!$servicoMapeado) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Serviço não encontrado.']);
            exit;
        }

        // Buscar contextos da empresa
        $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
        $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
        $respostas = json_decode($diagnostico['respostas'], true) ?? [];
        $contextosEmpresa = $this->extrairDadosEmpresaCompletos($empresa, $diagnostico, $respostas);

        Logger::info('Iniciando detalhamento de serviço', [
            'servico' => $servicoNome,
            'codigo' => $servicoCodigo,
            'setor' => $setorNome
        ]);

        // CHAMADA API: Detalhar completamente o serviço
        $prompt = ApiHelper::buildPromptDetalhamentoServico($servicoNome, $contextosEmpresa, $servicoMapeado);
        $resultado = ApiHelper::chamarAnalise($prompt, true);

        if (!$resultado['sucesso'] || !is_array($resultado['conteudo'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro no detalhamento: ' . ($resultado['erro'] ?? 'Resposta inválida')]);
            exit;
        }

        $detalhamentoCompleto = $resultado['conteudo'];
        
        // Salvar detalhamento no banco
        $servicoDetalhadoId = $this->salvarServicoDetalhado($estruturaId, $servicoMapeado, $detalhamentoCompleto);
        
        // Atualizar progresso
        $this->atualizarProgressoEtapa2B($estruturaId);
        
        Logger::info('Detalhamento de serviço concluído', [
            'servico' => $servicoNome,
            'problemas_mapeados' => count($detalhamentoCompleto['problemas_possiveis'] ?? []),
            'servico_detalhado_id' => $servicoDetalhadoId
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'servico' => $servicoNome,
            'problemas_mapeados' => count($detalhamentoCompleto['problemas_possiveis'] ?? []),
            'detalhamento_id' => $servicoDetalhadoId
        ]);
        exit;
    }

    /**
     * NOVA ARQUITETURA: Processa todos os SOPs individualmente
     * Etapa 2: Geração profunda de cada SOP
     */
    public function processarSOPs(): void
    {
        Auth::proteger();
        
        $estruturaId = (int) (isset($_GET['estrutura_id']) ? $_GET['estrutura_id'] : 0);
        if (!$estruturaId) {
            Flash::set('erro', 'Estrutura não encontrada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar estrutura salva
        $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
        if (!$estruturaData) {
            Flash::set('erro', 'Dados da estrutura não encontrados.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        $diagnosticoEstrutura = $estruturaData['estrutura'];
        $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);

        // Preparar contextos para geração dos SOPs
        $respostas = json_decode($diagnostico['respostas'], true) ?? [];
        $contextosEmpresa = $this->extrairContextosEmpresa($empresa, $diagnostico, $respostas, $diagnosticoEstrutura);

        require VIEW_PATH . '/sop/processar-sops.php';
    }

    /**
     * NOVA ARQUITETURA: Monta manual final
     * Etapa 3: Consolidação de todos os SOPs
     */
    public function montarManualFinal(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $estruturaId = (int) (isset($_POST['estrutura_id']) ? $_POST['estrutura_id'] : 0);
        
        if (!$estruturaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Estrutura não informada.']);
            exit;
        }

        // Buscar estrutura e SOPs gerados
        $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
        if (!$estruturaData) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Estrutura não encontrada.']);
            exit;
        }

        $diagnosticoEstrutura = $estruturaData['estrutura'];
        $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);

        // Buscar todos os SOPs gerados para esta empresa
        $sopsGerados = $this->buscarSOPsGerados($diagnostico['empresa_id'], $diagnostico['id']);
        
        if (empty($sopsGerados)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum SOP foi gerado ainda.']);
            exit;
        }

        Logger::info('Montando manual final', [
            'empresa_id' => $diagnostico['empresa_id'],
            'total_sops_gerados' => count($sopsGerados)
        ]);

        // Consolidar todo o conteúdo
        $todosOsSops = $this->consolidarConteudoSOPs($sopsGerados);

        // CHAMADA 3: Montagem final do manual
        $prompt3 = ApiHelper::buildPromptMontagemFinal(
            $diagnosticoEstrutura['diagnostico'],
            $diagnosticoEstrutura,
            $todosOsSops
        );
        
        $resultado3 = ApiHelper::chamarAnalise($prompt3, false); // Resposta em Markdown

        if (!$resultado3['sucesso']) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro na montagem final: ' . ($resultado3['erro'] ?? 'Erro desconhecido')]);
            exit;
        }

        // Salvar manual completo
        $manualId = $this->salvarManualCompleto($diagnostico['empresa_id'], $diagnostico['id'], $resultado3['conteudo']);

        Logger::info('Manual completo gerado com sucesso', [
            'manual_id' => $manualId,
            'tamanho_manual' => strlen($resultado3['conteudo']),
            'total_sops_incluidos' => count($sopsGerados)
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'manual_id' => $manualId,
            'redirect' => APP_URL . '/sop/manual-completo?id=' . $manualId
        ]);
        exit;
    }

    /**
     * Gera um SOP individual via AJAX (método original mantido para compatibilidade)
     */
    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopCodigo = htmlspecialchars(trim(isset($_POST['sop_id']) ? $_POST['sop_id'] : ''));
        $sopNome = htmlspecialchars(trim(isset($_POST['sop_nome']) ? $_POST['sop_nome'] : ''));
        $diagnosticoIdPost = (int) (isset($_POST['diagnostico_id']) ? $_POST['diagnostico_id'] : 0);
        
        // IMPORTANTE: Priorizar empresa do diagnóstico, não do usuário atual
        if ($diagnosticoIdPost > 0) {
            $diagnosticoEspecifico = Diagnostico::buscarPorId($diagnosticoIdPost);
            if ($diagnosticoEspecifico) {
                $empresaId = $diagnosticoEspecifico['empresa_id'];
                Logger::info('DIAGNÓSTICO ESPECÍFICO DETECTADO - Usando dados específicos da empresa', [
                    'diagnostico_id' => $diagnosticoIdPost,
                    'empresa_id' => $empresaId,
                    'sop_codigo' => $sopCodigo,
                    'modo' => 'ESPECÍFICO_DO_DIAGNÓSTICO'
                ]);
            } else {
                $empresaId = Auth::garantirEmpresa();
                Logger::warning('Diagnóstico especificado não encontrado, usando empresa do usuário', [
                    'diagnostico_id_solicitado' => $diagnosticoIdPost,
                    'empresa_usuario' => $empresaId
                ]);
            }
        } else {
            $empresaId = Auth::garantirEmpresa();
            Logger::info('Modo genérico - usando empresa do usuário atual', [
                'empresa_id' => $empresaId,
                'modo' => 'GENÉRICO'
            ]);
        }

        if (empty($sopCodigo) || !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar se SOP já existe
        $sopExistente = Sop::buscarPorCodigo($sopCodigo);
        if ($sopExistente) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP já existe. Redirecionando para revisão.',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopExistente['id'],
            ]);
            exit;
        }

        // Buscar dados da empresa e diagnóstico
        $empresa = Empresa::buscarPorId($empresaId);
        
        // SEMPRE priorizar diagnóstico específico se foi passado
        $diagnostico = null;
        if ($diagnosticoIdPost > 0) {
            $diagnostico = Diagnostico::buscarPorId($diagnosticoIdPost);
            Logger::info('USANDO DIAGNÓSTICO ESPECÍFICO para geração de SOP', [
                'diagnostico_id' => $diagnosticoIdPost,
                'sop_codigo' => $sopCodigo,
                'empresa_id' => $empresaId,
                'empresa_nome' => isset($empresa['nome']) ? $empresa['nome'] : 'N/A',
                'diagnostico_encontrado' => !empty($diagnostico),
                'respostas_diagnostico' => !empty($diagnostico['respostas'])
            ]);
        } else {
            $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
            Logger::info('Usando último diagnóstico da empresa', [
                'empresa_id' => $empresaId,
                'diagnostico_id' => isset($diagnostico['id']) ? $diagnostico['id'] : null
            ]);
        }
        
        if (!$empresa) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        // PROCESSO ROBUSTO DE MAPEAMENTO COM DIAGNÓSTICO ESPECÍFICO
        $mapeamentoCompleto = $this->criarMapeamentoEmpresarial($empresa, $diagnostico);
        
        Logger::info('Mapeamento empresarial criado', [
            'empresa_id' => $empresaId,
            'diagnostico_usado' => !empty($diagnostico),
            'departamentos_extraidos' => $mapeamentoCompleto['departamentos_texto'],
            'ferramentas_extraidas' => $mapeamentoCompleto['ferramentas'],
            'problemas_extraidos' => $mapeamentoCompleto['problemas'],
            'colaboradores' => $mapeamentoCompleto['colaboradores'],
            'maturidade' => $mapeamentoCompleto['maturidade']
        ]);
        
        // Montar dados ESPECÍFICOS da empresa para o prompt
        $empresaDados = [
            'nome' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'colaboradores' => $mapeamentoCompleto['colaboradores'],
            'faturamento' => $mapeamentoCompleto['faturamento'],
            'maturidade' => $mapeamentoCompleto['maturidade'],
            'departamentos' => $mapeamentoCompleto['departamentos_texto'],
            'ferramentas' => $mapeamentoCompleto['ferramentas'],
            'problemas' => $mapeamentoCompleto['problemas'],
            'objetivos' => $mapeamentoCompleto['objetivos'],
            'mapeamento_detalhado' => $mapeamentoCompleto['mapeamento_detalhado'],
            'procedimentos_mercado' => $mapeamentoCompleto['procedimentos_mercado']
        ];
        
        Logger::info('Dados específicos da empresa preparados para IA', [
            'empresa_nome' => $empresaDados['nome'],
            'setor' => $empresaDados['setor'],
            'colaboradores' => $empresaDados['colaboradores'],
            'departamentos' => $empresaDados['departamentos'],
            'ferramentas' => $empresaDados['ferramentas'],
            'problemas' => substr($empresaDados['problemas'], 0, 200) . '...',
            'tem_mapeamento_detalhado' => !empty($empresaDados['mapeamento_detalhado']),
            'tem_procedimentos_mercado' => !empty($empresaDados['procedimentos_mercado'])
        ]);

        // Dados ESPECÍFICOS do SOP com contexto do diagnóstico
        $departamentoSOP = $this->getDepartamentoPorId($sopCodigo);
        $contextoEspecifico = isset($mapeamentoCompleto['departamentos_detalhados'][$departamentoSOP]) ? $mapeamentoCompleto['departamentos_detalhados'][$departamentoSOP] : null;
        
        $sopData = [
            'id' => $sopCodigo,
            'nome' => $sopNome,
            'departamento' => $departamentoSOP,
            'subtopicos_texto' => $this->getSubtopicosPorIdEspecifico($sopCodigo, $diagnostico),
            'contexto_departamento' => $contextoEspecifico
        ];

        // Buscar documentos relevantes da empresa para enriquecer o SOP
        $documentosRelevantes = [];
        $contextoDocumentos = '';
        
        try {
            $areas = [$this->getDepartamentoPorId($sopCodigo)];
            // Verificar se a classe DocumentoProcessor está disponível
            if (class_exists('DocumentoProcessor')) {
                $documentosRelevantes = DocumentoProcessor::buscarDocumentosRelevantes($empresaId, $areas);
                $contextoDocumentos = DocumentoProcessor::construirContextoDocumentos($documentosRelevantes);
            }
            
            Logger::info('Documentos encontrados para SOP', [
                'sop_codigo' => $sopCodigo,
                'documentos_encontrados' => count($documentosRelevantes)
            ]);
        } catch (Exception $e) {
            Logger::warning('Erro ao buscar documentos para SOP', [
                'sop_codigo' => $sopCodigo,
                'erro' => $e->getMessage()
            ]);
        }

        // Gerar prompt estruturado com contexto dos documentos - USAR PROMPT PADRÃO-OURO
        $prompt = ApiHelper::buildPromptSopDetalhado($empresaDados, $sopData, $contextoDocumentos);
        
        // Log para acompanhar uso da nova estrutura
        Logger::info('Gerando SOP com prompt padrão-ouro DETALHADO', [
            'sop_codigo' => $sopCodigo,
            'empresa' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'prompt_size' => strlen($prompt),
            'contexto_documentos' => !empty($contextoDocumentos),
            'departamentos_empresa' => isset($mapeamentoCompleto['departamentos_array']) ? $mapeamentoCompleto['departamentos_array'] : [],
            'usa_diagnostico_especifico' => !empty($diagnosticoIdPost),
            'diagnostico_id' => $diagnosticoIdPost ?? null,
            'ferramentas_empresa' => $empresaDados['ferramentas'],
            'problemas_empresa' => substr($empresaDados['problemas'], 0, 100),
            'contexto_departamento_especifico' => !empty($contextoEspecifico)
        ]);

        // Chamar IA (GPT ou Claude conforme config)
        $resultado = ApiHelper::chamarAnalise($prompt, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Log da estrutura retornada pela IA
            $novaEstrutura = isset($resultado['conteudo']['cabecalho']);
            Logger::info('Resposta da IA recebida', [
                'sop_codigo' => $sopCodigo,
                'nova_estrutura_detectada' => $novaEstrutura,
                'secoes_retornadas' => array_keys($resultado['conteudo']),
                'total_secoes' => count($resultado['conteudo'])
            ]);
            
            // Sucesso: criar SOP no banco
            $sopId = $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, isset($diagnostico['id']) ? $diagnostico['id'] : null, $resultado['conteudo']);
            
            if ($sopId) {
                // Registrar uso dos documentos que contribuíram para o SOP
                if (class_exists('DocumentoProcessor') && !empty($documentosRelevantes)) {
                    foreach ($documentosRelevantes as $doc) {
                        DocumentoProcessor::registrarUso($doc['id'], $empresaId, Auth::id(), 'sop_geracao', $sopId);
                    }
                }
                
                Logger::acao('SOP gerado via IA', [
                    'sop_codigo' => $sopCodigo, 
                    'sop_id' => $sopId,
                    'documentos_utilizados' => count($documentosRelevantes),
                    'diagnostico_origem' => isset($diagnostico['id']) ? $diagnostico['id'] : 'ultimo_disponivel'
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'SOP gerado com sucesso!' . 
                        (count($documentosRelevantes) > 0 ? " Utilizou " . count($documentosRelevantes) . " documento(s) da empresa." : ''),
                    'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar SOP no banco de dados.']);
            }
        } else {
            // IA falhou: criar SOP básico e avisar
            $sopId = $this->criarSopBasico($sopCodigo, $sopNome, $empresaId, isset($diagnostico['id']) ? $diagnostico['id'] : null);
            Logger::warning('SOP gerado básico (IA falhou)', ['sop_codigo' => $sopCodigo, 'erro' => $resultado['erro']]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP gerado! (Alguns campos podem precisar de ajuste.)',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
                'aviso' => $resultado['erro'],
            ]);
        }
        exit;
    }

    /**
     * Formata resposta da IA para o formato esperado pela view
     */
    private function formatarRespostaIA(array $ia, string $sopId, string $sopNome, array $empresa): array
    {
        return [
            'id' => $sopId,
            'nome' => $sopNome,
            'versao' => $ia['versionamento']['versao'] ?? '1.0',
            'empresa' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $ia['objetivo'] ?? '',
            'escopo_aplica' => $ia['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $ia['escopo']['nao_aplica'] ?? '',
            'subtopicos' => $ia['subtopicos'] ?? [],
            'responsaveis' => $ia['responsaveis'] ?? [],
            'prerequisitos' => $ia['prerequisitos'] ?? [],
            'ferramentas' => $ia['ferramentas'] ?? [],
            'procedimento_subtopico_1' => $ia['procedimentos'][0]['passos'] ?? [],
            'checklist' => $ia['checklist'] ?? [],
            'evidencias' => $ia['evidencias'] ?? [],
            'relatorios' => $ia['relatorios'] ?? [],
            'kpis' => $ia['kpis'] ?? [],
            'contencao_n1' => $ia['contencao']['n1'] ?? [],
            'contencao_n2' => $ia['contencao']['n2'] ?? [],
            'contencao_n3' => $ia['contencao']['n3'] ?? [],
        ];
    }

    /**
     * Retorna departamento a partir do ID do SOP
     */
    private function getDepartamentoPorId(string $sopId): string
    {
        $map = ['COM' => 'Comercial', 'ONB' => 'Onboarding', 'OPS' => 'Operacional', 'FIN' => 'Financeiro', 'JUR' => 'Jurídico', 'RH' => 'RH'];
        preg_match('/SOP-\w+-(\w+)-/', $sopId, $m);
        return $map[$m[1] ?? ''] ?? 'Geral';
    }

    /**
     * Retorna subtópicos específicos baseados no diagnóstico da empresa
     */
    private function getSubtopicosPorIdEspecifico(string $sopId, ?array $diagnostico): string
    {
        if (!$diagnostico) {
            return $this->getSubtopicosPorId($sopId);
        }
        
        $respostas = json_decode($diagnostico['respostas'], true) ?? [];
        $departamento = $this->getDepartamentoPorId($sopId);
        
        // Gerar subtópicos específicos baseados no diagnóstico
        $problemasEmpresa = explode(',', $this->extrairProblemas($diagnostico));
        $ferramentasEmpresa = explode(',', $this->extrairFerramentas($diagnostico));
        
        switch($departamento) {
            case 'Comercial':
                return "- Subtópico A: Prospecção usando " . implode(' e ', array_slice($ferramentasEmpresa, 0, 2)) . 
                       "\n- Subtópico B: Qualificação de Leads e Follow-up" .
                       "\n- Subtópico C: Fechamento e Pós-venda";
                       
            case 'TI':
                return "- Subtópico A: Recebimento via " . implode(' e ', array_slice($ferramentasEmpresa, 0, 2)) .
                       "\n- Subtópico B: Classificação por Prioridade" .
                       "\n- Subtópico C: Resolução e Documentação";
                       
            case 'Operações':
                $problema1 = trim($problemasEmpresa[0] ?? 'Falta de padronização');
                return "- Subtópico A: Planejamento e Recursos" .
                       "\n- Subtópico B: Execução com Controle de Qualidade" .
                       "\n- Subtópico C: Tratamento de: " . $problema1;
                       
            case 'Financeiro':
                return "- Subtópico A: Controle Diário de Fluxo" .
                       "\n- Subtópico B: Conciliação e Conferências" .
                       "\n- Subtópico C: Relatórios e Análises";
                       
            case 'RH':
                return "- Subtópico A: Processo Seletivo" .
                       "\n- Subtópico B: Onboarding e Integração" .
                       "\n- Subtópico C: Acompanhamento e Desenvolvimento";
                       
            default:
                return "- Subtópico A: Preparação e Planejamento Específico" .
                       "\n- Subtópico B: Execução com Monitoramento" .
                       "\n- Subtópico C: Verificação e Melhoria Contínua";
        }
    }

    /**
     * Retorna subtópicos pré-definidos para o SOP
     */
    private function getSubtopicosPorId(string $sopId): string
    {
        $subtopicos = [
            'SOP-TI-ONB-001' => "- Subtópico A: Fornecedor Amistoso (quando coopera)\n- Subtópico B: Fornecedor Não Amistoso (quando dificulta)\n- Subtópico C: Etapas Seguras de Migração (sequência sem downtime)",
            'SOP-TI-OPS-002' => "- Subtópico A: Configuração da Rotina de Backup\n- Subtópico B: Validação e Teste de Restauração\n- Subtópico C: Resposta a Falha de Backup",
            'SOP-TI-OPS-001' => "- Subtópico A: Recebimento e Classificação do Chamado\n- Subtópico B: Escalonamento e Priorização\n- Subtópico C: Resolução e Fechamento com Validação do Cliente",
        ];
        return $subtopicos[$sopId] ?? "- Subtópico A: Preparação e planejamento\n- Subtópico B: Execução e monitoramento\n- Subtópico C: Verificação e encerramento";
    }

    /**
     * Tela de revisão do SOP gerado (13 componentes) — F-05 Implementation
     */
    public function revisar(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP no banco
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado ou corrompido.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Formatar dados para a view (manter compatibilidade com view existente)
        $sopFormatado = $this->formatarSopParaView($sop, $conteudoCompleto);

        $dados = [
            'sop' => $sopFormatado,
        ];

        require VIEW_PATH . '/sop/revisar.php';
    }

    /**
     * Aprova SOP via AJAX — F-05 Implementation
     */
    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $usuarioId = Auth::usuarioId();

        if (!$sopId || !$usuarioId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        if ($sop['status'] === 'ativo') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP já está aprovado.']);
            exit;
        }

        // Aprovar SOP (salva KPIs e contingência automaticamente)
        $sucesso = Sop::aprovar($sopId, $usuarioId);

        if ($sucesso) {
            Logger::acao('SOP aprovado', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP aprovado! KPIs e planos de contingência foram salvos automaticamente.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao aprovar SOP.']);
        }
        exit;
    }

    /**
     * Salva rascunho via AJAX — F-05 Implementation
     */
    public function salvarRascunho(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Processar campos editados enviados via POST
        $camposEditados = [];
        $camposPermitidos = [
            'objetivo', 'escopo_aplica', 'escopo_nao_aplica', 'subtopicos', 
            'responsaveis', 'prerequisitos', 'ferramentas', 'checklist', 
            'evidencias', 'relatorios', 'kpis', 'contencao_n1', 'contencao_n2', 'contencao_n3'
        ];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($_POST[$campo])) {
                $camposEditados[$campo] = is_array($_POST[$campo]) ? $_POST[$campo] : trim($_POST[$campo]);
            }
        }
        
        // Se há campos editados, atualizar o conteúdo
        if (!empty($camposEditados)) {
            $conteudoAtual = json_decode($sop['conteudo_completo'], true) ?: [];
            $conteudoAtualizado = array_merge($conteudoAtual, $camposEditados);
            
            // Salvar conteúdo atualizado
            $sucesso = Database::execute(
                "UPDATE sops SET conteudo_completo = :conteudo, atualizado_em = NOW() WHERE id = :id",
                [
                    'conteudo' => json_encode($conteudoAtualizado, JSON_UNESCAPED_UNICODE),
                    'id' => $sopId
                ]
            );
            
            if (!$sucesso) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar alterações.']);
                exit;
            }
        }
        
        Logger::acao('SOP salvo como rascunho', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Rascunho salvo!']);
        exit;
    }

    /**
     * Visualizar SOP completo com subtópicos — F-06 Implementation
     */
    public function ver(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        if ($sop['status'] !== 'ativo') {
            Flash::set('aviso', 'Este SOP ainda não foi aprovado.');
            header('Location: ' . APP_URL . '/sop/revisar?id=' . $sopId);
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar empresa
        $empresa = Empresa::buscarPorId($sop['empresa_id']);

        // Buscar alertas ativos para este SOP
        $alertasAtivos = $this->buscarAlertasAtivos($sopId);

        // Buscar KPIs do SOP
        $kpis = Sop::buscarKpis($sopId);

        // Formatar dados para view
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        $dados = [
            'sop' => $sopFormatado,
            'alertas_ativos' => $alertasAtivos,
        ];

        require VIEW_PATH . '/sop/ver.php';
    }

    /**
     * Listar todos os SOPs gerados para um diagnóstico específico
     * PRIORIDADE: Mostrar dados da nova arquitetura se existirem
     */
    public function listarSopsPorDiagnostico(): void
    {
        Auth::proteger();
        
        $diagnosticoId = (int) (isset($_GET['diagnostico_id']) ? $_GET['diagnostico_id'] : 0);
        
        if (!$diagnosticoId) {
            Flash::set('erro', 'Diagnóstico não especificado.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        // Buscar diagnóstico
        $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
        if (!$diagnostico) {
            Flash::set('erro', 'Diagnóstico não encontrado.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        // Verificar permissão
        if (Auth::perfil() !== 'ADMIN_HOLDING' && $diagnostico['usuario_id'] != Auth::id()) {
            Flash::set('erro', 'Sem permissão para acessar este diagnóstico.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        // Buscar empresa
        $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        Logger::info('LISTAGEM SIMPLIFICADA - FLUXO LINEAR', [
            'diagnostico_id' => $diagnosticoId,
            'empresa_id' => $empresa['id']
        ]);

        // FLUXO SIMPLIFICADO: Buscar setores e serviços organizados hierarquicamente
        $setoresComServicos = Database::query(
            "SELECT DISTINCT
                so.id as setor_id,
                so.nome_setor,
                so.tipo_setor,
                so.descricao as setor_descricao,
                COUNT(sm.id) as total_servicos,
                COUNT(CASE WHEN s.id IS NOT NULL THEN 1 END) as total_sops
             FROM setores_organizacionais so
             INNER JOIN estruturas_hierarquicas eh ON so.estrutura_id = eh.id
             LEFT JOIN servicos_mapeados sm ON so.id = sm.setor_id
             LEFT JOIN sops s ON sm.id = s.servico_id
             WHERE eh.diagnostico_id = :diagnostico_id 
               AND eh.empresa_id = :empresa_id
             GROUP BY so.id, so.nome_setor, so.tipo_setor, so.descricao
             ORDER BY 
                CASE so.tipo_setor
                    WHEN 'core' THEN 1
                    WHEN 'apoio' THEN 2
                    WHEN 'estrategico' THEN 3
                    ELSE 4
                END,
                so.nome_setor",
            [
                'diagnostico_id' => $diagnosticoId,
                'empresa_id' => $empresa['id']
            ]
        );

        // Para cada setor, buscar seus serviços com SOPs
        $setoresOrganizados = [];
        foreach ($setoresComServicos as $setor) {
            $servicosDoSetor = Database::query(
                "SELECT 
                    sm.id,
                    sm.nome_servico,
                    sm.codigo_servico,
                    sm.categoria,
                    sm.criticidade,
                    sm.frequencia,
                    sm.status as servico_status,
                    s.id as sop_id,
                    s.nome as sop_nome,
                    s.status as sop_status,
                    s.criado_em as sop_criado_em,
                    CASE 
                        WHEN s.id IS NOT NULL THEN 'sop_gerado'
                        WHEN sm.detalhamento IS NOT NULL THEN 'detalhado'
                        ELSE 'mapeado'
                    END as status_final
                 FROM servicos_mapeados sm
                 LEFT JOIN sops s ON sm.id = s.servico_id
                 WHERE sm.setor_id = :setor_id
                 ORDER BY sm.categoria, sm.nome_servico",
                ['setor_id' => $setor['setor_id']]
            );
            
            $setoresOrganizados[] = [
                'setor' => $setor,
                'servicos' => $servicosDoSetor
            ];
        }

        // Estatísticas gerais
        $estatisticas = [
            'total_setores' => count($setoresOrganizados),
            'total_servicos' => array_sum(array_column($setoresComServicos, 'total_servicos')),
            'total_sops' => array_sum(array_column($setoresComServicos, 'total_sops')),
            'percentual_conclusao' => 0
        ];

        if ($estatisticas['total_servicos'] > 0) {
            $estatisticas['percentual_conclusao'] = round(
                ($estatisticas['total_sops'] / $estatisticas['total_servicos']) * 100, 1
            );
        }

        $dados = [
            'diagnostico' => $diagnostico,
            'empresa' => $empresa,
            'setores_organizados' => $setoresOrganizados,
            'estatisticas' => $estatisticas,
            'usar_fluxo_linear' => true
        ];

        Logger::info('DADOS PREPARADOS PARA FLUXO LINEAR', [
            'total_setores' => $estatisticas['total_setores'],
            'total_servicos' => $estatisticas['total_servicos'],
            'total_sops' => $estatisticas['total_sops'],
            'percentual_conclusao' => $estatisticas['percentual_conclusao']
        ]);

        require VIEW_PATH . '/sop/listar-por-diagnostico.php';
    }

    /**
     * Buscar estrutura da nova arquitetura por diagnóstico
     */
    private function buscarEstruturaPorDiagnostico(int $diagnosticoId): ?array
    {
        try {
            // Primeiro tentar a nova tabela hierárquica
            $estrutura = Database::queryOne(
                "SELECT * FROM estruturas_organizacionais WHERE diagnostico_id = ? ORDER BY criado_em DESC LIMIT 1",
                [$diagnosticoId]
            );
            
            if ($estrutura) {
                Logger::info('Encontrada estrutura hierárquica', [
                    'diagnostico_id' => $diagnosticoId,
                    'estrutura_id' => $estrutura['id']
                ]);
                return $estrutura;
            }
            
            // Fallback para estrutura temporária se existir
            $estruturaTemporaria = Database::queryOne(
                "SELECT * FROM estruturas_temporarias WHERE diagnostico_id = ? ORDER BY criado_em DESC LIMIT 1",
                [$diagnosticoId]
            );
            
            if ($estruturaTemporaria) {
                Logger::info('Encontrada estrutura temporária (fallback)', [
                    'diagnostico_id' => $diagnosticoId,
                    'estrutura_id' => $estruturaTemporaria['id']
                ]);
                return $estruturaTemporaria;
            }
            
            return null;
            
        } catch (Exception $e) {
            Logger::info('Tabelas da nova arquitetura não encontradas', ['erro' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Carregar dados da nova arquitetura (PRIORIDADE)
     */
    private function carregarDadosNovaArquitetura(array $diagnostico, array $empresa, array $estrutura): array
    {
        $estruturaId = $estrutura['id'];
        
        try {
            // Verificar se é estrutura hierárquica ou temporária
            $isHierarquica = isset($estrutura['nicho']); // Estrutura hierárquica tem o campo nicho
            
            if ($isHierarquica) {
                // NOVA ESTRUTURA HIERÁRQUICA
                return $this->carregarDadosHierarquicos($diagnostico, $empresa, $estrutura);
            } else {
                // ESTRUTURA TEMPORÁRIA (fallback)
                return $this->carregarDadosTemporarios($diagnostico, $empresa, $estrutura);
            }
        } catch (Exception $e) {
            Logger::error('Erro ao carregar dados da nova arquitetura', [
                'diagnostico_id' => $diagnostico['id'],
                'estrutura_id' => $estruturaId,
                'erro' => $e->getMessage()
            ]);
            
            // Fallback para dados mínimos
            return [
                'diagnostico' => $diagnostico,
                'empresa' => $empresa,
                'usar_nova_arquitetura' => false,
                'estrutura' => $estrutura,
                'setores' => [],
                'progresso' => null,
                'total_setores_mapeados' => 0,
                'total_servicos_detalhados' => 0,
                'estatisticas' => ['servicos_criticos' => 0],
                'erro_carregamento' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Carregar dados do sistema hierárquico
     */
    private function carregarDadosHierarquicos(array $diagnostico, array $empresa, array $estrutura): array
    {
        $estruturaId = $estrutura['id'];
        
        // Buscar setores da estrutura hierárquica
        $setores = Database::query(
            "SELECT se.*, COUNT(ss.id) as total_servicos_reais
             FROM setores_empresa se
             LEFT JOIN servicos_setor ss ON se.id = ss.setor_id
             WHERE se.estrutura_id = ?
             GROUP BY se.id
             ORDER BY se.nome_setor",
            [$estruturaId]
        );
        
        // Buscar progresso hierárquico
        $progresso = Database::queryOne(
            "SELECT * FROM progresso_hierarquico WHERE estrutura_id = ?",
            [$estruturaId]
        );
        
        // Organizar dados por setores (MESMO FORMATO DA GESTÃO HIERÁRQUICA)
        $setoresPorNome = [];
        $totalServicosDetalhados = 0;
        $totalServicosCriticos = 0;
        
        foreach ($setores as $setor) {
            // Buscar serviços do setor
            $servicos = Database::query(
                "SELECT * FROM servicos_setor 
                 WHERE setor_id = ? 
                 ORDER BY nome_servico",
                [$setor['id']]
            );
            
            // Separar serviços mapeados e detalhados
            $servicosMapeados = [];
            $servicosDetalhados = [];
            
            foreach ($servicos as $servico) {
                $servicosMapeados[] = ['nome' => $servico['nome_servico']];
                
                // Se não é apenas mapeado, incluir nos detalhados
                if ($servico['status'] !== 'mapeado') {
                    $servicosDetalhados[] = [
                        'id' => $servico['id'],
                        'servico_nome' => $servico['nome_servico'],
                        'servico_codigo' => $servico['codigo_servico'],
                        'criticidade' => $servico['criticidade'] === 'alta' ? 1 : ($servico['criticidade'] === 'media' ? 2 : 3),
                        'problemas_mapeados' => 0, // TODO: implementar contagem real
                        'data_criacao_formatada' => date('d/m/Y às H:i', strtotime($servico['criado_em'])),
                        'status' => $servico['status']
                    ];
                    $totalServicosDetalhados++;
                }
                
                if ($servico['criticidade'] === 'alta') {
                    $totalServicosCriticos++;
                }
            }
            
            $setoresPorNome[$setor['nome_setor']] = [
                'info' => [
                    'id' => $setor['id'],
                    'setor_tipo' => $setor['tipo_setor'],
                    'status' => $setor['status'],
                    'total_servicos' => $setor['total_servicos_reais']
                ],
                'servicos_mapeados' => $servicosMapeados,
                'servicos_detalhados' => $servicosDetalhados
            ];
        }
        
        Logger::info('Dados hierárquicos carregados com sucesso', [
            'diagnostico_id' => $diagnostico['id'],
            'estrutura_id' => $estruturaId,
            'total_setores' => count($setores),
            'total_servicos_detalhados' => $totalServicosDetalhados
        ]);
        
        return [
            'diagnostico' => $diagnostico,
            'empresa' => $empresa,
            'usar_nova_arquitetura' => true,
            'estrutura' => $estrutura,
            'setores' => $setoresPorNome,
            'progresso' => $progresso ?: ['percentual_conclusao' => 0],
            'total_setores_mapeados' => count($setores),
            'total_servicos_detalhados' => $totalServicosDetalhados,
            'estatisticas' => [
                'servicos_criticos' => $totalServicosCriticos
            ],
            'sistema' => 'hierarquico' // IMPORTANTE: Marcar como sistema hierárquico
        ];
    }
    
    /**
     * Carregar dados temporários (fallback)
     */
    private function carregarDadosTemporarios(array $diagnostico, array $empresa, array $estrutura): array
    {
        $estruturaId = $estrutura['id'];
        
        // Buscar setores mapeados da estrutura temporária
        $setoresMapeados = Database::query(
            "SELECT * FROM servicos_mapeados WHERE estrutura_id = ? ORDER BY setor_nome",
            [$estruturaId]
        );

        // Buscar serviços detalhados da estrutura temporária
        $servicosDetalhados = Database::query(
            "SELECT sd.*, sm.setor_nome, sm.setor_tipo,
                    DATE_FORMAT(sd.criado_em, '%d/%m/%Y às %H:%i') as data_criacao_formatada
             FROM servicos_detalhados sd
             JOIN servicos_mapeados sm ON sd.servico_mapeado_id = sm.id
             WHERE sd.estrutura_id = ?
             ORDER BY sm.setor_nome, sd.criticidade ASC, sd.servico_nome",
            [$estruturaId]
        );

        // Buscar progresso temporário
        $progresso = Database::queryOne(
            "SELECT * FROM progresso_manual WHERE estrutura_id = ?",
            [$estruturaId]
        );

        // Organizar por setores
        $setoresPorNome = [];
        foreach ($setoresMapeados as $setor) {
            $setoresPorNome[$setor['setor_nome']] = [
                'info' => $setor,
                'servicos_mapeados' => json_decode($setor['servicos_json'], true) ?? [],
                'servicos_detalhados' => []
            ];
        }

        foreach ($servicosDetalhados as $servico) {
            $setorNome = $servico['setor_nome'];
            if (isset($setoresPorNome[$setorNome])) {
                $setoresPorNome[$setorNome]['servicos_detalhados'][] = $servico;
            }
        }

        Logger::info('Dados temporários carregados (fallback)', [
            'diagnostico_id' => $diagnostico['id'],
            'total_setores' => count($setoresPorNome),
            'total_servicos_detalhados' => count($servicosDetalhados),
            'progresso' => $progresso['progresso_percentual'] ?? 0
        ]);

        return [
            'diagnostico' => $diagnostico,
            'empresa' => $empresa,
            'usar_nova_arquitetura' => true,
            'estrutura' => $estrutura,
            'setores' => $setoresPorNome,
            'progresso' => $progresso,
            'total_setores_mapeados' => count($setoresMapeados),
            'total_servicos_detalhados' => count($servicosDetalhados),
            'estatisticas' => $this->calcularEstatisticasNovaArquitetura($setoresPorNome),
            'sistema' => 'temporario'
        ];
    }

    /**
     * Carregar dados da arquitetura tradicional (FALLBACK)
     */
    private function carregarDadosArquiteturaTradicional(array $diagnostico, array $empresa): array
    {
        // Buscar SOPs tradicionais
        $sopsGerados = Database::query(
            "SELECT s.*, 
                    CASE 
                        WHEN s.status = 'ativo' THEN 'Aprovado'
                        WHEN s.status = 'rascunho' THEN 'Rascunho' 
                        WHEN s.status = 'revisao' THEN 'Em Revisão'
                        ELSE 'Não Definido'
                    END as status_formatado,
                    DATE_FORMAT(s.criado_em, '%d/%m/%Y às %H:%i') as data_criacao_formatada
             FROM sops s 
             WHERE s.empresa_id = ? AND s.diagnostico_id = ? 
             ORDER BY s.departamento, s.titulo",
            [$empresa['id'], $diagnostico['id']]
        );

        // Agrupar por departamento
        $sopsPorDepartamento = [];
        foreach ($sopsGerados as $sop) {
            $dept = $sop['departamento'] ?: 'Outros';
            if (!isset($sopsPorDepartamento[$dept])) {
                $sopsPorDepartamento[$dept] = [
                    'nome' => $dept,
                    'sops_tradicionais' => []
                ];
            }
            $sopsPorDepartamento[$dept]['sops_tradicionais'][] = $sop;
        }

        Logger::info('Dados da arquitetura tradicional carregados', [
            'diagnostico_id' => $diagnostico['id'],
            'total_sops' => count($sopsGerados),
            'departamentos' => array_keys($sopsPorDepartamento)
        ]);

        return [
            'diagnostico' => $diagnostico,
            'empresa' => $empresa,
            'usar_nova_arquitetura' => false,
            'sops_por_departamento' => $sopsPorDepartamento,
            'total_sops_tradicional' => count($sopsGerados),
            'total_geral' => count($sopsGerados),
            'sistema' => 'tradicional'
        ];
    }

    /**
     * Calcular estatísticas da nova arquitetura
     */
    private function calcularEstatisticasNovaArquitetura(array $setores): array
    {
        $totalServicos = 0;
        $servicosCriticos = 0;
        $servicosCompletos = 0;

        foreach ($setores as $setor) {
            foreach ($setor['servicos_detalhados'] as $servico) {
                $totalServicos++;
                
                if ($servico['criticidade'] == 1) {
                    $servicosCriticos++;
                }
                
                if ($servico['status'] == 'concluido') {
                    $servicosCompletos++;
                }
            }
        }

        return [
            'total_servicos' => $totalServicos,
            'servicos_criticos' => $servicosCriticos,
            'servicos_completos' => $servicosCompletos,
            'percentual_completo' => $totalServicos > 0 ? round(($servicosCompletos / $totalServicos) * 100, 1) : 0
        ];
    }

    /**
     * Carregar planos de contingência via AJAX — F-06 Implementation
     */
    public function contencao(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        $nivelSugerido = $_GET['nivel'] ?? 'N1';

        if (!$sopId) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado.</div>';
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado ou sem permissão.</div>';
            exit;
        }

        // Buscar planos de contingência
        $contencoes = Sop::buscarContencoes($sopId);
        $planos = [];
        
        foreach ($contencoes as $contencao) {
            $nivel = strtolower($contencao['nivel']);
            $planos[$nivel] = [
                'situacao' => $contencao['situacao'],
                'acao' => $contencao['acao'], 
                'quem' => $contencao['responsavel'],
                'escalar' => $contencao['escalar_se'],
                'comunicacao' => $contencao['comunicacao'],
                'documentacao' => $contencao['documentacao_obrigatoria'],
                'advogado_responsavel' => $contencao['nivel'] === 'N3' ? 'Dr. João Silva - (11) 99999-9999' : null,
            ];
        }

        // Buscar histórico de acionamentos
        $historicoContencao = $this->buscarHistoricoContencao($sopId);

        // Incluir view (não layout, é AJAX)
        include VIEW_PATH . '/sop/contencao.php';
    }

    /**
     * Acionar plano de contingência — F-06 Implementation  
     */
    public function acionarContencao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $nivel = $_POST['nivel'] ?? '';
        $situacaoDetectada = trim($_POST['situacao_detectada'] ?? '');

        if (!$sopId || !in_array($nivel, ['N1', 'N2', 'N3']) || empty($situacaoDetectada)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Buscar plano de contingência específico
        $contencao = Database::queryOne(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id AND nivel = :nivel LIMIT 1",
            ['sop_id' => $sopId, 'nivel' => $nivel]
        );

        if (!$contencao) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Plano de contingência não encontrado.']);
            exit;
        }

        Database::beginTransaction();

        try {
            // 1. Registrar ocorrência de contingência
            $sucessoInsert = Database::execute(
                "INSERT INTO ocorrencias_contencao (empresa_id, sop_id, contencao_id, nivel, situacao_detectada, responsavel_execucao, usuario_responsavel, data_inicio) 
                 VALUES (:empresa_id, :sop_id, :contencao_id, :nivel, :situacao_detectada, :responsavel_execucao, :usuario_responsavel, NOW())",
                [
                    'empresa_id' => $sop['empresa_id'],
                    'sop_id' => $sopId,
                    'contencao_id' => $contencao['id'],
                    'nivel' => $nivel,
                    'situacao_detectada' => $situacaoDetectada,
                    'responsavel_execucao' => $contencao['responsavel'],
                    'usuario_responsavel' => Auth::usuarioId(),
                ]
            );
            
            $ocorrenciaId = $sucessoInsert ? (int) Database::lastInsertId() : 0;

            // 2. Se é N2 ou N3, agendar revisão automática do SOP
            if (in_array($nivel, ['N2', 'N3'])) {
                $motivo = "Contenção {$nivel} acionada: {$situacaoDetectada}";
                Database::execute(
                    "UPDATE sops SET necessita_revisao = 1, motivo_revisao = :motivo, data_agendamento_revisao = NOW() 
                     WHERE id = :sop_id",
                    ['motivo' => $motivo, 'sop_id' => $sopId]
                );
            }

            // 3. Executar ações automáticas específicas por nível
            $acaoResultado = $this->executarContencaoAutomatica($nivel, $sop, $contencao, $situacaoDetectada);

            Database::commit();

            Logger::acao('Contingência acionada', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'situacao' => $situacaoDetectada,
                'ocorrencia_id' => $ocorrenciaId,
                'acao_resultado' => $acaoResultado
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => "Contingência {$nivel} acionada com sucesso.",
                'ocorrencia_id' => $ocorrenciaId,
                'proxima_acao' => $acaoResultado,
                'revisao_agendada' => in_array($nivel, ['N2', 'N3'])
            ]);

        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao acionar contingência', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'erro' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar acionamento: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Executa ações automáticas específicas por nível de contenção
     */
    private function executarContencaoAutomatica(string $nivel, array $sop, array $contencao, string $situacao): string
    {
        $empresaId = $sop['empresa_id'];
        $resultado = '';

        switch ($nivel) {
            case 'N1':
                // Ação básica: apenas registrar e alertar
                $resultado = 'Monitoramento ativo iniciado. Responsável notificado.';
                break;

            case 'N2':
                // Ação intermediária: escalar e intensificar monitoramento
                $resultado = 'Processo escalado. Monitoramento intensivo ativo. SOP agendado para revisão.';
                
                // Criar alerta crítico para direção
                Database::execute(
                    "INSERT INTO alertas (empresa_id, sop_id, tipo, titulo, descricao, prioridade, status, data_criacao)
                     VALUES (:empresa_id, :sop_id, 'contencao_n2', 'Contenção N2 Acionada', :descricao, 'critica', 'ativo', NOW())",
                    [
                        'empresa_id' => $empresaId,
                        'sop_id' => $sop['id'],
                        'descricao' => "SOP {$sop['sop_codigo']}: {$situacao}. Ação imediata necessária."
                    ]
                );
                break;

            case 'N3':
                // Ação crítica: parar processo e escalar para direção
                $resultado = 'PROCESSO INTERROMPIDO. Escalado para direção executiva. Análise de causa raiz obrigatória.';
                
                // Criar múltiplos alertas críticos
                Database::execute(
                    "INSERT INTO alertas (empresa_id, sop_id, tipo, titulo, descricao, prioridade, status, data_criacao)
                     VALUES (:empresa_id, :sop_id, 'contencao_n3_critica', 'CONTENÇÃO N3 - PROCESSO INTERROMPIDO', :descricao, 'critica', 'ativo', NOW())",
                    [
                        'empresa_id' => $empresaId,
                        'sop_id' => $sop['id'],
                        'descricao' => "URGENTE - SOP {$sop['sop_codigo']}: {$situacao}. Processo interrompido. Direção deve intervir IMEDIATAMENTE."
                    ]
                );

                // Agendar reunião de emergência (se sistema de agenda existir)
                try {
                    Database::execute(
                        "INSERT INTO agenda_emergencia (empresa_id, sop_id, titulo, descricao, data_agendamento, prioridade)
                         VALUES (:empresa_id, :sop_id, :titulo, :descricao, DATE_ADD(NOW(), INTERVAL 2 HOUR), 'critica')",
                        [
                            'empresa_id' => $empresaId,
                            'sop_id' => $sop['id'],
                            'titulo' => "REUNIÃO DE EMERGÊNCIA - Contenção N3",
                            'descricao' => "Análise de causa raiz obrigatória para: {$situacao}"
                        ]
                    );
                } catch (Exception $e) {
                    // Tabela de agenda pode não existir ainda
                    Logger::info('Agenda de emergência não criada - tabela inexistente', ['sop_id' => $sop['id']]);
                }
                break;
        }

        return $resultado;
    }

    /**
     * Buscar contenção por SOP e nível
     */
    private function buscarContencaoPorNivel(int $sopId, int $nivel): ?array
    {
        return Database::queryOne(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id AND nivel = :nivel LIMIT 1",
            ['sop_id' => $sopId, 'nivel' => $nivel]
        );

        if (!$contencao) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Plano de contingência não encontrado.']);
            exit;
        }

        // Registrar ocorrência
        $sucesso = Database::execute(
            "INSERT INTO ocorrencias_contencao (empresa_id, sop_id, contencao_id, nivel, situacao_detectada, responsavel_execucao, usuario_responsavel) 
             VALUES (:empresa_id, :sop_id, :contencao_id, :nivel, :situacao_detectada, :responsavel_execucao, :usuario_responsavel)",
            [
                'empresa_id' => $sop['empresa_id'],
                'sop_id' => $sopId,
                'contencao_id' => $contencao['id'],
                'nivel' => $nivel,
                'situacao_detectada' => $situacaoDetectada,
                'responsavel_execucao' => $contencao['responsavel'],
                'usuario_responsavel' => Auth::usuarioId(),
            ]
        );

        if ($sucesso) {
            Logger::acao('Contingência acionada', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'situacao' => $situacaoDetectada
            ]);

            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "Contingência {$nivel} acionada com sucesso."]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar acionamento.']);
        }
        exit;
    }
    /**
     * API para retornar função RACI de um cargo específico
     */
    public function getRaciFuncao(): void
    {
        Auth::proteger();
        
        $sopId = htmlspecialchars(trim($_GET['sop_id'] ?? ''));
        $cargo = htmlspecialchars(trim($_GET['cargo'] ?? ''));
        
        if (empty($sopId) || empty($cargo)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e cargo são obrigatórios.']);
            exit;
        }
        
        try {
            // Buscar matriz RACI do banco (se existir)
            $raciMatriz = Database::queryOne(
                "SELECT matriz_raci FROM sops WHERE sop_codigo = :sop_codigo",
                ['sop_codigo' => $sopId]
            );
            
            if ($raciMatriz && $raciMatriz['matriz_raci']) {
                $matriz = json_decode($raciMatriz['matriz_raci'], true);
                $funcao = $matriz[$cargo] ?? 'I';
            } else {
                // Usar matriz RACI padrão por tipo de SOP
                $funcao = $this->getFuncaoRaciPadrao($sopId, $cargo);
            }
            
            $descricoes = [
                'R' => 'Responsável - Executa a atividade',
                'A' => 'Aprovador - Aprova e é accountable pelo resultado',
                'C' => 'Consultado - Fornece input/conhecimento',
                'I' => 'Informado - Recebe informação sobre o resultado',
                '-' => 'Não envolvido'
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'funcao' => $funcao,
                'descricao' => $descricoes[$funcao] ?? 'Função não definida',
                'sop_id' => $sopId,
                'cargo' => $cargo
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar função RACI', ['erro' => $e->getMessage(), 'sop_id' => $sopId, 'cargo' => $cargo]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna função RACI padrão baseada no tipo de SOP e cargo
     */
    private function getFuncaoRaciPadrao(string $sopId, string $cargo): string
    {
        // Mapear funções RACI baseado no padrão de SOP
        $raciPadroes = [
            // SOPs Comerciais
            'SOP-TI-COM-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'C', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            // SOPs Onboarding
            'SOP-TI-ONB-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => 'I', 'Jurídico' => 'C', 'RH' => '-'
            ],
            // SOPs Operacionais
            'SOP-TI-OPS-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'R', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            'SOP-TI-OPS-002' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => '-', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Financeiros
            'SOP-TI-FIN-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'C', 'Analista N2' => '-', 
                'Suporte N1' => '-', 'Financeiro' => 'R', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Jurídicos
            'SOP-TI-JUR-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'C', 'Analista N2' => 'I', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => 'R', 'RH' => 'C'
            ],
        ];
        
        // Para SOPs de outros setores, usar padrão genérico baseado no departamento
        if (!isset($raciPadroes[$sopId])) {
            if (strpos($sopId, '-COM-') !== false) {
                return in_array($cargo, ['Diretor TI', 'Gerente Comercial']) ? 'A' : 
                       (in_array($cargo, ['Vendedor', 'Analista Comercial']) ? 'R' : 'I');
            } elseif (strpos($sopId, '-FIN-') !== false) {
                return in_array($cargo, ['Diretor Financeiro', 'Controller']) ? 'A' :
                       (in_array($cargo, ['Analista Financeiro', 'Contador']) ? 'R' : 'I');
            }
            return 'I'; // Padrão para SOPs não mapeados
        }
        
        return $raciPadroes[$sopId][$cargo] ?? 'I';
    }

    public function raci(): void
    {
        Auth::proteger();

        $dados = [
            'cargos' => ['Diretor TI', 'Gerente Ops', 'Analista N2', 'Suporte N1', 'Financeiro', 'Jurídico', 'RH'],
            'sops' => [
                ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação', 'raci' => ['A', 'R', 'C', 'I', '-', '-', '-']],
                ['id' => 'SOP-TI-ONB-001', 'nome' => 'Recebimento e migração', 'raci' => ['A', 'R', 'R', 'C', 'I', 'C', '-']],
                ['id' => 'SOP-TI-OPS-001', 'nome' => 'Gestão de chamados e SLA', 'raci' => ['I', 'A', 'R', 'R', '-', '-', '-']],
                ['id' => 'SOP-TI-OPS-002', 'nome' => 'Rotina de segurança e backups', 'raci' => ['I', 'A', 'R', 'C', '-', 'I', '-']],
                ['id' => 'SOP-TI-FIN-001', 'nome' => 'Faturamento e cobrança', 'raci' => ['I', 'C', '-', '-', 'R', 'I', '-']],
                ['id' => 'SOP-TI-JUR-001', 'nome' => 'LGPD e tratamento de dados', 'raci' => ['A', 'C', 'I', 'I', '-', 'R', 'C']],
            ],
        ];

        require VIEW_PATH . '/sop/raci.php';
    }

    /**
     * Solicita ajuste da IA em seção específica — F-05 Implementation
     */
    public function ajustar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $instrucao = trim($_POST['instrucao'] ?? '');
        $secoesAjustar = $_POST['secoes_a_ajustar'] ?? [];

        if (!$sopId || empty($instrucao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e instruções são obrigatórios.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        $conteudoAtual = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoAtual) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo do SOP não encontrado.']);
            exit;
        }

        // Montar prompt para ajuste específico
        $promptAjuste = $this->buildPromptAjuste($conteudoAtual, $instrucao, $secoesAjustar);
        
        // Chamar IA
        $resultado = ApiHelper::chamarAnalise($promptAjuste, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Mesclar seções atualizadas
            $conteudoNovo = $this->mesclarConteudo($conteudoAtual, $resultado['conteudo'], $secoesAjustar);
            
            // Incrementar versão
            $versaoNova = Sop::incrementarVersao($sop['versao']);
            
            // Salvar histórico
            Sop::salvarHistorico($sopId, $sop['versao'], $versaoNova, $conteudoAtual, $instrucao, Auth::usuarioId());
            
            // Atualizar SOP
            $sucesso = Sop::atualizar($sopId, [
                'conteudo_completo' => $conteudoNovo,
                'versao' => $versaoNova,
                'motivo_alteracao' => $instrucao,
            ]);

            if ($sucesso) {
                Logger::acao('SOP ajustado via IA', ['sop_id' => $sopId, 'versao_nova' => $versaoNova]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true, 
                    'mensagem' => "SOP atualizado para versão {$versaoNova}. Recarregando...",
                    'versao' => $versaoNova
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar ajustes.']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $resultado['erro'] ?? 'IA não disponível para ajustes.']);
        }
        exit;
    }

    /**
     * Monta prompt para ajuste específico
     */
    private function buildPromptAjuste(array $conteudoAtual, string $instrucao, array $secoes): string
    {
        $conteudoJson = json_encode($conteudoAtual, JSON_UNESCAPED_UNICODE);
        $secoesStr = implode(', ', $secoes);

        return "SOP atual (JSON): {$conteudoJson}

INSTRUÇÃO DE AJUSTE: {$instrucao}

Seções a ajustar: {$secoesStr}

Regere APENAS as seções indicadas mantendo a mesma estrutura JSON. As demais seções devem permanecer EXATAMENTE iguais.
Responda APENAS com JSON válido contendo as seções atualizadas.";
    }

    /**
     * Mescla conteúdo atualizado com o existente
     */
    private function mesclarConteudo(array $original, array $atualizado, array $secoes): array
    {
        foreach ($secoes as $secao) {
            if (isset($atualizado[$secao])) {
                $original[$secao] = $atualizado[$secao];
            }
        }
        return $original;
    }
    public function kpis(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::garantirEmpresa();

        // Buscar KPIs de todos os SOPs aprovados da empresa
        $kpisReais = Database::query(
            "SELECT k.*, s.titulo as sop_titulo, s.sop_codigo 
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.empresa_id = :empresa_id AND k.ativo = 1 AND s.status = 'ativo'
             ORDER BY k.zona_atual DESC, k.nome",
            ['empresa_id' => $empresaId]
        );

        // Se não há KPIs reais, mostrar empty state
        if (empty($kpisReais)) {
            $dados = ['kpis' => []];
        } else {
            $dados = ['kpis' => $this->formatarKpisParaView($kpisReais)];
        }

        require VIEW_PATH . '/sop/kpis.php';
    }

    /**
     * Registra valor de KPI via AJAX — F-06 Enhanced
     */
    public function registrarKpi(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $kpiId = (int) ($_POST['kpi_id'] ?? 0);
        $valorAtual = trim($_POST['valor'] ?? '');

        if (!$kpiId || empty($valorAtual)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI ID e valor são obrigatórios.']);
            exit;
        }

        // Buscar KPI
        $kpi = Database::queryOne("SELECT * FROM sop_kpis WHERE id = :id AND empresa_id = :empresa_id", [
            'id' => $kpiId,
            'empresa_id' => Auth::empresa()
        ]);

        if (!$kpi) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI não encontrado.']);
            exit;
        }

        // Determinar zona baseada no valor
        $zonaAtual = $this->determinarZonaKPI($valorAtual, $kpi);

        // Atualizar KPI
        $sucesso = Sop::atualizarKpi($kpiId, $valorAtual, $zonaAtual);

        if ($sucesso) {
            Logger::acao('KPI valor registrado', [
                'kpi_id' => $kpiId,
                'kpi_nome' => $kpi['nome'],
                'valor' => $valorAtual,
                'zona' => $zonaAtual
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Valor registrado!',
                'zona' => $zonaAtual
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar KPI.']);
        }
        exit;
    }

    /**
     * Determinar zona do KPI baseada no valor
     */
    private function determinarZonaKPI(string $valor, array $kpi): string
    {
        // Lógica simplificada - pode ser expandida para diferentes tipos de KPI
        $valorNumerico = (float) preg_replace('/[^\d.]/', '', $valor);
        
        // Para KPIs de percentual
        if (strpos($valor, '%') !== false) {
            if ($valorNumerico >= 95) return 'verde';
            if ($valorNumerico >= 80) return 'amarela';
            return 'vermelha';
        }
        
        // Para KPIs de tempo (assumindo que menor é melhor)
        if (strpos($valor, 'min') !== false || strpos($valor, 'h') !== false) {
            if ($valorNumerico <= 30) return 'verde';
            if ($valorNumerico <= 120) return 'amarela';
            return 'vermelha';
        }
        
        // Padrão: comparar com meta verde/amarela/vermelha (implementação básica)
        return 'amarela'; // Padrão para valores não classificados automaticamente
    }

    /**
     * Exportar SOP como PDF — F-06 Implementation
     */
    public function exportarPdf(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Gerar conteúdo HTML para PDF
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        $kpis = Sop::buscarKpis($sopId);
        
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        // HTML básico para PDF (sem CSS complexo)
        $html = $this->gerarHtmlParaPdf($sopFormatado);

        // Headers para download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="SOP-' . $sop['sop_codigo'] . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Gerar PDF simples (texto puro convertido)
        echo $this->gerarPdfSimples($html, $sopFormatado['titulo']);
        exit;
    }

    /**
     * Exportar todos os SOPs como ZIP — F-06 Implementation
     */
    public function exportarTodosZip(): void
    {
        Auth::proteger();

        $empresaId = Auth::garantirEmpresa();

        // Buscar todos os SOPs aprovados
        $sops = Database::query(
            "SELECT * FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo' ORDER BY sop_codigo",
            ['empresa_id' => $empresaId]
        );

        if (empty($sops)) {
            Flash::set('erro', 'Nenhum SOP aprovado encontrado para exportar.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Headers para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="SOPs-Manual-Operacional.zip"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        // Criar ZIP em memória (implementação simplificada)
        $this->gerarZipSOPs($sops);
        exit;
    }

    /**
     * Gerar HTML limpo para PDF
     */
    private function gerarHtmlParaPdf(array $sop): string
    {
        $html = '<html>
        <body style="font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4;">
            <h1 style="color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px;">' . 
            ($sop['sop_codigo'] ?? 'SOP') . ' - ' . ($sop['titulo'] ?? 'Procedimento') . '</h1>
        </body>
        </html>';
        return $html;
    }

    /**
     * Retorna setores ESPECÍFICOS por nicho da empresa
     */
    private function getSetoresEspecificosPorNicho(string $nicho): array
    {
        switch($nicho) {
            case 'construção':
                return [
                    'ORÇAMENTAÇÃO E ENGENHARIA DE CUSTOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Levantamento de quantitativos, BDI, cotação de insumos',
                        'funcoes_principais' => ['Levantamento quantitativo', 'Composição de BDI', 'Cotação de materiais'],
                        'sops_padrao' => [
                            'Levantamento de Quantitativos de Obra Detalhado',
                            'Composição de BDI e Preços Unitários',
                            'Cotação de Insumos e Materiais de Construção',
                            'Elaboração de Orçamento Executivo Detalhado',
                            'Análise de Viabilidade Técnica e Financeira',
                            'Gestão de Banco de Preços e Composições',
                            'Orçamentação de Mão de Obra Especializada',
                            'Análise de Produtividade e Rendimentos',
                            'Orçamentação de Equipamentos e Ferramentas',
                            'Gestão de Reajustes e Variações de Preço',
                            'Orçamentação de Projetos Complementares',
                            'Análise de Riscos em Orçamentação',
                            'Controle de Margem e Lucratividade',
                            'Orçamentação por Módulos e Etapas',
                            'Gestão de Cronograma Físico-Financeiro Preliminar'
                        ],
                        'kpis_essenciais' => ['Precisão orçamentária', 'Prazo de orçamento', 'Margem de lucro']
                    ],
                    'SUPRIMENTOS / COMPRAS DE OBRA' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Cotação, homologação de fornecedores, prazo de entrega',
                        'funcoes_principais' => ['Compras especializadas', 'Gestão de fornecedores', 'Logística de obra'],
                        'sops_padrao' => [
                            'Cotação e Análise Comparativa de Fornecedores',
                            'Homologação e Qualificação de Fornecedores de Obra',
                            'Negociação de Contratos de Fornecimento',
                            'Controle Rigoroso de Prazo de Entrega',
                            'Gestão Estratégica de Estoque de Obra',
                            'Recebimento e Inspeção de Materiais',
                            'Gestão de Relacionamento com Fornecedores',
                            'Controle de Qualidade de Insumos',
                            'Logística de Transporte para Canteiro',
                            'Gestão de Compras de Emergência',
                            'Auditoria de Fornecedores e Performance',
                            'Gestão de Contratos de Locação de Equipamentos',
                            'Compras Sustentáveis e Compliance Ambiental'
                        ],
                        'kpis_essenciais' => ['Prazo de entrega', 'Economia em compras', 'Qualidade fornecedores']
                    ],
                    'GESTÃO DE OBRAS / CAMPO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Diário de obra, cronograma físico-financeiro, medições',
                        'funcoes_principais' => ['Gestão executiva', 'Controle de cronograma', 'Medições e relatórios'],
                        'sops_padrao' => [
                            'Diário de Obra e Controle Diário',
                            'Cronograma Físico-Financeiro',
                            'Medições e Boletim de Obra',
                            'Controle de Mão de Obra',
                            'Gestão de Equipamentos de Obra'
                        ],
                        'kpis_essenciais' => ['Avanço físico (%)', 'Prazo de obra', 'Produtividade equipes']
                    ],
                    'SEGURANÇA DO TRABALHO (SESMT)' => [
                        'tipo' => 'especifico',
                        'descricao' => 'DDS, uso de EPI, laudos',
                        'funcoes_principais' => ['Segurança ocupacional', 'Treinamentos', 'Compliance trabalhista'],
                        'sops_padrao' => [
                            'DDS - Diálogo Diário de Segurança',
                            'Controle de EPI e Equipamentos',
                            'Elaboração de Laudos e ASO',
                            'Investigação de Acidentes',
                            'Treinamento de Segurança'
                        ],
                        'kpis_essenciais' => ['Taxa de acidentes', 'Compliance NR', 'Treinamentos realizados']
                    ]
                ];
                
            case 'saúde':
                return [
                    'AGENDAMENTO E RECEPÇÃO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Confirmação de consulta, no-show, encaixe',
                        'funcoes_principais' => ['Gestão de agenda', 'Recepção especializada', 'Controle de fluxo'],
                        'sops_padrao' => [
                            'Agendamento e Confirmação de Consultas',
                            'Controle de No-Show e Remarcações',
                            'Encaixe de Urgências',
                            'Gestão de Lista de Espera',
                            'Protocolo de Recepção'
                        ],
                        'kpis_essenciais' => ['Taxa no-show', 'Tempo de espera', 'Satisfação recepção']
                    ],
                    'PRONTUÁRIO E COMPLIANCE (LGPD)' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Sigilo, armazenamento de dados sensíveis',
                        'funcoes_principais' => ['Gestão de prontuários', 'Compliance LGPD', 'Segurança de dados'],
                        'sops_padrao' => [
                            'Gestão de Prontuário Eletrônico',
                            'Compliance LGPD em Saúde',
                            'Sigilo Médico e Confidencialidade',
                            'Backup de Dados Clínicos',
                            'Auditoria de Acesso a Dados'
                        ],
                        'kpis_essenciais' => ['Compliance LGPD', 'Incidentes de dados', 'Auditoria prontuários']
                    ],
                    'CORPO CLÍNICO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Escalas, protocolos clínicos, prontuário eletrônico',
                        'funcoes_principais' => ['Gestão médica', 'Protocolos clínicos', 'Escalas médicas'],
                        'sops_padrao' => [
                            'Escalas Médicas e Plantões',
                            'Protocolos Clínicos Padronizados',
                            'Interconsultas e Referências',
                            'Controle de Prescrições',
                            'Educação Médica Continuada'
                        ],
                        'kpis_essenciais' => ['Produtividade médica', 'Protocolos seguidos', 'Satisfação pacientes']
                    ]
                ];
                
            case 'ecommerce':
                return [
                    'GESTÃO DE MARKETPLACE' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Cadastro de produto, precificação por canal',
                        'funcoes_principais' => ['Gestão multicanal', 'Precificação dinâmica', 'Catálogo de produtos'],
                        'sops_padrao' => [
                            'Cadastro Completo de Produtos em Marketplace',
                            'Precificação Dinâmica por Canal de Venda',
                            'Gestão Integrada de Estoque Multicanal',
                            'Otimização de Anúncios e Campanhas Patrocinadas',
                            'Análise de Performance por Canal e Produto',
                            'Gestão de Categorização e Atributos',
                            'Controle de Concorrência e Monitoramento de Preços',
                            'Gestão de Promoções e Campanhas Especiais',
                            'Otimização de SEO para Marketplace',
                            'Gestão de Reviews e Feedback de Clientes',
                            'Controle de Buy Box e Posicionamento',
                            'Gestão de Frete e Políticas de Entrega',
                            'Análise de Métricas de Conversão por Canal',
                            'Gestão de Políticas de Troca e Devolução',
                            'Integração com ERPs e Sistemas de Gestão',
                            'Compliance com Regras de Marketplace'
                        ],
                        'kpis_essenciais' => ['Conversão por canal', 'Share of voice', 'Margem por marketplace']
                    ],
                    'LOGÍSTICA E FULFILLMENT' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Separação, embalagem, prazo de despacho',
                        'funcoes_principais' => ['Fulfillment', 'Gestão logística', 'Controle de prazos'],
                        'sops_padrao' => [
                            'Separação e Picking de Pedidos',
                            'Embalagem e Proteção de Produtos',
                            'Despacho e Prazo de Entrega',
                            'Gestão de Transportadoras',
                            'Rastreamento de Envios'
                        ],
                        'kpis_essenciais' => ['Tempo de despacho', 'Avarias (%)', 'Prazo de entrega']
                    ],
                    'PÓS-VENDA / TROCAS E DEVOLUÇÕES' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Reembolso, código de defesa do consumidor',
                        'funcoes_principais' => ['Pós-venda especializado', 'Gestão de devoluções', 'Compliance CDC'],
                        'sops_padrao' => [
                            'Processo de Trocas e Devoluções',
                            'Reembolso e Estorno',
                            'SAC E-commerce Especializado',
                            'Compliance Código Defesa Consumidor',
                            'Gestão de Avaliações Online'
                        ],
                        'kpis_essenciais' => ['Taxa de devolução', 'Tempo de reembolso', 'NPS e-commerce']
                    ]
                ];
                
            case 'educação':
                return [
                    'PEDAGÓGICO / CONTEÚDO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Criação e revisão de material, grade curricular',
                        'funcoes_principais' => ['Desenvolvimento pedagógico', 'Criação de conteúdo', 'Gestão curricular'],
                        'sops_padrao' => [
                            'Desenvolvimento de Conteúdo Pedagógico',
                            'Revisão e Atualização Curricular',
                            'Criação de Material Didático',
                            'Planejamento de Aulas',
                            'Avaliação Pedagógica'
                        ],
                        'kpis_essenciais' => ['Qualidade do conteúdo', 'Engajamento alunos', 'Aprovação pedagógica']
                    ],
                    'SECRETARIA ACADÊMICA' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Matrícula, histórico, emissão de certificados',
                        'funcoes_principais' => ['Gestão acadêmica', 'Documentação estudantil', 'Certificações'],
                        'sops_padrao' => [
                            'Processo de Matrícula e Inscrição',
                            'Gestão de Histórico Escolar',
                            'Emissão de Certificados',
                            'Controle de Frequência',
                            'Documentação Acadêmica'
                        ],
                        'kpis_essenciais' => ['Taxa de matrícula', 'Prazo documentação', 'Satisfação acadêmica']
                    ]
                ];
                
            case 'tecnologia':
                return [
                    'PRODUTO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Roadmap, priorização de backlog',
                        'funcoes_principais' => ['Gestão de produto', 'Roadmap estratégico', 'Backlog management'],
                        'sops_padrao' => [
                            'Gestão de Roadmap de Produto',
                            'Priorização de Backlog',
                            'Pesquisa e Validação com Usuários',
                            'Definição de Features',
                            'Análise de Métricas de Produto'
                        ],
                        'kpis_essenciais' => ['Feature adoption', 'User engagement', 'Product-market fit']
                    ],
                    'DESENVOLVIMENTO / ENGENHARIA' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Code review, deploy, versionamento',
                        'funcoes_principais' => ['Desenvolvimento de software', 'DevOps', 'Qualidade de código'],
                        'sops_padrao' => [
                            'Processo Rigoroso de Code Review',
                            'Pipeline Automatizado de Deploy e CI/CD',
                            'Gestão de Versionamento e Release Management',
                            'Gestão de Ambientes de Desenvolvimento e Produção',
                            'Documentação Técnica Completa de Sistemas',
                            'Testes Automatizados e Quality Assurance',
                            'Gestão de Configuração e Infrastructure as Code',
                            'Monitoramento e Observabilidade de Aplicações',
                            'Gestão de Segurança e Vulnerabilidades',
                            'Arquitetura de Software e Design Patterns',
                            'Gestão de Performance e Otimização',
                            'Backup e Recovery de Código e Dados',
                            'Gestão de Dependências e Bibliotecas',
                            'Processo de Debugging e Troubleshooting',
                            'Gestão de APIs e Integrações',
                            'Desenvolvimento Mobile e Multiplataforma',
                            'Gestão de Banco de Dados e Migrations'
                        ],
                        'kpis_essenciais' => ['Lead time', 'Bug rate', 'Deploy frequency']
                    ],
                    'CUSTOMER SUCCESS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Onboarding, health score, upsell',
                        'funcoes_principais' => ['Sucesso do cliente', 'Onboarding', 'Expansion revenue'],
                        'sops_padrao' => [
                            'Onboarding de Clientes SaaS',
                            'Monitoramento de Health Score',
                            'Processo de Upsell e Cross-sell',
                            'Gestão de Renovações',
                            'Análise de Churn'
                        ],
                        'kpis_essenciais' => ['Churn rate', 'NPS', 'Expansion revenue']
                    ]
                ];
                
            case 'alimentação':
                return [
                    'COZINHA / PRODUÇÃO DE ALIMENTOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Ficha técnica, boas práticas de manipulação',
                        'funcoes_principais' => ['Preparo de alimentos', 'Controle qualidade', 'Higiene alimentar'],
                        'sops_padrao' => [
                            'Fichas Técnicas e Receituário',
                            'Boas Práticas de Manipulação',
                            'Controle de Temperatura',
                            'Higienização e Sanitização',
                            'Controle de Validade'
                        ],
                        'kpis_essenciais' => ['Tempo de preparo', 'Qualidade alimentar', 'Aproveitamento ingredientes']
                    ],
                    'SALÃO / ATENDIMENTO NO LOCAL' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Tempo de espera, turno de mesa',
                        'funcoes_principais' => ['Atendimento presencial', 'Gestão de mesas', 'Experiência cliente'],
                        'sops_padrao' => [
                            'Recepção e Acomodação',
                            'Atendimento de Mesa',
                            'Gestão de Fila de Espera',
                            'Turno e Limpeza de Mesa',
                            'Cobrança e Fechamento'
                        ],
                        'kpis_essenciais' => ['Tempo de espera', 'Giro de mesa', 'Satisfação atendimento']
                    ],
                    'DELIVERY' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Tempo de entrega, embalagem térmica, parceria com apps',
                        'funcoes_principais' => ['Gestão delivery', 'Embalagem', 'Logística entrega'],
                        'sops_padrao' => [
                            'Gestão de Pedidos Online',
                            'Embalagem e Acondicionamento',
                            'Logística de Entrega',
                            'Gestão de Apps de Delivery',
                            'Controle de Prazo'
                        ],
                        'kpis_essenciais' => ['Tempo de entrega', 'Qualidade na chegada', 'Taxa de avarias']
                    ]
                ];
                
            case 'imobiliário':
                return [
                    'CAPTAÇÃO DE IMÓVEIS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Visita técnica, documentação do imóvel',
                        'funcoes_principais' => ['Captação imóveis', 'Avaliação técnica', 'Documentação'],
                        'sops_padrao' => [
                            'Prospecção e Captação de Imóveis',
                            'Visita Técnica e Avaliação',
                            'Análise Documental',
                            'Precificação e Posicionamento',
                            'Fotografia e Marketing'
                        ],
                        'kpis_essenciais' => ['Imóveis captados/mês', 'Tempo de venda', 'Margem por venda']
                    ],
                    'LOCAÇÃO / ADMINISTRAÇÃO DE IMÓVEIS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Vistoria de entrada/saída, repasse ao proprietário',
                        'funcoes_principais' => ['Gestão locatícia', 'Vistorias', 'Relacionamento proprietários'],
                        'sops_padrao' => [
                            'Vistoria de Entrada',
                            'Gestão de Contratos de Locação',
                            'Cobrança e Repasse',
                            'Vistoria de Saída',
                            'Manutenção Preventiva'
                        ],
                        'kpis_essenciais' => ['Inadimplência (%)', 'Vacância', 'Satisfação proprietários']
                    ]
                ];
                
            case 'advocacia':
                return [
                    'CONTENCIOSO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Prazos processuais, andamento de processo',
                        'funcoes_principais' => ['Gestão processual', 'Controle prazos', 'Acompanhamento judicial'],
                        'sops_padrao' => [
                            'Controle de Prazos Processuais',
                            'Acompanhamento de Andamentos',
                            'Elaboração de Petições',
                            'Gestão de Audiências',
                            'Relatórios Processuais'
                        ],
                        'kpis_essenciais' => ['Prazos em dia (%)', 'Taxa de êxito', 'Produtividade por advogado']
                    ],
                    'CONSULTIVO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Elaboração de pareceres, contratos',
                        'funcoes_principais' => ['Consultoria jurídica', 'Elaboração contratos', 'Pareceres'],
                        'sops_padrao' => [
                            'Elaboração de Pareceres Jurídicos',
                            'Análise e Revisão de Contratos',
                            'Consultoria Preventiva',
                            'Due Diligence',
                            'Compliance Jurídico'
                        ],
                        'kpis_essenciais' => ['Prazo de entrega', 'Qualidade pareceres', 'Faturamento consultivo']
                    ]
                ];
                
            case 'beleza':
                return [
                    'AGENDA E RECEPÇÃO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Confirmação, encaixe, política de atraso',
                        'funcoes_principais' => ['Gestão de agenda', 'Recepção especializada', 'Controle de fluxo'],
                        'sops_padrao' => [
                            'Agendamento e Confirmação',
                            'Recepção e Acolhimento',
                            'Gestão de Encaixes',
                            'Política de Atrasos',
                            'Follow-up de Clientes'
                        ],
                        'kpis_essenciais' => ['Taxa de no-show', 'Ocupação agenda', 'Satisfação recepção']
                    ],
                    'SALA DE PROCEDIMENTOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Protocolo de higienização, ficha de anamnese',
                        'funcoes_principais' => ['Execução procedimentos', 'Higienização', 'Segurança'],
                        'sops_padrao' => [
                            'Protocolo de Higienização',
                            'Ficha de Anamnese',
                            'Execução de Procedimentos',
                            'Controle de Materiais',
                            'Pós-procedimento'
                        ],
                        'kpis_essenciais' => ['Satisfação procedimentos', 'Tempo médio', 'Segurança (0 acidentes)']
                    ]
                ];
                
            case 'fitness':
                return [
                    'CONSULTORIA DE TREINO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Avaliação física, plano de treino',
                        'funcoes_principais' => ['Avaliação física', 'Prescrição treino', 'Acompanhamento'],
                        'sops_padrao' => [
                            'Avaliação Física Completa',
                            'Prescrição de Treino',
                            'Acompanhamento e Evolução',
                            'Reavaliação Periódica',
                            'Orientação Nutricional Básica'
                        ],
                        'kpis_essenciais' => ['Evolução clientes', 'Satisfação treino', 'Retenção personal']
                    ],
                    'RETENÇÃO DE ALUNOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Acompanhamento de frequência, reativação de inativos',
                        'funcoes_principais' => ['Controle frequência', 'Reativação', 'Engajamento'],
                        'sops_padrao' => [
                            'Controle de Frequência',
                            'Identificação de Inativos',
                            'Processo de Reativação',
                            'Programas de Engajamento',
                            'Feedback e Melhoria'
                        ],
                        'kpis_essenciais' => ['Taxa de retenção', 'Frequência média', 'Reativações/mês']
                    ]
                ];
                
            case 'turismo':
                return [
                    'RESERVAS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Confirmação, overbooking, cancelamento',
                        'funcoes_principais' => ['Gestão reservas', 'Revenue management', 'Atendimento'],
                        'sops_padrao' => [
                            'Processo de Reservas',
                            'Gestão de Overbooking',
                            'Política de Cancelamento',
                            'Check-in e Check-out',
                            'Revenue Management'
                        ],
                        'kpis_essenciais' => ['Taxa ocupação', 'RevPAR', 'Satisfação hospede']
                    ],
                    'GOVERNANÇA / HOUSEKEEPING' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Checklist de limpeza, manutenção de quartos',
                        'funcoes_principais' => ['Limpeza especializada', 'Manutenção', 'Controle qualidade'],
                        'sops_padrao' => [
                            'Checklist de Limpeza',
                            'Manutenção Preventiva',
                            'Controle de Amenities',
                            'Inspeção de Qualidade',
                            'Gestão de Enxoval'
                        ],
                        'kpis_essenciais' => ['Qualidade limpeza', 'Tempo por quarto', 'Satisfação housekeeping']
                    ]
                ];
                
            case 'indústria':
                return [
                    'PRODUÇÃO / FÁBRICA (PCP)' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Ordem de produção, controle de lote',
                        'funcoes_principais' => ['Planejamento produção', 'Controle processo', 'Gestão lotes'],
                        'sops_padrao' => [
                            'Planejamento de Produção',
                            'Controle de Lotes',
                            'Ordens de Produção',
                            'Sequenciamento',
                            'Controle de Capacidade'
                        ],
                        'kpis_essenciais' => ['OEE', 'Produtividade', 'Prazo de entrega']
                    ],
                    'QUALIDADE INDUSTRIAL' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Inspeção, não conformidade',
                        'funcoes_principais' => ['Controle qualidade', 'Inspeções', 'Melhorias'],
                        'sops_padrao' => [
                            'Inspeção de Qualidade',
                            'Controle de Não Conformidades',
                            'Calibração de Equipamentos',
                            'Auditoria de Processo',
                            'Melhoria Contínua'
                        ],
                        'kpis_essenciais' => ['Taxa de defeitos', 'First pass yield', 'Custo da qualidade']
                    ]
                ];
                
            case 'logística':
                return [
                    'FROTA' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Manutenção veicular, checklist de saída',
                        'funcoes_principais' => ['Gestão frota', 'Manutenção', 'Controle operacional'],
                        'sops_padrao' => [
                            'Manutenção Preventiva de Frota',
                            'Checklist de Saída',
                            'Controle de Combustível',
                            'Gestão de Motoristas',
                            'Monitoramento GPS'
                        ],
                        'kpis_essenciais' => ['Disponibilidade frota', 'Custo por km', 'Acidentes']
                    ],
                    'ROTEIRIZAÇÃO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Definição de rotas, otimização de entrega',
                        'funcoes_principais' => ['Planejamento rotas', 'Otimização', 'Monitoramento'],
                        'sops_padrao' => [
                            'Planejamento de Rotas',
                            'Otimização de Entregas',
                            'Sequenciamento de Paradas',
                            'Monitoramento em Tempo Real',
                            'Análise de Performance'
                        ],
                        'kpis_essenciais' => ['Km rodado', 'Entregas no prazo', 'Custo por entrega']
                    ]
                ];
                
            case 'consultoria':
                return [
                    'DELIVERY DE PROJETOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Escopo, cronograma, entregáveis',
                        'funcoes_principais' => ['Gestão projetos', 'Entregas', 'Cliente management'],
                        'sops_padrao' => [
                            'Definição de Escopo',
                            'Planejamento de Projeto',
                            'Gestão de Entregáveis',
                            'Controle de Cronograma',
                            'Encerramento de Projeto'
                        ],
                        'kpis_essenciais' => ['Prazo de entrega', 'Margem por projeto', 'Satisfação cliente']
                    ]
                ];
                
            case 'financeiro':
                return [
                    'ANÁLISE DE CRÉDITO / RISCO' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Score, aprovação, política de risco',
                        'funcoes_principais' => ['Análise crédito', 'Gestão risco', 'Aprovação'],
                        'sops_padrao' => [
                            'Análise de Crédito',
                            'Políticas de Risco',
                            'Processo de Aprovação',
                            'Monitoramento de Carteira',
                            'Provisões e Perdas'
                        ],
                        'kpis_essenciais' => ['Taxa inadimplência', 'Aprovação crédito', 'ROE']
                    ]
                ];
                
            case 'marketing':
                return [
                    'PLANEJAMENTO DE CONTAS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Briefing, aprovação de campanha',
                        'funcoes_principais' => ['Account planning', 'Briefing', 'Estratégia'],
                        'sops_padrao' => [
                            'Briefing e Planejamento',
                            'Desenvolvimento de Estratégia',
                            'Aprovação de Campanhas',
                            'Execução e Monitoramento',
                            'Relatórios de Performance'
                        ],
                        'kpis_essenciais' => ['ROI campanhas', 'Taxa aprovação', 'Satisfação cliente']
                    ]
                ];
                
            case 'automotivo':
                return [
                    'VENDAS DE VEÍCULOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Test-drive, avaliação de usado',
                        'funcoes_principais' => ['Vendas veículos', 'Test-drive', 'Avaliação'],
                        'sops_padrao' => [
                            'Atendimento e Qualificação',
                            'Test-drive e Demonstração',
                            'Avaliação de Usado',
                            'Negociação e Fechamento',
                            'Entrega do Veículo'
                        ],
                        'kpis_essenciais' => ['Vendas/mês', 'Margem por venda', 'Satisfação pós-venda']
                    ]
                ];
                
            case 'agronegócio':
                return [
                    'PRODUÇÃO AGRÍCOLA/PECUÁRIA' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Manejo, plantio, colheita',
                        'funcoes_principais' => ['Produção agrícola', 'Manejo', 'Controle sanitário'],
                        'sops_padrao' => [
                            'Planejamento de Safra',
                            'Manejo de Solo',
                            'Controle Fitossanitário',
                            'Operações de Campo',
                            'Colheita e Pós-colheita'
                        ],
                        'kpis_essenciais' => ['Produtividade/hectare', 'Custo produção', 'Qualidade produto']
                    ]
                ];
                
            case 'ong':
                return [
                    'CAPTAÇÃO DE RECURSOS' => [
                        'tipo' => 'especifico',
                        'descricao' => 'Prospecção de doadores, editais',
                        'funcoes_principais' => ['Fundraising', 'Relacionamento doadores', 'Projetos'],
                        'sops_padrao' => [
                            'Prospecção de Doadores',
                            'Elaboração de Projetos',
                            'Submissão de Editais',
                            'Prestação de Contas',
                            'Relacionamento com Financiadores'
                        ],
                        'kpis_essenciais' => ['Recursos captados', 'Taxa aprovação projetos', 'Retenção doadores']
                    ]
                ];
                
            default:
                return []; // Sem setores específicos para nichos não mapeados
        }
    }

    /**
     * Gerar HTML completo de um SOP
     */
    private function gerarHtmlSOP(array $sop): string
    {
        $html = '<html><head><title>SOP - ' . $sop['sop_codigo'] . '</title></head><body>';
        $html .= '<h1>' . $sop['sop_codigo'] . ' - ' . $sop['titulo'] . '</h1>';
        $html .= '<p><strong>Empresa:</strong> ' . $sop['empresa'] . '</p>';
        $html .= '<p><strong>Setor:</strong> ' . $sop['setor'] . '</p>';
        $html .= '<p><strong>Versão:</strong> ' . $sop['versao'] . '</p>';
        $html .= '<p><strong>Norma:</strong> ' . $sop['norma'] . '</p>';
        $html .= '<h2>Objetivo</h2>';
        $html .= '<p>' . $sop['objetivo'] . '</p>';
        $html .= '<h2>Escopo</h2>';
        $html .= '<p><strong>Aplica-se a:</strong> ' . $sop['escopo_aplica'] . '</p>';
        $html .= '<p><strong>Não se aplica a:</strong> ' . $sop['escopo_nao_aplica'] . '</p>';

        // Subtópicos
        if (isset($sop['subtopicos_completos'])) {
            foreach ($sop['subtopicos_completos'] as $subtopico) {
                $html .= '<h2>Subtópico ' . $subtopico['letra'] . ': ' . $subtopico['nome'] . '</h2>';
                $html .= '<p>' . $subtopico['descricao'] . '</p>';
                
                $html .= '<h3>Procedimentos:</h3><ol>';
                foreach ($subtopico['procedimentos'] as $proc) {
                    $html .= '<li>' . $proc['acao'] . ' <em>(' . $proc['responsavel'] . ' - ' . $proc['prazo'] . ')</em></li>';
                }
                $html .= '</ol>';
            }
        }

        $html .= '</body></html>';
        return $html;
    }

    /**
     * Gerar PDF simples (conversão de HTML)
     */
    private function gerarPdfSimples(string $html, string $titulo): string
    {
        // Implementação simplificada - retorna HTML como "PDF"
        // Em produção, usar biblioteca como TCPDF ou DOMPDF
        return $html;
    }

    /**
     * Gerar ZIP com múltiplos SOPs
     */
    private function gerarZipSOPs(array $sops): void
    {
        // Implementação simplificada - retorna lista de SOPs como texto
        // Em produção, usar ZipArchive do PHP
        echo "Manual Operacional - SOPs:\n\n";
        foreach ($sops as $sop) {
            echo "- {$sop['sop_codigo']}: {$sop['titulo']}\n";
        }
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function formatarKpisParaView(array $kpis): array
    {
        return array_map(function($kpi) {
            return [
                'kpi' => $kpi['nome'],
                'sop' => $kpi['sop_codigo'] ?? 'N/A',
                'atual' => $kpi['valor_atual'] ?? 'Não medido',
                'meta_verde' => $kpi['meta_verde'],
                'meta_amarela' => $kpi['meta_amarela'],
                'meta_vermelha' => $kpi['meta_vermelha'],
                'zona' => $kpi['zona_atual'] ?? 'verde',
                'frequencia' => 'Conforme SOP',
                'responsavel' => $kpi['responsavel'] ?? 'A definir',
            ];
        }, $kpis);
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function criarSopNoBanco(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId, array $conteudoIA): int|false
    {
        // Detectar se é nova estrutura padrão-ouro (15 seções)
        $novaEstrutura = isset($conteudoIA['cabecalho']);
        
        if ($novaEstrutura) {
            // Nova estrutura padrão-ouro
            $titulo = $conteudoIA['cabecalho']['nome'] ?? $sopNome;
            $versao = $conteudoIA['cabecalho']['versao'] ?? '1.0';
            $conteudoResumo = 'SOP gerado via IA com estrutura padrão-ouro (15 seções completas)';
        } else {
            // Estrutura antiga (compatibilidade)
            $titulo = $sopNome;
            $versao = '1.0';
            $conteudoResumo = 'SOP gerado via IA com 13 componentes completos';
        }
        
        return Sop::criar([
            'empresa_id'        => $empresaId,
            'diagnostico_id'    => $diagnosticoId,
            'sop_codigo'        => $sopCodigo,
            'titulo'            => $titulo,
            'departamento'      => $this->getDepartamentoPorId($sopCodigo),
            'conteudo'          => $conteudoResumo,
            'conteudo_completo' => $conteudoIA,
            'versao'            => $versao,
            'status'            => 'rascunho',
            'gerado_por_ia'     => 1,
        ]);
    }

    /**
     * Cria SOP básico quando IA falha (com nova estrutura padrão-ouro)
     */
    private function criarSopBasico(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId): int|false
    {
        // Criar estrutura básica padrão-ouro quando IA falha
        $basicData = [
            'cabecalho' => [
                'codigo' => $sopCodigo,
                'nome' => $sopNome,
                'versao' => '1.0',
                'data_criacao' => date('Y-m-d'),
                'data_revisao' => date('Y-m-d', strtotime('+1 year')),
                'dono_processo' => 'A definir',
                'aprovador' => 'A definir'
            ],
            'objetivo' => 'Definir procedimento específico para ' . $sopNome,
            'escopo' => [
                'cobre' => 'A ser detalhado conforme necessidades da empresa',
                'nao_cobre' => 'Outros processos não relacionados a este procedimento'
            ],
            'glossario' => [
                ['termo' => 'Procedimento', 'definicao' => 'Sequência de passos para executar uma tarefa']
            ],
            'raci' => [
                'responsavel' => 'A definir',
                'aprovador' => 'A definir',
                'consultado' => 'A definir',
                'informado' => 'A definir'
            ],
            'pre_requisitos' => [
                'Definir acessos necessários',
                'Identificar informações obrigatórias',
                'Configurar ferramentas necessárias'
            ],
            'passo_a_passo' => [
                [
                    'passo' => 1,
                    'acao' => 'Definir passos específicos do procedimento',
                    'sistema' => 'A definir',
                    'responsavel' => 'A definir',
                    'tempo_estimado' => 'A definir',
                    'criterio_conclusao' => 'A definir critério específico'
                ]
            ],
            'pontos_controle' => [
                [
                    'checkpoint' => 'Verificação inicial',
                    'criterio_aceite' => 'A definir critério específico',
                    'acao_se_falhar' => 'A definir ação corretiva'
                ]
            ],
            'tratamento_excecoes' => [
                [
                    'cenario' => 'Erro genérico no processo',
                    'solucao' => 'A definir solução específica',
                    'escalar_para' => 'Supervisor'
                ]
            ],
            'ferramentas_sistemas' => [
                'Definir sistemas específicos utilizados'
            ],
            'kpis_processo' => [
                'tempo_medio_esperado' => 'A definir',
                'taxa_erro_aceitavel' => 'A definir',
                'sla_interno' => 'A definir',
                'meta_qualidade' => 'A definir'
            ],
            'riscos_nao_conformidades' => [
                [
                    'risco' => 'Não execução do procedimento',
                    'impacto' => 'A definir impacto no negócio',
                    'prevencao' => 'A definir medidas preventivas'
                ]
            ],
            'melhorias_recomendadas' => [
                'Detalhar procedimento específico baseado na realidade da empresa',
                'Definir métricas e controles adequados',
                'Estabelecer ferramentas e responsáveis específicos'
            ],
            'anexos' => [
                'checklist_rapido' => [
                    '☐ Verificar pré-requisitos',
                    '☐ Executar procedimento',
                    '☐ Validar resultado'
                ],
                'fluxograma_textual' => [
                    '1. Início → Verificar pré-requisitos',
                    '2. Executar → Seguir procedimento',
                    '3. Finalizar → Documentar resultado'
                ]
            ],
            'historico_revisoes' => [
                [
                    'versao' => '1.0',
                    'data' => date('Y-m-d'),
                    'mudanca' => 'Criação inicial (IA indisponível - estrutura básica)',
                    'responsavel' => 'Sistema O Consultor'
                ]
            ]
        ];
        
        return $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, $diagnosticoId, $basicData);
    }

    /**
     * Formata SOP do banco para a view (compatível com nova estrutura de 15 seções)
     */
    private function formatarSopParaView(array $sop, array $conteudo): array
    {
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        
        // Compatibilidade com ambas as estruturas (antiga e nova padrão-ouro)
        $novaEstrutura = isset($conteudo['cabecalho']);
        
        if ($novaEstrutura) {
            // Nova estrutura padrão-ouro (15 seções)
            return [
                'id' => $sop['sop_codigo'] ?? $sop['id'],
                'nome' => $conteudo['cabecalho']['nome'] ?? $sop['titulo'],
                'versao' => $conteudo['cabecalho']['versao'] ?? $sop['versao'],
                'empresa' => $empresa['nome'] ?? 'Empresa',
                'setor' => $empresa['segmento'] ?? 'Tecnologia',
                'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
                
                // Mapeamento da nova estrutura para view existente
                'objetivo' => $conteudo['objetivo'] ?? '',
                'escopo_aplica' => $conteudo['escopo']['cobre'] ?? '',
                'escopo_nao_aplica' => $conteudo['escopo']['nao_cobre'] ?? '',
                'glossario' => $conteudo['glossario'] ?? [],
                'raci' => $conteudo['raci'] ?? [],
                'subtopicos' => $this->extrairSubtopicosDaNovaEstrutura($conteudo),
                'responsaveis' => $this->extrairResponsaveisDaNovaEstrutura($conteudo),
                'prerequisitos' => $conteudo['pre_requisitos'] ?? [],
                'ferramentas' => $conteudo['ferramentas_sistemas'] ?? [],
                'procedimento_subtopico_1' => $conteudo['passo_a_passo'] ?? [],
                'pontos_controle' => $conteudo['pontos_controle'] ?? [],
                'tratamento_excecoes' => $conteudo['tratamento_excecoes'] ?? [],
                'checklist' => $conteudo['anexos']['checklist_rapido'] ?? [],
                'fluxograma_textual' => $conteudo['anexos']['fluxograma_textual'] ?? [],
                'evidencias' => $this->extrairEvidenciasDaNovaEstrutura($conteudo),
                'relatorios' => $this->extrairRelatoriosDaNovaEstrutura($conteudo),
                'kpis' => $this->extrairKpisDaNovaEstrutura($conteudo),
                'riscos' => $conteudo['riscos_nao_conformidades'] ?? [],
                'melhorias_recomendadas' => $conteudo['melhorias_recomendadas'] ?? [],
                'contencao_n1' => $this->extrairContencaoN1($conteudo),
                'contencao_n2' => $this->extrairContencaoN2($conteudo),
                'contencao_n3' => $this->extrairContencaoN3($conteudo),
                'historico_revisoes' => $conteudo['historico_revisoes'] ?? [],
                
                // Campos específicos da nova estrutura
                'cabecalho' => $conteudo['cabecalho'] ?? [],
                'nova_estrutura' => true
            ];
        } else {
            // Estrutura antiga (compatibilidade)
            return [
                'id' => $sop['sop_codigo'] ?? $sop['id'],
                'nome' => $sop['titulo'],
                'versao' => $sop['versao'],
                'empresa' => $empresa['nome'] ?? 'Empresa',
                'setor' => $empresa['segmento'] ?? 'Tecnologia',
                'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
                'objetivo' => $conteudo['objetivo'] ?? '',
                'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
                'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
                'subtopicos' => $conteudo['subtopicos'] ?? [],
                'responsaveis' => $conteudo['responsaveis'] ?? [],
                'prerequisitos' => $conteudo['prerequisitos'] ?? [],
                'ferramentas' => $conteudo['ferramentas'] ?? [],
                'procedimento_subtopico_1' => $conteudo['procedimentos'][0]['passos'] ?? [],
                'checklist' => $conteudo['checklist'] ?? [],
                'evidencias' => $conteudo['evidencias'] ?? [],
                'relatorios' => $conteudo['relatorios'] ?? [],
                'kpis' => $conteudo['kpis'] ?? [],
                'contencao_n1' => $conteudo['contencao']['n1'] ?? [],
                'contencao_n2' => $conteudo['contencao']['n2'] ?? [],
                'contencao_n3' => $conteudo['contencao']['n3'] ?? [],
                'nova_estrutura' => false
            ];
        }
    }
    
    /**
     * Extrai subtópicos da nova estrutura para compatibilidade com view
     */
    private function extrairSubtopicosDaNovaEstrutura(array $conteudo): array
    {
        // Na nova estrutura, os subtópicos estão implícitos no passo_a_passo
        // Vamos criar subtópicos baseados na estrutura de passos
        if (empty($conteudo['passo_a_passo'])) {
            return [['nome' => 'Procedimento Principal', 'descricao' => 'Executar conforme passo a passo']];
        }
        
        // Agrupar passos em subtópicos lógicos (por exemplo, a cada 5-7 passos)
        $totalPassos = count($conteudo['passo_a_passo']);
        $subtopicos = [];
        
        if ($totalPassos <= 7) {
            $subtopicos[] = ['nome' => 'Procedimento Completo', 'descricao' => 'Executar todos os passos sequencialmente'];
        } else {
            $metade = ceil($totalPassos / 2);
            $subtopicos[] = ['nome' => 'Primeira Fase', 'descricao' => "Passos 1 a {$metade}"];
            $subtopicos[] = ['nome' => 'Segunda Fase', 'descricao' => "Passos " . ($metade + 1) . " a {$totalPassos}"];
        }
        
        return $subtopicos;
    }
    
    /**
     * Extrai responsáveis da nova estrutura RACI
     */
    private function extrairResponsaveisDaNovaEstrutura(array $conteudo): array
    {
        if (empty($conteudo['raci'])) {
            return [['papel' => 'Executor', 'cargo' => 'A definir']];
        }
        
        $responsaveis = [];
        $raci = $conteudo['raci'];
        
        if (!empty($raci['responsavel'])) {
            $responsaveis[] = ['papel' => 'Responsável (R)', 'cargo' => $raci['responsavel']];
        }
        if (!empty($raci['aprovador'])) {
            $responsaveis[] = ['papel' => 'Aprovador (A)', 'cargo' => $raci['aprovador']];
        }
        if (!empty($raci['consultado'])) {
            $responsaveis[] = ['papel' => 'Consultado (C)', 'cargo' => $raci['consultado']];
        }
        if (!empty($raci['informado'])) {
            $responsaveis[] = ['papel' => 'Informado (I)', 'cargo' => $raci['informado']];
        }
        
        return !empty($responsaveis) ? $responsaveis : [['papel' => 'Executor', 'cargo' => 'A definir']];
    }
    
    /**
     * Converte evidências implícitas da nova estrutura
     */
    private function extrairEvidenciasDaNovaEstrutura(array $conteudo): array
    {
        $evidencias = [];
        
        // Extrair evidências dos checkpoints
        if (!empty($conteudo['pontos_controle'])) {
            foreach ($conteudo['pontos_controle'] as $checkpoint) {
                $evidencias[] = "Evidência de " . $checkpoint['checkpoint'];
            }
        }
        
        // Adicionar evidências padrão
        $evidencias[] = "Registro de execução dos passos";
        $evidencias[] = "Validação dos critérios de conclusão";
        $evidencias[] = "Documentação de exceções tratadas";
        
        return $evidencias;
    }
    
    /**
     * Converte relatórios implícitos da nova estrutura
     */
    private function extrairRelatoriosDaNovaEstrutura(array $conteudo): array
    {
        $relatorios = [];
        
        // Baseado nos KPIs definidos
        if (!empty($conteudo['kpis_processo'])) {
            $kpis = $conteudo['kpis_processo'];
            if (!empty($kpis['tempo_medio_esperado'])) {
                $relatorios[] = ['oque' => 'Tempo de execução', 'para_quem' => 'Gestor do processo', 'frequencia' => 'Semanal', 'canal' => 'E-mail'];
            }
            if (!empty($kpis['taxa_erro_aceitavel'])) {
                $relatorios[] = ['oque' => 'Taxa de erros', 'para_quem' => 'Coordenação', 'frequencia' => 'Mensal', 'canal' => 'Dashboard'];
            }
        }
        
        // Relatórios padrão
        if (empty($relatorios)) {
            $relatorios[] = ['oque' => 'Status de execução', 'para_quem' => 'Responsável do processo', 'frequencia' => 'Conforme execução', 'canal' => 'Sistema'];
        }
        
        return $relatorios;
    }
    
    /**
     * Converte KPIs da nova estrutura
     */
    private function extrairKpisDaNovaEstrutura(array $conteudo): array
    {
        if (empty($conteudo['kpis_processo'])) {
            return [['kpi' => 'Taxa de sucesso', 'verde' => '95%', 'amarela' => '80%', 'vermelha' => '<80%', 'acao_vermelha' => 'Revisar processo']];
        }
        
        $kpisProcesso = $conteudo['kpis_processo'];
        $kpis = [];
        
        if (!empty($kpisProcesso['tempo_medio_esperado'])) {
            $tempo = $kpisProcesso['tempo_medio_esperado'];
            $kpis[] = [
                'kpi' => 'Tempo de execução',
                'verde' => "≤ {$tempo}",
                'amarela' => "≤ " . $this->calcularAumento($tempo, 20),
                'vermelha' => "> " . $this->calcularAumento($tempo, 50),
                'acao_vermelha' => 'Analisar gargalos e otimizar processo'
            ];
        }
        
        if (!empty($kpisProcesso['taxa_erro_aceitavel'])) {
            $taxa = $kpisProcesso['taxa_erro_aceitavel'];
            $kpis[] = [
                'kpi' => 'Taxa de erro',
                'verde' => "≤ {$taxa}",
                'amarela' => "≤ " . $this->calcularAumento($taxa, 50),
                'vermelha' => "> " . $this->calcularAumento($taxa, 100),
                'acao_vermelha' => 'Revisão urgente do procedimento'
            ];
        }
        
        // KPI padrão se não houver específicos
        if (empty($kpis)) {
            $kpis[] = [
                'kpi' => 'Conformidade do processo',
                'verde' => '≥95%',
                'amarela' => '80-94%',
                'vermelha' => '<80%',
                'acao_vermelha' => 'Retreinamento da equipe'
            ];
        }
        
        return $kpis;
    }
    
    /**
     * Calcula aumento percentual para KPIs
     */
    private function calcularAumento(string $valor, int $percentual): string
    {
        // Extrair número do valor (ex: "30 minutos" -> 30)
        preg_match('/(\d+(?:\.\d+)?)/', $valor, $matches);
        if (!empty($matches[1])) {
            $numero = floatval($matches[1]);
            $novoNumero = $numero * (1 + $percentual / 100);
            return str_replace($matches[1], number_format($novoNumero, 0), $valor);
        }
        return $valor;
    }
    
    /**
     * Extrai informações de contenção N1 (compatibilidade)
     */
    private function extrairContencaoN1(array $conteudo): array
    {
        // Na nova estrutura não há contenção separada, mas podemos inferir das exceções
        if (!empty($conteudo['tratamento_excecoes'])) {
            $primeiraExcecao = $conteudo['tratamento_excecoes'][0];
            return [
                'situacao' => $primeiraExcecao['cenario'] ?? 'Erro operacional',
                'acao' => $primeiraExcecao['solucao'] ?? 'Seguir procedimento de correção',
                'quem' => $primeiraExcecao['escalar_para'] ?? 'Responsável direto',
                'escalar' => 'Supervisor imediato'
            ];
        }
        
        return [
            'situacao' => 'Erro operacional padrão',
            'acao' => 'Verificar procedimento e corrigir',
            'quem' => 'Executor do processo',
            'escalar' => 'Supervisor direto'
        ];
    }
    
    /**
     * Extrai informações de contenção N2 (compatibilidade)
     */
    private function extrairContencaoN2(array $conteudo): array
    {
        return [
            'situacao' => 'Falha recorrente ou crítica',
            'acao' => 'Revisar processo completo',
            'quem' => 'Gestor do departamento',
            'escalar' => 'Gerência executiva'
        ];
    }
    
    /**
     * Extrai informações de contenção N3 (compatibilidade)
     */
    private function extrairContencaoN3(array $conteudo): array
    {
        return [
            'situacao' => 'Falha sistêmica com impacto no cliente',
            'acao' => 'Interromper processo e ativar plano de contingência',
            'quem' => 'Diretor responsável',
            'escalar' => 'CEO/Presidência',
            'comunicacao' => 'Comunicação imediata aos stakeholders',
            'documentacao' => 'Relatório detalhado de causa raiz obrigatório'
        ];
    }

    /**
     * Extrai dados do diagnóstico para o prompt
     */
    private function extrairColaboradores(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return '10-25';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        $internos = (int) ($respostas['colaboradores_internos'] ?? 10);
        $externos = (int) ($respostas['colaboradores_externos'] ?? 5);
        $total = $internos + $externos;
        
        if ($total <= 10) return '1-10';
        if ($total <= 25) return '11-25';  
        if ($total <= 50) return '26-50';
        if ($total <= 100) return '51-100';
        return '100+';
    }
    
    private function extrairFaturamento(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'R$ 100-500 mil';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['faturamento_mensal']) ? $respostas['faturamento_mensal'] : 'R$ 100-500 mil';
    }

    private function extrairDepartamentos(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Comercial, TI, Operações, Financeiro, RH';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        if (!$respostas) {
            return 'Comercial, TI, Operações, Financeiro, RH';
        }
        
        // CORREÇÃO: Extrair departamentos de TODAS as perguntas do diagnóstico
        $departamentosEncontrados = [];
        
        // Buscar em diferentes campos do diagnóstico que podem conter departamentos
        $camposParaBuscar = [
            'departamentos', 'setores', 'areas_empresa', 'estrutura_empresa',
            'departamentos_empresa', 'areas_atuacao', 'estrutura_organizacional'
        ];
        
        foreach ($camposParaBuscar as $campo) {
            if (isset($respostas[$campo])) {
                if (is_array($respostas[$campo])) {
                    $departamentosEncontrados = array_merge($departamentosEncontrados, $respostas[$campo]);
                } else {
                    // Se for string, dividir por vírgulas ou quebras de linha
                    $depsTexto = str_replace([';', '\n', '\r'], ',', $respostas[$campo]);
                    $depsArray = array_map('trim', explode(',', $depsTexto));
                    $departamentosEncontrados = array_merge($departamentosEncontrados, $depsArray);
                }
            }
        }
        
        // Buscar também em perguntas que podem conter informação de departamentos
        $perguntasRelevantes = [
            'empresa_colaboradores', 'estrutura_atual', 'principais_areas',
            'equipe_atual', 'areas_responsabilidade', 'organograma'
        ];
        
        foreach ($perguntasRelevantes as $pergunta) {
            if (isset($respostas[$pergunta]) && is_string($respostas[$pergunta])) {
                $texto = strtolower($respostas[$pergunta]);
                
                // Detectar departamentos mencionados no texto
                $padroesDepto = [
                    'comercial' => 'Comercial',
                    'vendas' => 'Comercial',
                    'ti' => 'TI',
                    'tecnologia' => 'TI',
                    'operac' => 'Operações',
                    'produc' => 'Operações',
                    'financeiro' => 'Financeiro',
                    'contab' => 'Financeiro',
                    'rh' => 'RH',
                    'recursos humanos' => 'RH',
                    'pessoas' => 'RH',
                    'marketing' => 'Marketing',
                    'juridico' => 'Jurídico',
                    'legal' => 'Jurídico',
                    'administrativo' => 'Administrativo',
                    'compras' => 'Compras',
                    'logistica' => 'Logística',
                    'qualidade' => 'Qualidade'
                ];
                
                foreach ($padroesDepto as $padrao => $departamento) {
                    if (strpos($texto, $padrao) !== false) {
                        $departamentosEncontrados[] = $departamento;
                    }
                }
            }
        }
        
        // Limpar e padronizar departamentos
        $departamentosEncontrados = array_filter($departamentosEncontrados);
        $departamentosEncontrados = array_unique($departamentosEncontrados);
        $departamentosEncontrados = array_map('trim', $departamentosEncontrados);
        $departamentosEncontrados = array_filter($departamentosEncontrados, function($dept) {
            return !empty($dept) && strlen($dept) > 1;
        });
        
        // Se não encontrou nenhum departamento específico, usar padrão baseado no segmento da empresa
        if (empty($departamentosEncontrados)) {
            $segmentoEmpresa = isset($respostas['segmento']) ? $respostas['segmento'] : 'Tecnologia';
            
            switch(strtolower($segmentoEmpresa)) {
                case 'tecnologia':
                    return 'Comercial, TI, Operações, Financeiro, RH';
                case 'saude':
                case 'saúde':
                    return 'Atendimento, Clínico, Administrativo, Financeiro, RH';
                case 'educacao':
                case 'educação':
                    return 'Pedagógico, Administrativo, Financeiro, Coordenação, RH';
                case 'varejo':
                    return 'Comercial, Operações, Estoque, Financeiro, RH';
                case 'industria':
                case 'indústria':
                    return 'Produção, Comercial, Qualidade, Financeiro, RH, Compras';
                case 'servicos':
                case 'serviços':
                    return 'Comercial, Operações, Atendimento, Financeiro, RH';
                default:
                    return 'Comercial, Operações, Administrativo, Financeiro, RH';
            }
        }
        
        $departamentosFinais = implode(', ', $departamentosEncontrados);
        
        Logger::info('Departamentos extraídos do diagnóstico', [
            'departamentos_encontrados' => $departamentosEncontrados,
            'total_departamentos' => count($departamentosEncontrados),
            'string_final' => $departamentosFinais
        ]);
        
        return $departamentosFinais;
    }

    private function extrairFerramentas(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'E-mail, WhatsApp, Excel';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['ferramentas']) ? $respostas['ferramentas'] : 'E-mail, WhatsApp, Excel';
    }

    private function extrairProblemas(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Processos não documentados, falta de padronização';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['pontos_melhoria']) ? $respostas['pontos_melhoria'] : 'Processos não documentados, falta de padronização';
    }
    
    private function extrairObjetivos(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Crescer com processos organizados e estruturados';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['objetivo_12_meses']) ? $respostas['objetivo_12_meses'] : 'Crescer com processos organizados e estruturados';
    }

    /**
     * Retorna departamentos com SOPs baseados no setor da empresa
     */
    private function getDepartamentosPorSetor(string $setor, int $empresaId): array
    {
        Logger::info('Carregando departamentos por setor', ['setor' => $setor, 'empresa_id' => $empresaId]);
        
        // Buscar SOPs existentes no banco (ambas arquiteturas)
        $sopsExistentes = $this->buscarSopsCompletos($empresaId);
        $sopsMap = [];
        foreach ($sopsExistentes as $sop) {
                $status = 'nao_gerado';
                switch($sop['status']) {
                    case 'ativo':
                        $status = 'aprovado';
                        break;
                    case 'rascunho':
                        $status = 'gerado';
                        break;
                    default:
                        $status = 'nao_gerado';
                        break;
                }
                
            $sopsMap[$sop['sop_codigo']] = [
                'id' => $sop['sop_codigo'],
                'nome' => $sop['titulo'],
                'status' => $status
            ];
        }

        // Templates por setor específico
        $templatesSOP = $this->getSOPsPorSetor($setor);
        Logger::info('Templates SOP carregados', ['setor' => $setor, 'templates' => count($templatesSOP)]);

        // Buscar SOPs customizados da empresa
        $sopsCustomizados = [];
        try {
            $sopsCustomizados = Database::query(
                "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY departamento, nome",
                ['empresa_id' => $empresaId]
            );
        } catch (Exception $e) {
            // Tabela pode não existir ainda - continuar sem SOPs customizados
            Logger::warning('Tabela sops_customizados não encontrada', ['erro' => $e->getMessage()]);
        }

        // Aplicar status aos SOPs padrão do setor
        foreach ($templatesSOP as &$dept) {
            foreach ($dept['sops'] as &$sop) {
                if (isset($sopsMap[$sop['id']])) {
                    $sop['status'] = $sopsMap[$sop['id']]['status'];
                } else {
                    $sop['status'] = 'nao_gerado';
                }
            }
        }
        
        Logger::info('Departamentos processados', ['departamentos' => count($templatesSOP), 'dados' => $templatesSOP]);

        // Integrar SOPs customizados nos departamentos
        foreach ($sopsCustomizados as $sopCustomizado) {
            $departamento = $sopCustomizado['departamento'];
            $icone = $sopCustomizado['icone'] ?? '📋';
            
            // Procurar se departamento já existe
            $deptIndex = null;
            foreach ($templatesSOP as $index => $dept) {
                if (strtolower($dept['nome']) === strtolower($departamento)) {
                    $deptIndex = $index;
                    break;
                }
            }
            
            // Se departamento não existe, criar novo
            if ($deptIndex === null) {
                $templatesSOP[] = [
                    'nome' => $departamento,
                    'icone' => $this->converterIconeParaEmoji($sopCustomizado['icone'] ?? 'documento'),
                    'sops' => []
                ];
                $deptIndex = count($templatesSOP) - 1;
            }
            
            // Adicionar SOP customizado ao departamento
            $status = isset($sopsMap[$sopCustomizado['sop_codigo']]) 
                ? $sopsMap[$sopCustomizado['sop_codigo']]['status'] 
                : 'nao_gerado';
            
            $templatesSOP[$deptIndex]['sops'][] = [
                'id' => $sopCustomizado['sop_codigo'],
                'nome' => $sopCustomizado['nome'],
                'status' => $status,
                'customizado' => true,
                'descricao' => $sopCustomizado['descricao']
            ];
        }

        return $templatesSOP;
    }

    /**
     * Retorna SOPs específicos por setor da empresa
     */
    private function getSOPsPorSetor(string $setor): array
    {
        switch (strtolower($setor)) {
            case 'construção civil':
            case 'construcao civil':
                return $this->getSOPsConstrutoraCivil();
                
            case 'tecnologia':
            case 'ti':
            case 'software':
                return $this->getSOPsTecnologia();
                
            case 'saúde':
            case 'saude':
            case 'medicina':
                return $this->getSOPsSaude();
                
            case 'educação':
            case 'educacao':
            case 'ensino':
                return $this->getSOPsEducacao();
                
            case 'consultoria':
                return $this->getSOPsConsultoria();
                
            case 'varejo':
            case 'comercio':
                return $this->getSOPsVarejo();
                
            case 'industria':
            case 'industrial':
                return $this->getSOPsIndustrial();
                
            case 'alimenticio':
            case 'restaurante':
            case 'food':
                return $this->getSOPsAlimenticio();
                
            default:
                return $this->getSOPsGeral();
        }
    }

    /**
     * SOPs específicos para Construção Civil
     */
    private function getSOPsConstrutoraCivil(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CC-COM-001', 'nome' => 'Prospecção e visita técnica'],
                    ['id' => 'SOP-CC-COM-002', 'nome' => 'Orçamento e memorial descritivo'],
                    ['id' => 'SOP-CC-COM-003', 'nome' => 'Negociação e contrato de obra'],
                    ['id' => 'SOP-CC-COM-004', 'nome' => 'Aprovação de projetos e licenças'],
                ],
            ],
            [
                'nome' => 'Planejamento de Obra',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-CC-PLAN-001', 'nome' => 'Cronograma executivo de obra'],
                    ['id' => 'SOP-CC-PLAN-002', 'nome' => 'Compra e logística de materiais'],
                    ['id' => 'SOP-CC-PLAN-003', 'nome' => 'Contratação de mão de obra'],
                    ['id' => 'SOP-CC-PLAN-004', 'nome' => 'Gestão de subcontratados'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '🚧',
                'sops' => [
                    ['id' => 'SOP-CC-EXEC-001', 'nome' => 'Controle de qualidade e fiscalização'],
                    ['id' => 'SOP-CC-EXEC-002', 'nome' => 'Segurança do trabalho na obra'],
                    ['id' => 'SOP-CC-EXEC-003', 'nome' => 'Medições e controle de avanço'],
                    ['id' => 'SOP-CC-EXEC-004', 'nome' => 'Gestão de mudanças e aditivos'],
                    ['id' => 'SOP-CC-EXEC-005', 'nome' => 'Controle de custos e orçamento'],
                ],
            ],
            [
                'nome' => 'Financeiro',
                'icone' => '💰',
                'sops' => [
                    ['id' => 'SOP-CC-FIN-001', 'nome' => 'Faturamento e medições'],
                    ['id' => 'SOP-CC-FIN-002', 'nome' => 'Controle de fluxo de caixa da obra'],
                    ['id' => 'SOP-CC-FIN-003', 'nome' => 'Gestão de garantias e retenções'],
                ],
            ],
            [
                'nome' => 'Entrega',
                'icone' => '🏠',
                'sops' => [
                    ['id' => 'SOP-CC-ENT-001', 'nome' => 'Vistoria e entrega da obra'],
                    ['id' => 'SOP-CC-ENT-002', 'nome' => 'Documentação técnica e As Built'],
                    ['id' => 'SOP-CC-ENT-003', 'nome' => 'Pós-entrega e assistência técnica'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Tecnologia
     */
    private function getSOPsTecnologia(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação'],
                    ['id' => 'SOP-TI-COM-002', 'nome' => 'Proposta técnica e comercial'],
                    ['id' => 'SOP-TI-COM-003', 'nome' => 'Negociação e fechamento'],
                ],
            ],
            [
                'nome' => 'Desenvolvimento',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-TI-DEV-001', 'nome' => 'Gestão de projetos ágeis'],
                    ['id' => 'SOP-TI-DEV-002', 'nome' => 'Code review e qualidade'],
                    ['id' => 'SOP-TI-DEV-003', 'nome' => 'Deploy e CI/CD'],
                    ['id' => 'SOP-TI-DEV-004', 'nome' => 'Gestão de bugs e suporte'],
                ],
            ],
            [
                'nome' => 'Infraestrutura',
                'icone' => '🔧',
                'sops' => [
                    ['id' => 'SOP-TI-INF-001', 'nome' => 'Monitoramento de serviços'],
                    ['id' => 'SOP-TI-INF-002', 'nome' => 'Backup e disaster recovery'],
                    ['id' => 'SOP-TI-INF-003', 'nome' => 'Gestão de acessos e segurança'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Saúde
     */
    private function getSOPsSaude(): array
    {
        return [
            [
                'nome' => 'Atendimento',
                'icone' => '🏥',
                'sops' => [
                    ['id' => 'SOP-SA-ATD-001', 'nome' => 'Agendamento e triagem'],
                    ['id' => 'SOP-SA-ATD-002', 'nome' => 'Consulta e anamnese'],
                    ['id' => 'SOP-SA-ATD-003', 'nome' => 'Prescrição e orientações'],
                ],
            ],
            [
                'nome' => 'Procedimentos',
                'icone' => '⚕️',
                'sops' => [
                    ['id' => 'SOP-SA-PROC-001', 'nome' => 'Esterilização e biossegurança'],
                    ['id' => 'SOP-SA-PROC-002', 'nome' => 'Controle de infecção hospitalar'],
                    ['id' => 'SOP-SA-PROC-003', 'nome' => 'Gestão de materiais médicos'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-SA-ADM-001', 'nome' => 'Prontuário eletrônico e LGPD'],
                    ['id' => 'SOP-SA-ADM-002', 'nome' => 'Faturamento e convênios'],
                ],
            ],
        ];
    }

    /**
     * SOPs gerais para setores não mapeados
     */
    private function getSOPsGeral(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-GER-COM-001', 'nome' => 'Prospecção de clientes'],
                    ['id' => 'SOP-GER-COM-002', 'nome' => 'Proposta comercial'],
                    ['id' => 'SOP-GER-COM-003', 'nome' => 'Fechamento de vendas'],
                ],
            ],
            [
                'nome' => 'Operacional',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-GER-OPS-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-GER-OPS-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-GER-OPS-003', 'nome' => 'Gestão de fornecedores'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-GER-ADM-001', 'nome' => 'Gestão financeira'],
                    ['id' => 'SOP-GER-ADM-002', 'nome' => 'Recursos humanos'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Educação
     */
    private function getSOPsEducacao(): array
    {
        return [
            [
                'nome' => 'Acadêmico',
                'icone' => '🎓',
                'sops' => [
                    ['id' => 'SOP-EDU-ACA-001', 'nome' => 'Planejamento pedagógico'],
                    ['id' => 'SOP-EDU-ACA-002', 'nome' => 'Avaliação e recuperação'],
                    ['id' => 'SOP-EDU-ACA-003', 'nome' => 'Gestão de turmas e horários'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-EDU-ADM-001', 'nome' => 'Matrícula e rematrícula'],
                    ['id' => 'SOP-EDU-ADM-002', 'nome' => 'Controle de frequência'],
                    ['id' => 'SOP-EDU-ADM-003', 'nome' => 'Comunicação com responsáveis'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Consultoria
     */
    private function getSOPsConsultoria(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CON-COM-001', 'nome' => 'Diagnóstico empresarial'],
                    ['id' => 'SOP-CON-COM-002', 'nome' => 'Proposta de projeto'],
                    ['id' => 'SOP-CON-COM-003', 'nome' => 'Contratação de consultoria'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-CON-EXEC-001', 'nome' => 'Gestão de projetos'],
                    ['id' => 'SOP-CON-EXEC-002', 'nome' => 'Entrega de resultados'],
                    ['id' => 'SOP-CON-EXEC-003', 'nome' => 'Acompanhamento pós-projeto'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Varejo
     */
    private function getSOPsVarejo(): array
    {
        return [
            [
                'nome' => 'Vendas',
                'icone' => '🛒',
                'sops' => [
                    ['id' => 'SOP-VAR-VEN-001', 'nome' => 'Atendimento e vendas'],
                    ['id' => 'SOP-VAR-VEN-002', 'nome' => 'Gestão do caixa'],
                    ['id' => 'SOP-VAR-VEN-003', 'nome' => 'Pós-venda e trocas'],
                ],
            ],
            [
                'nome' => 'Estoque',
                'icone' => '📦',
                'sops' => [
                    ['id' => 'SOP-VAR-EST-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-VAR-EST-002', 'nome' => 'Compras e fornecedores'],
                    ['id' => 'SOP-VAR-EST-003', 'nome' => 'Inventário e perdas'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Indústria
     */
    private function getSOPsIndustrial(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '🏭',
                'sops' => [
                    ['id' => 'SOP-IND-PROD-001', 'nome' => 'Planejamento de produção'],
                    ['id' => 'SOP-IND-PROD-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-IND-PROD-003', 'nome' => 'Manutenção preventiva'],
                ],
            ],
            [
                'nome' => 'Segurança',
                'icone' => '🦺',
                'sops' => [
                    ['id' => 'SOP-IND-SEG-001', 'nome' => 'Segurança do trabalho'],
                    ['id' => 'SOP-IND-SEG-002', 'nome' => 'Meio ambiente e resíduos'],
                    ['id' => 'SOP-IND-SEG-003', 'nome' => 'Emergências e acidentes'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Alimentício/Restaurante
     */
    private function getSOPsAlimenticio(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '👨‍🍳',
                'sops' => [
                    ['id' => 'SOP-ALI-PROD-001', 'nome' => 'Manipulação de alimentos'],
                    ['id' => 'SOP-ALI-PROD-002', 'nome' => 'Controle de temperatura'],
                    ['id' => 'SOP-ALI-PROD-003', 'nome' => 'Higienização e limpeza'],
                ],
            ],
            [
                'nome' => 'Atendimento',
                'icone' => '🍽️',
                'sops' => [
                    ['id' => 'SOP-ALI-ATD-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-ALI-ATD-002', 'nome' => 'Delivery e take-away'],
                ],
            ],
            [
                'nome' => 'Controle',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-ALI-CTRL-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-ALI-CTRL-002', 'nome' => 'APPCC e vigilância sanitária'],
                ],
            ],
        ];
    }

    // ===== F-06 HELPER METHODS =====

    /**
     * Buscar alertas ativos para um SOP
     */
    private function buscarAlertasAtivos(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM alertas WHERE sop_id = :sop_id AND status = 'ativo' ORDER BY prioridade DESC, data_criacao DESC",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Buscar histórico de contingências de um SOP
     */
    private function buscarHistoricoContencao(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM ocorrencias_contencao WHERE sop_id = :sop_id ORDER BY data_inicio DESC LIMIT 10",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Formatar SOP completo para visualização (F-06)
     */
    private function formatarSopParaViewCompleta(array $sop, array $conteudo, array $empresa, array $kpis): array
    {
        // Estruturar subtópicos com procedimentos próprios
        $subtopicosCompletos = [];
        if (isset($conteudo['subtopicos'])) {
            foreach ($conteudo['subtopicos'] as $index => $subtopico) {
                $letra = chr(65 + $index); // A, B, C...
                
                $subtopicosCompletos[] = [
                    'letra' => $letra,
                    'nome' => $subtopico['nome'],
                    'descricao' => $subtopico['descricao'],
                    'procedimentos' => $conteudo['procedimentos'][$index]['passos'] ?? [],
                    'checklist' => $this->getChecklistPorSubtopico($conteudo['checklist'] ?? [], $index),
                    'evidencias' => $this->getEvidenciasPorSubtopico($conteudo['evidencias'] ?? [], $index),
                ];
            }
        }

        return [
            'id' => $sop['id'],
            'sop_codigo' => $sop['sop_codigo'],
            'titulo' => $sop['titulo'],
            'versao' => $sop['versao'],
            'empresa' => $empresa['nome'] ?? 'Empresa',
            'setor' => $empresa['segmento'] ?? 'Tecnologia', 
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $conteudo['objetivo'] ?? '',
            'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
            'subtopicos_completos' => $subtopicosCompletos,
            'responsaveis' => $conteudo['responsaveis'] ?? [],
            'kpis' => $kpis,
        ];
    }

    /**
     * Dividir checklist por subtópico
     */
    private function getChecklistPorSubtopico(array $checklist, int $index): array
    {
        if (empty($checklist)) return [];
        
        $itemsPorSubtopico = ceil(count($checklist) / 3); // Dividir em 3 subtópicos
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($checklist, $inicio, $itemsPorSubtopico);
    }

    /**
     * Dividir evidências por subtópico
     */
    private function getEvidenciasPorSubtopico(array $evidencias, int $index): array
    {
        if (empty($evidencias)) return [];
        
        $itemsPorSubtopico = ceil(count($evidencias) / 3);
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($evidencias, $inicio, $itemsPorSubtopico);
    }

    // ===== MÉTODOS AUXILIARES PARA NOVA ARQUITETURA DETALHADA =====

    /**
     * Salvar serviços mapeados no banco (Etapa 2A)
     */
    private function salvarServicosMapeados(int $estruturaId, string $setorNome, array $servicosMapeados): int
    {
        Logger::info('=== INICIANDO SALVAMENTO DE SERVIÇOS MAPEADOS ===', [
            'estrutura_id' => $estruturaId,
            'setor_nome' => $setorNome,
            'servicos_count' => count($servicosMapeados['servicos'] ?? []),
            'dados_keys' => array_keys($servicosMapeados)
        ]);

        $totalServicos = count($servicosMapeados['servicos'] ?? []);
        
        // Validar dados antes de salvar
        if ($totalServicos === 0) {
            Logger::warning('Nenhum serviço foi mapeado para o setor', [
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'dados_completos' => $servicosMapeados
            ]);
        }

        // Preparar dados para inserção
        $jsonServicos = json_encode($servicosMapeados, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Erro ao codificar serviços em JSON', [
                'json_error' => json_last_error_msg(),
                'dados_originais' => $servicosMapeados
            ]);
            throw new Exception('Erro ao codificar dados em JSON: ' . json_last_error_msg());
        }

        Logger::info('Dados preparados para inserção', [
            'estrutura_id' => $estruturaId,
            'setor_nome' => $setorNome,
            'setor_tipo' => 'base', // TODO: Determinar tipo baseado no setor
            'total_servicos' => $totalServicos,
            'json_size' => strlen($jsonServicos),
            'json_valid' => json_last_error() === JSON_ERROR_NONE
        ]);

        try {
            $sucesso = Database::execute(
                "INSERT INTO servicos_mapeados (estrutura_id, setor_nome, setor_tipo, servicos_json, total_servicos, status, criado_em, processado_em) 
                 VALUES (?, ?, ?, ?, ?, 'concluido', NOW(), NOW())",
                [
                    $estruturaId,
                    $setorNome,
                    'base', // TODO: Determinar tipo baseado no setor
                    $jsonServicos,
                    $totalServicos
                ]
            );

            if (!$sucesso) {
                Logger::error('Database::execute retornou false', [
                    'estrutura_id' => $estruturaId,
                    'setor_nome' => $setorNome,
                    'query_params' => [
                        'estrutura_id' => $estruturaId,
                        'setor_nome' => $setorNome,
                        'setor_tipo' => 'base',
                        'total_servicos' => $totalServicos,
                        'json_size' => strlen($jsonServicos)
                    ]
                ]);
                throw new Exception('Falha na execução da query de inserção');
            }

            $lastInsertId = (int) Database::lastInsertId();
            
            Logger::info('Serviços mapeados salvos com sucesso', [
                'servico_mapeado_id' => $lastInsertId,
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'total_servicos' => $totalServicos,
                'database_insert_success' => true
            ]);

            // Validação pós-inserção: verificar se o registro foi realmente salvo
            $registroSalvo = Database::queryOne(
                "SELECT id, setor_nome, total_servicos, status FROM servicos_mapeados WHERE id = ?",
                [$lastInsertId]
            );

            if (!$registroSalvo) {
                Logger::error('Registro não encontrado após inserção', [
                    'expected_id' => $lastInsertId,
                    'estrutura_id' => $estruturaId,
                    'setor_nome' => $setorNome
                ]);
                throw new Exception('Falha na verificação pós-inserção');
            }

            Logger::info('Validação pós-inserção bem-sucedida', [
                'registro_salvo' => $registroSalvo,
                'dados_conferem' => [
                    'setor_nome' => $registroSalvo['setor_nome'] === $setorNome,
                    'total_servicos' => (int)$registroSalvo['total_servicos'] === $totalServicos,
                    'status' => $registroSalvo['status'] === 'concluido'
                ]
            ]);

            return $lastInsertId;

        } catch (Exception $e) {
            Logger::error('Erro na operação de banco de dados', [
                'estrutura_id' => $estruturaId,
                'setor_nome' => $setorNome,
                'erro_mensagem' => $e->getMessage(),
                'erro_codigo' => $e->getCode(),
                'erro_arquivo' => $e->getFile(),
                'erro_linha' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'dados_tentativa' => [
                    'total_servicos' => $totalServicos,
                    'json_size' => strlen($jsonServicos),
                    'setor_tipo' => 'base'
                ]
            ]);
            throw $e;
        }
    }

    /**
     * Buscar serviços mapeados de todos os setores
     */
    private function buscarServicosMapeados(int $estruturaId): array
    {
        return Database::query(
            "SELECT * FROM servicos_mapeados WHERE estrutura_id = ? ORDER BY setor_nome",
            [$estruturaId]
        );
    }

    /**
     * Salvar detalhamento de serviço no banco (Etapa 2B)
     */
    private function salvarServicoDetalhado(int $estruturaId, array $servicoMapeado, array $detalhamentoCompleto): int
    {
        $problemasMapeados = count($detalhamentoCompleto['problemas_possiveis'] ?? []);
        $servicoNome = $detalhamentoCompleto['servico'] ?? 'Serviço';
        $servicoCodigo = $this->gerarCodigoServico($servicoMapeado['setor_nome'], $servicoNome);
        
        $sucesso = Database::execute(
            "INSERT INTO servicos_detalhados (servico_mapeado_id, estrutura_id, setor_nome, servico_nome, servico_codigo, 
                                             criticidade, detalhamento_json, problemas_mapeados, status, criado_em, processado_em) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'concluido', NOW(), NOW())",
            [
                $servicoMapeado['id'],
                $estruturaId,
                $servicoMapeado['setor_nome'],
                $servicoNome,
                $servicoCodigo,
                2, // TODO: Extrair criticidade do detalhamento
                json_encode($detalhamentoCompleto, JSON_UNESCAPED_UNICODE),
                $problemasMapeados
            ]
        );
        
        if (!$sucesso) {
            throw new Exception('Erro ao salvar detalhamento de serviço');
        }
        
        return (int) Database::lastInsertId();
    }

    /**
     * Buscar dados específicos de um serviço mapeado
     */
    private function buscarDadosServicoMapeado(int $estruturaId, string $servicoCodigo, string $setorNome): ?array
    {
        // Primeiro tentar buscar por código específico se já existe
        $servico = Database::queryOne(
            "SELECT sm.*, sj.servicos as servicos_json_parsed 
             FROM servicos_mapeados sm 
             LEFT JOIN (SELECT id, JSON_EXTRACT(servicos_json, '$.servicos') as servicos FROM servicos_mapeados) sj ON sm.id = sj.id
             WHERE sm.estrutura_id = ? AND sm.setor_nome = ?",
            [$estruturaId, $setorNome]
        );
        
        if (!$servico) {
            return null;
        }
        
        // Parsear JSON dos serviços e encontrar o específico
        $servicosArray = json_decode($servico['servicos_json'], true);
        if (isset($servicosArray['servicos'])) {
            foreach ($servicosArray['servicos'] as $s) {
                if ($s['codigo'] === $servicoCodigo) {
                    return array_merge($servico, $s, ['setor' => $setorNome]);
                }
            }
        }
        
        return null;
    }

    /**
     * Gerar código único para um serviço
     */
    private function gerarCodigoServico(string $setorNome, string $servicoNome): string
    {
        $setorSlug = strtolower(str_replace([' ', '/', '\\', '-'], '_', $setorNome));
        $servicoSlug = strtolower(str_replace([' ', '/', '\\', '-'], '_', $servicoNome));
        
        return $setorSlug . '_' . $servicoSlug;
    }

    /**
     * Inicializar progresso do manual se não existir
     */
    private function inicializarProgressoManual(int $estruturaId, array $estruturaData): void
    {
        $existente = Database::queryOne(
            "SELECT id FROM progresso_manual WHERE estrutura_id = ?",
            [$estruturaId]
        );
        
        if (!$existente) {
            $totalSetores = count($estruturaData['estrutura']['setores'] ?? []);
            
            // Buscar empresa_id do diagnóstico
            $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
            $empresaId = $diagnostico['empresa_id'] ?? 0;
            
            Database::execute(
                "INSERT INTO progresso_manual (estrutura_id, diagnostico_id, empresa_id, etapa_atual, total_setores, iniciado_em) 
                 VALUES (?, ?, ?, 'etapa2a', ?, NOW())",
                [
                    $estruturaId,
                    $estruturaData['diagnostico_id'],
                    $empresaId,
                    $totalSetores
                ]
            );
        }
    }

    /**
     * Buscar progresso atual do manual
     */
    private function buscarProgressoManual(int $estruturaId): ?array
    {
        return Database::queryOne(
            "SELECT * FROM progresso_manual WHERE estrutura_id = ?",
            [$estruturaId]
        );
    }

    /**
     * Atualizar progresso da Etapa 2A
     */
    private function atualizarProgressoEtapa2A(int $estruturaId): void
    {
        Logger::info('=== ATUALIZANDO PROGRESSO ETAPA 2A ===', [
            'estrutura_id' => $estruturaId
        ]);

        try {
            // Contar setores mapeados
            $querySetoresMapeados = Database::queryOne(
                "SELECT COUNT(*) as total FROM servicos_mapeados WHERE estrutura_id = ? AND status = 'concluido'",
                [$estruturaId]
            );
            $setoresMapeados = $querySetoresMapeados['total'] ?? 0;

            Logger::info('Setores mapeados contabilizados', [
                'estrutura_id' => $estruturaId,
                'setores_mapeados' => $setoresMapeados,
                'query_result' => $querySetoresMapeados
            ]);
            
            // Buscar total de setores
            $progresso = $this->buscarProgressoManual($estruturaId);
            if (!$progresso) {
                Logger::warning('Progresso manual não encontrado', [
                    'estrutura_id' => $estruturaId
                ]);
                return;
            }

            $totalSetores = $progresso['total_setores'] ?? 1;
            Logger::info('Dados de progresso recuperados', [
                'estrutura_id' => $estruturaId,
                'total_setores' => $totalSetores,
                'setores_mapeados' => $setoresMapeados,
                'progresso_atual' => $progresso['progresso_percentual'] ?? 0
            ]);
            
            // Calcular percentual (Etapa 2A = 25% do total)
            $percentualEtapa2A = ($setoresMapeados / $totalSetores) * 25;
            
            Logger::info('Novo percentual calculado', [
                'estrutura_id' => $estruturaId,
                'calculo' => "({$setoresMapeados} / {$totalSetores}) * 25",
                'percentual_etapa2a' => $percentualEtapa2A,
                'percentual_formatado' => round($percentualEtapa2A, 2) . '%'
            ]);
            
            $updateResult = Database::execute(
                "UPDATE progresso_manual SET setores_mapeados = ?, progresso_percentual = ?, atualizado_em = NOW() 
                 WHERE estrutura_id = ?",
                [$setoresMapeados, $percentualEtapa2A, $estruturaId]
            );

            if ($updateResult) {
                Logger::info('Progresso atualizado com sucesso', [
                    'estrutura_id' => $estruturaId,
                    'setores_mapeados' => $setoresMapeados,
                    'percentual_final' => $percentualEtapa2A,
                    'database_update_success' => true
                ]);

                // Verificação pós-update
                $progressoAtualizado = $this->buscarProgressoManual($estruturaId);
                Logger::info('Verificação pós-update', [
                    'progresso_pos_update' => $progressoAtualizado,
                    'dados_conferem' => [
                        'setores_mapeados' => (int)$progressoAtualizado['setores_mapeados'] === $setoresMapeados,
                        'percentual' => abs((float)$progressoAtualizado['progresso_percentual'] - $percentualEtapa2A) < 0.01
                    ]
                ]);
            } else {
                Logger::error('Falha ao atualizar progresso no banco', [
                    'estrutura_id' => $estruturaId,
                    'setores_mapeados' => $setoresMapeados,
                    'percentual' => $percentualEtapa2A
                ]);
            }

        } catch (Exception $e) {
            Logger::error('Erro ao atualizar progresso da Etapa 2A', [
                'estrutura_id' => $estruturaId,
                'erro_mensagem' => $e->getMessage(),
                'erro_codigo' => $e->getCode(),
                'erro_arquivo' => $e->getFile(),
                'erro_linha' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Verificar se Etapa 2A foi concluída
     */
    private function verificarEtapa2AConcluida(int $estruturaId): bool
    {
        $progresso = $this->buscarProgressoManual($estruturaId);
        if (!$progresso) return false;
        
        return ($progresso['setores_mapeados'] >= $progresso['total_setores']);
    }

    /**
     * Atualizar progresso da Etapa 2B
     */
    private function atualizarProgressoEtapa2B(int $estruturaId): void
    {
        // Contar serviços detalhados
        $servicosDetalhados = Database::queryOne(
            "SELECT COUNT(*) as total FROM servicos_detalhados WHERE estrutura_id = ? AND status = 'concluido'",
            [$estruturaId]
        )['total'] ?? 0;
        
        // Contar total de serviços mapeados
        $totalServicos = Database::queryOne(
            "SELECT SUM(total_servicos) as total FROM servicos_mapeados WHERE estrutura_id = ?",
            [$estruturaId]
        )['total'] ?? 1;
        
        // Calcular percentual (Etapa 2B = 50% do total, começa em 25%)
        $percentualEtapa2B = 25 + (($servicosDetalhados / $totalServicos) * 50);
        
        Database::execute(
            "UPDATE progresso_manual SET servicos_detalhados = ?, total_servicos = ?, progresso_percentual = ?, atualizado_em = NOW() 
             WHERE estrutura_id = ?",
            [$servicosDetalhados, $totalServicos, $percentualEtapa2B, $estruturaId]
        );
    }

    /**
     * Verificar e criar tabelas da nova arquitetura se necessário
     */
    private function verificarTabelasNovaArquitetura(): void
    {
        try {
            // Verificar se as tabelas existem
            Database::queryOne("SELECT 1 FROM servicos_mapeados LIMIT 1");
            Database::queryOne("SELECT 1 FROM servicos_detalhados LIMIT 1");
            Database::queryOne("SELECT 1 FROM progresso_manual LIMIT 1");
            
            // Verificar se estruturas_temporarias tem coluna atualizado_em
            $this->garantirColunaAtualizadoEm();
            
        } catch (Exception $e) {
            // Se as tabelas não existem, criá-las
            Logger::info('Criando tabelas da nova arquitetura automaticamente');
            $this->criarTabelasNovaArquitetura();
        }
    }
    
    /**
     * Garantir que a tabela estruturas_temporarias tenha a coluna atualizado_em
     */
    private function garantirColunaAtualizadoEm(): void
    {
        try {
            // Tentar buscar a coluna
            Database::queryOne("SELECT atualizado_em FROM estruturas_temporarias LIMIT 1");
        } catch (Exception $e) {
            // Se a coluna não existe, adicionar
            Logger::info('Adicionando coluna atualizado_em à tabela estruturas_temporarias');
            Database::execute("ALTER TABLE estruturas_temporarias ADD COLUMN atualizado_em DATETIME DEFAULT NULL");
            Database::execute("UPDATE estruturas_temporarias SET atualizado_em = criado_em WHERE atualizado_em IS NULL");
        }
    }

    /**
     * Criar tabelas da nova arquitetura automaticamente
     */
    private function criarTabelasNovaArquitetura(): void
    {
        // Tabela para serviços mapeados (Etapa 2A)
        Database::execute("
            CREATE TABLE IF NOT EXISTS `servicos_mapeados` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `estrutura_id` int(11) NOT NULL COMMENT 'FK para estruturas_temporarias',
              `setor_nome` varchar(100) NOT NULL,
              `setor_tipo` enum('base', 'especifico_do_nicho') DEFAULT 'base',
              `servicos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Lista de todos os serviços possíveis do setor',
              `total_servicos` int(11) DEFAULT 0 COMMENT 'Quantidade de serviços identificados',
              `status` enum('mapeando', 'concluido', 'erro') DEFAULT 'mapeando',
              `criado_em` datetime NOT NULL,
              `processado_em` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_estrutura_id` (`estrutura_id`),
              KEY `idx_setor_nome` (`setor_nome`),
              KEY `idx_status` (`status`),
              KEY `idx_criado_em` (`criado_em`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela para serviços detalhados (Etapa 2B)
        Database::execute("
            CREATE TABLE IF NOT EXISTS `servicos_detalhados` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `servico_mapeado_id` int(11) NOT NULL COMMENT 'FK para servicos_mapeados',
              `estrutura_id` int(11) NOT NULL COMMENT 'FK para estruturas_temporarias',
              `setor_nome` varchar(100) NOT NULL,
              `servico_nome` varchar(200) NOT NULL,
              `servico_codigo` varchar(50) NOT NULL COMMENT 'Código único do serviço',
              `criticidade` tinyint(1) DEFAULT 2 COMMENT '1=crítico, 2=importante, 3=complementar',
              `detalhamento_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Detalhamento completo com problemas N1-N2-N3',
              `problemas_mapeados` int(11) DEFAULT 0 COMMENT 'Quantidade de problemas N1-N2-N3 identificados',
              `status` enum('detalhando', 'concluido', 'erro') DEFAULT 'detalhando',
              `criado_em` datetime NOT NULL,
              `processado_em` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_servico_mapeado_id` (`servico_mapeado_id`),
              KEY `idx_estrutura_id` (`estrutura_id`),
              KEY `idx_setor_nome` (`setor_nome`),
              KEY `idx_servico_codigo` (`servico_codigo`),
              KEY `idx_criticidade` (`criticidade`),
              KEY `idx_status` (`status`),
              KEY `idx_criado_em` (`criado_em`),
              UNIQUE KEY `unique_servico_por_estrutura` (`estrutura_id`, `servico_codigo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Tabela para progresso das etapas
        Database::execute("
            CREATE TABLE IF NOT EXISTS `progresso_manual` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `estrutura_id` int(11) NOT NULL,
              `diagnostico_id` int(11) NOT NULL,
              `empresa_id` int(11) NOT NULL,
              `etapa_atual` enum('etapa1', 'etapa2a', 'etapa2b', 'etapa3', 'concluido') DEFAULT 'etapa1',
              `total_setores` int(11) DEFAULT 0,
              `setores_mapeados` int(11) DEFAULT 0,
              `total_servicos` int(11) DEFAULT 0,
              `servicos_detalhados` int(11) DEFAULT 0,
              `total_sops` int(11) DEFAULT 0,
              `sops_gerados` int(11) DEFAULT 0,
              `progresso_percentual` decimal(5,2) DEFAULT 0.00,
              `iniciado_em` datetime NOT NULL,
              `atualizado_em` datetime DEFAULT NULL,
              `concluido_em` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_estrutura` (`estrutura_id`),
              KEY `idx_diagnostico_id` (`diagnostico_id`),
              KEY `idx_empresa_id` (`empresa_id`),
              KEY `idx_etapa_atual` (`etapa_atual`),
              KEY `idx_progresso` (`progresso_percentual`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        Logger::info('Tabelas da nova arquitetura criadas automaticamente');
    }

    /**
     * Regenerar token CSRF via AJAX (para debug)
     */
    public function regenerarTokenCSRF(): void
    {
        Auth::proteger();
        
        $novoToken = Csrf::gerar();
        
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'novo_token' => $novoToken,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Verificar e criar tabelas da nova arquitetura via AJAX (para debug)
     */
    public function verificarCriarTabelas(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        try {
            $tabelasCriadas = [];
            $tabelasExistentes = [];
            
            // Lista das tabelas necessárias
            $tabelasNecessarias = [
                'estruturas_temporarias',
                'progresso_manual_completo', 
                'servicos_mapeados',
                'servicos_detalhados',
                'sops_gerados_nova_arquitetura',
                'servicos_manuais'
            ];
            
            foreach ($tabelasNecessarias as $tabela) {
                $existe = Database::queryOne("SHOW TABLES LIKE '{$tabela}'");
                
                if ($existe) {
                    $tabelasExistentes[] = $tabela;
                } else {
                    // Tentar criar a tabela executando a migração
                    $this->criarTabelaSeNecessario($tabela);
                    $tabelasCriadas[] = $tabela;
                }
            }
            
            Logger::info('Verificação de tabelas concluída', [
                'tabelas_criadas' => $tabelasCriadas,
                'tabelas_existentes' => $tabelasExistentes
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'tabelas_criadas' => $tabelasCriadas,
                'tabelas_existentes' => $tabelasExistentes,
                'total_tabelas' => count($tabelasNecessarias),
                'mensagem' => 'Verificação das tabelas concluída com sucesso.'
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro na verificação de tabelas', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro na verificação das tabelas: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Criar uma tabela específica se necessário
     */
    private function criarTabelaSeNecessario(string $nomeTabela): void
    {
        // Executar a migração 026 que cria todas as tabelas necessárias
        $migrationFile = __DIR__ . '/../../database/migrations/026_adicionar_tabelas_servicos.sql';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Arquivo de migração não encontrado: {$migrationFile}");
        }
        
        $sql = file_get_contents($migrationFile);
        
        if (!$sql) {
            throw new Exception("Não foi possível ler o arquivo de migração");
        }
        
        // Dividir em comandos individuais
        $comandos = array_filter(
            array_map('trim', explode(';', $sql)), 
            function($cmd) { 
                return !empty($cmd) && !preg_match('/^\s*--/', $cmd); 
            }
        );
        
        foreach ($comandos as $comando) {
            if (!empty($comando)) {
                try {
                    Database::execute($comando);
                } catch (Exception $e) {
                    // Ignorar erros de "tabela já existe"
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'já existe') === false) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Detalhar um serviço específico individualmente
     */
    public function detalharServicoIndividual(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $estruturaId = (int) ($_POST['estrutura_id'] ?? 0);
        $setorIndex = (int) ($_POST['setor_index'] ?? 0);
        $servicoIndex = (int) ($_POST['servico_index'] ?? 0);
        $servicoNome = trim($_POST['servico_nome'] ?? '');
        
        Logger::info('Iniciando detalhamento individual', [
            'estrutura_id' => $estruturaId,
            'setor_index' => $setorIndex,
            'servico_index' => $servicoIndex,
            'servico_nome' => $servicoNome
        ]);
        
        if (!$estruturaId || !$servicoNome) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }
        
        try {
            // Buscar dados da estrutura
            $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
            if (!$estruturaData) {
                throw new Exception('Estrutura não encontrada.');
            }
            
            $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
            $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
            $respostas = json_decode($diagnostico['respostas'], true) ?? [];
            $dadosEmpresa = $this->extrairDadosEmpresaCompletos($empresa, $diagnostico, $respostas);
            
            // Gerar detalhamento usando IA (com fallback automático GPT -> Claude)
            $prompt = ApiHelper::buildPromptDetalhamentoServicoIndividual($dadosEmpresa, $servicoNome);
            $resultadoIA = ApiHelper::chamarAnalise($prompt, true);
            
            if (!$resultadoIA || !$resultadoIA['sucesso']) {
                $erro = $resultadoIA['erro'] ?? 'Erro na comunicação com a IA.';
                throw new Exception($erro);
            }
            
            $detalhamento = $resultadoIA['conteudo'];
            if (is_string($detalhamento)) {
                $detalhamento = json_decode($detalhamento, true);
            }
            
            if (!$detalhamento) {
                throw new Exception('Resposta inválida da IA.');
            }
            
            // Salvar detalhamento no banco
            $detalhamentoId = Database::execute(
                "INSERT INTO servicos_detalhados (estrutura_id, setor_nome, servico_nome, detalhamento_json, criado_em, empresa_id) 
                 VALUES (:estrutura_id, :setor_nome, :servico_nome, :detalhamento, NOW(), :empresa_id)",
                [
                    'estrutura_id' => $estruturaId,
                    'setor_nome' => $estruturaData['estrutura']['setores'][$setorIndex]['nome_setor'] ?? 'Setor Desconhecido',
                    'servico_nome' => $servicoNome,
                    'detalhamento' => json_encode($detalhamento, JSON_UNESCAPED_UNICODE),
                    'empresa_id' => Auth::empresa()
                ]
            );
            
            $detalhamentoId = Database::lastInsertId();
            
            Logger::info('Detalhamento individual gerado', [
                'detalhamento_id' => $detalhamentoId,
                'servico' => $servicoNome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'detalhamento_id' => $detalhamentoId,
                'servico_nome' => $servicoNome,
                'total_cenarios' => count($detalhamento['cenarios'] ?? []),
                'total_processos' => count($detalhamento['processos'] ?? []),
                'mensagem' => 'Detalhamento gerado com sucesso!'
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro no detalhamento individual', [
                'erro' => $e->getMessage(),
                'servico' => $servicoNome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro ao gerar detalhamento: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Gerar SOP de um serviço específico individualmente
     */
    public function gerarSopIndividual(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $estruturaId = (int) ($_POST['estrutura_id'] ?? 0);
        $setorIndex = (int) ($_POST['setor_index'] ?? 0);
        $servicoIndex = (int) ($_POST['servico_index'] ?? 0);
        $servicoNome = trim($_POST['servico_nome'] ?? '');
        
        Logger::info('Iniciando geração de SOP individual COMPLETO', [
            'estrutura_id' => $estruturaId,
            'setor_index' => $setorIndex,
            'servico_index' => $servicoIndex,
            'servico_nome' => $servicoNome
        ]);
        
        if (!$estruturaId || !$servicoNome) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }
        
        try {
            // Buscar dados da estrutura
            $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
            if (!$estruturaData) {
                throw new Exception('Estrutura não encontrada.');
            }
            
            $diagnostico = Diagnostico::buscarPorId($estruturaData['diagnostico_id']);
            $empresa = Empresa::buscarPorId($diagnostico['empresa_id']);
            $respostas = json_decode($diagnostico['respostas'], true) ?? [];
            
            // Preparar dados do serviço para o novo formato
            $servicoData = [
                'nome_servico' => $servicoNome,
                'nome_setor' => $estruturaData['estrutura']['setores'][$setorIndex]['nome_setor'] ?? 'Setor Desconhecido',
                'codigo_servico' => 'SOP-' . strtoupper(substr($servicoNome, 0, 3)) . '-001',
                'empresa_id' => Auth::empresa()
            ];
            
            // Buscar detalhamento existente (se houver) ou criar básico
            $detalhamento = Database::queryOne(
                "SELECT * FROM servicos_detalhados WHERE estrutura_id = :estrutura_id AND servico_nome = :servico_nome ORDER BY id DESC LIMIT 1",
                ['estrutura_id' => $estruturaId, 'servico_nome' => $servicoNome]
            );
            
            $detalhamentoData = null;
            if ($detalhamento) {
                $detalhamentoData = json_decode($detalhamento['detalhamento_json'], true);
            } else {
                // Criar detalhamento mínimo se não existir
                $detalhamentoData = [
                    'servico' => $servicoNome,
                    'setor' => $servicoData['nome_setor'],
                    'objetivo_principal' => 'Executar ' . $servicoNome . ' de forma eficiente e padronizada',
                    'processos_principais' => ['Preparação', 'Execução', 'Finalização'],
                    'recursos_necessarios' => ['Sistemas internos', 'Documentação padrão']
                ];
            }
            
            // Usar o MESMO prompt detalhado da regeneração
            $prompt = $this->criarPromptSopCompletissimo($servicoData, $detalhamentoData, $empresa, $diagnostico);
            
            Logger::info('Chamando OpenAI para geração completa individual', [
                'prompt_length' => strlen($prompt),
                'servico' => $servicoNome
            ]);
            
            $resultadoIA = ApiHelper::chamarOpenAI($prompt, 'gpt-4o', true);
            
            if (!$resultadoIA || !$resultadoIA['sucesso']) {
                $erro = $resultadoIA['erro'] ?? 'Erro na comunicação com a IA.';
                throw new Exception($erro);
            }
            
            $sop = $resultadoIA['conteudo'];
            
            if (!$sop) {
                throw new Exception('Resposta inválida da IA.');
            }
            
            // Validar seções obrigatórias
            $secoesObrigatorias = ['objetivo', 'escopo', 'procedimentos', 'responsaveis'];
            foreach ($secoesObrigatorias as $secao) {
                if (!isset($sop[$secao]) || empty($sop[$secao])) {
                    Logger::warning("Seção '$secao' faltando no SOP gerado, adicionando automaticamente");
                }
            }
            
            // Salvar SOP no banco usando a tabela sops
            Database::execute(
                "INSERT INTO sops (empresa_id, titulo, departamento, conteudo, versao, status, gerado_por_ia, criado_em, atualizado_em) 
                 VALUES (:empresa_id, :titulo, :departamento, :conteudo, '1.0', 'ativo', 1, NOW(), NOW())",
                [
                    'empresa_id' => Auth::empresa(),
                    'titulo' => $servicoNome,
                    'departamento' => $servicoData['nome_setor'],
                    'conteudo' => json_encode($sop, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                ]
            );
            
            $sopId = Database::lastInsertId();
            
            Logger::info('SOP individual COMPLETO gerado', [
                'sop_id' => $sopId,
                'servico' => $servicoNome,
                'secoes_incluidas' => array_keys($sop)
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'sop_id' => $sopId,
                'servico_nome' => $servicoNome,
                'total_procedimentos' => count($sop['procedimentos'] ?? []),
                'total_checklists' => count($sop['checklists'] ?? []),
                'total_controles' => count($sop['pontos_controle'] ?? []),
                'mensagem' => 'SOP COMPLETO gerado com sucesso! Inclui procedimentos detalhados, controles, checklists e KPIs.'
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro na geração de SOP individual completo', [
                'erro' => $e->getMessage(),
                'servico' => $servicoNome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro ao gerar SOP: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Ver detalhamento de um serviço
     */
    public function verDetalhamentoServico(): void
    {
        Auth::proteger();
        
        $detalhamentoId = (int) ($_GET['detalhamento_id'] ?? 0);
        
        if (!$detalhamentoId) {
            Flash::set('erro', 'Detalhamento não encontrado.');
            header('Location: ' . APP_URL . '/sop');
            exit;
        }
        
        $detalhamento = Database::queryOne(
            "SELECT * FROM servicos_detalhados WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $detalhamentoId, 'empresa_id' => Auth::empresa()]
        );
        
        if (!$detalhamento) {
            Flash::set('erro', 'Detalhamento não encontrado.');
            header('Location: ' . APP_URL . '/sop');
            exit;
        }
        
        $dados = [
            'detalhamento' => $detalhamento,
            'detalhamento_data' => json_decode($detalhamento['detalhamento_json'], true),
            'titulo_pagina' => 'Detalhamento: ' . $detalhamento['servico_nome']
        ];
        
        require VIEW_PATH . '/sop/ver-detalhamento-servico.php';
    }

    /**
     * Ver SOP individual
     */
    public function verSopIndividual(): void
    {
        Auth::proteger();
        
        // Aceitar tanto 'sop_id' quanto 'id' para compatibilidade
        $sopId = (int) ($_GET['sop_id'] ?? $_GET['id'] ?? 0);
        
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/sop');
            exit;
        }
        
        $sop = Database::queryOne(
            "SELECT s.*, ss.codigo_servico, ss.nome_servico, se.nome_setor 
             FROM sops s
             INNER JOIN servicos_setor ss ON s.id = ss.sop_id
             INNER JOIN setores_empresa se ON ss.setor_id = se.id
             WHERE s.id = :id AND s.empresa_id = :empresa_id AND ss.tem_sop = 1",
            ['id' => $sopId, 'empresa_id' => Auth::empresa()]
        );
        
        // Se não encontrou com JOIN, tentar buscar apenas na tabela sops (fallback)
        if (!$sop) {
            $sop = Database::queryOne(
                "SELECT s.*, s.titulo as nome_servico, s.titulo as servico_nome,
                        s.departamento as nome_setor, s.departamento as setor_nome,
                        s.titulo as codigo_servico
                 FROM sops s
                 WHERE s.id = :id AND s.empresa_id = :empresa_id",
                ['id' => $sopId, 'empresa_id' => Auth::empresa()]
            );
        }
        
        // Debug: Log para identificar o problema
        Logger::info('Debug verSopIndividual', [
            'sop_id' => $sopId,
            'empresa_id' => Auth::empresa(),
            'sop_encontrado' => !empty($sop),
            'campos_sop' => $sop ? array_keys($sop) : 'nenhum'
        ]);
        
        // Debug adicional: Verificar se existem SOPs na tabela
        $totalSOPs = Database::queryOne(
            "SELECT COUNT(*) as total FROM sops WHERE empresa_id = :empresa_id",
            ['empresa_id' => Auth::empresa()]
        );
        
        Logger::info('Debug: SOPs na empresa', [
            'total_sops' => $totalSOPs['total'] ?? 0,
            'empresa_id' => Auth::empresa()
        ]);
        
        if (!$sop) {
            // Verificar se há um serviço referenciando este SOP mas o SOP não existe
            $servicoComReferencia = Database::queryOne(
                "SELECT ss.*, se.nome_setor FROM servicos_setor ss 
                 LEFT JOIN setores_empresa se ON ss.setor_id = se.id
                 WHERE ss.sop_id = :sop_id AND ss.empresa_id = :empresa_id",
                ['sop_id' => $sopId, 'empresa_id' => Auth::empresa()]
            );
            
            if ($servicoComReferencia) {
                Logger::error('Referência quebrada detectada', [
                    'sop_id' => $sopId,
                    'servico' => $servicoComReferencia['nome_servico'],
                    'setor' => $servicoComReferencia['nome_setor'] ?? 'N/A'
                ]);
                
                Flash::set('erro', "Referência de SOP quebrada detectada! O serviço '{$servicoComReferencia['nome_servico']}' aponta para um SOP inexistente. <a href='" . APP_URL . "/sop/corrigir-referencias' class='underline text-blue-600'>Clique aqui para corrigir automaticamente</a>.");
            } else {
                Flash::set('erro', 'SOP não encontrado.');
            }
            
            header('Location: ' . APP_URL . '/sop');
            exit;
        }
        
        // Debug: verificar se o conteúdo é um JSON válido
        $conteudoDecodificado = json_decode($sop['conteudo'], true);
        $erroJson = json_last_error();
        
        Logger::info('Debug conteúdo SOP', [
            'sop_id' => $sopId,
            'conteudo_raw' => substr($sop['conteudo'], 0, 200) . '...',
            'conteudo_decodificado' => $conteudoDecodificado ? 'SUCESSO' : 'FALHOU',
            'json_error' => $erroJson,
            'json_error_msg' => json_last_error_msg(),
            'conteudo_length' => strlen($sop['conteudo']),
            'conteudo_type' => gettype($sop['conteudo'])
        ]);
        
        // Se o JSON falhou, criar estrutura mínima para evitar erros na view
        if (!$conteudoDecodificado) {
            $conteudoDecodificado = [
                'sop_titulo' => $sop['titulo'] ?? 'SOP sem título',
                'objetivo' => 'Conteúdo sendo processado...',
                'escopo' => 'Aguarde processamento completo.',
                'procedimentos' => [],
                'erro_json' => 'Conteúdo não está em formato JSON válido: ' . json_last_error_msg()
            ];
        }
        
        $dados = [
            'sop' => $sop,
            'sop_data' => $conteudoDecodificado,
            'titulo_pagina' => 'SOP: ' . ($sop['nome_servico'] ?? $sop['titulo'] ?? 'SOP Individual')
        ];
        
        // Verificar se todos os dados necessários estão presentes
        Logger::info('Dados preparados para view', [
            'sop_keys' => array_keys($sop),
            'sop_data_keys' => array_keys($conteudoDecodificado),
            'titulo_pagina' => $dados['titulo_pagina']
        ]);
        
        require VIEW_PATH . '/sop/ver-sop-individual.php';
    }
    
    /**
     * Debug: Ver dados brutos do SOP e relacionamentos (apenas para debug)
     */
    public function debugSopDados(): void
    {
        Auth::proteger();
        
        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            die('ID não informado');
        }
        
        header('Content-Type: text/plain; charset=utf-8');
        echo "=== DEBUG COMPLETO SOP #$sopId ===\n\n";
        
        // 1. Verificar se SOP existe na tabela sops
        $sop = Database::queryOne(
            "SELECT * FROM sops WHERE id = :id AND empresa_id = :empresa_id LIMIT 1",
            ['id' => $sopId, 'empresa_id' => Auth::empresa()]
        );
        
        echo "1. TABELA SOPS:\n";
        if ($sop) {
            echo "✅ SOP encontrado na tabela sops\n";
            foreach ($sop as $campo => $valor) {
                $valorExibicao = is_string($valor) ? (strlen($valor) > 100 ? substr($valor, 0, 100) . '...' : $valor) : $valor;
                echo "- $campo: $valorExibicao\n";
            }
        } else {
            echo "❌ SOP NÃO encontrado na tabela sops\n";
        }
        
        echo "\n2. TABELA SERVICOS_SETOR (procurando sop_id = $sopId):\n";
        $servicos = Database::query(
            "SELECT * FROM servicos_setor WHERE sop_id = :sop_id AND empresa_id = :empresa_id",
            ['sop_id' => $sopId, 'empresa_id' => Auth::empresa()]
        );
        
        if ($servicos) {
            echo "✅ " . count($servicos) . " serviço(s) encontrado(s) vinculado(s) ao SOP\n";
            foreach ($servicos as $i => $servico) {
                echo "Serviço #" . ($i + 1) . ":\n";
                foreach ($servico as $campo => $valor) {
                    $valorExibicao = is_string($valor) ? (strlen($valor) > 100 ? substr($valor, 0, 100) . '...' : $valor) : $valor;
                    echo "  - $campo: $valorExibicao\n";
                }
                echo "\n";
            }
        } else {
            echo "❌ Nenhum serviço encontrado com sop_id = $sopId\n";
        }
        
        echo "\n3. VERIFICAR SERVIÇOS COM STATUS 'sop_gerado' ou 'aprovado':\n";
        $servicosGerados = Database::query(
            "SELECT id, nome_servico, codigo_servico, sop_id, status FROM servicos_setor 
             WHERE empresa_id = :empresa_id AND status IN ('sop_gerado', 'aprovado') 
             ORDER BY id DESC LIMIT 10",
            ['empresa_id' => Auth::empresa()]
        );
        
        if ($servicosGerados) {
            echo "✅ " . count($servicosGerados) . " serviço(s) com SOP gerado:\n";
            foreach ($servicosGerados as $servico) {
                echo "- ID: {$servico['id']}, Serviço: {$servico['nome_servico']}, SOP ID: {$servico['sop_id']}, Status: {$servico['status']}\n";
            }
        } else {
            echo "❌ Nenhum serviço encontrado com SOP gerado\n";
        }
        
        echo "\n4. VERIFICAR TODOS OS SOPs DA EMPRESA:\n";
        $todosSOPs = Database::query(
            "SELECT id, titulo, departamento, status, criado_em FROM sops 
             WHERE empresa_id = :empresa_id 
             ORDER BY id DESC LIMIT 10",
            ['empresa_id' => Auth::empresa()]
        );
        
        if ($todosSOPs) {
            echo "✅ " . count($todosSOPs) . " SOP(s) na empresa:\n";
            foreach ($todosSOPs as $sopItem) {
                echo "- ID: {$sopItem['id']}, Título: {$sopItem['titulo']}, Status: {$sopItem['status']}\n";
            }
        } else {
            echo "❌ Nenhum SOP encontrado na empresa\n";
        }
        
        echo "\n5. EMPRESA ATUAL:\n";
        echo "- Empresa ID: " . Auth::empresa() . "\n";
        echo "- Usuário: " . (Auth::usuario()['nome'] ?? 'N/A') . "\n";
        
        echo "\n=== FIM DEBUG ===\n";
    }
    
    /**
     * Debug: Ver dados da hierarquia completa para debug
     */
    public function debugHierarquia(): void
    {
        Auth::proteger();
        
        $estruturaId = (int) ($_GET['estrutura_id'] ?? 0);
        
        header('Content-Type: text/plain; charset=utf-8');
        echo "=== DEBUG HIERARQUIA COMPLETA ===\n\n";
        echo "Empresa ID: " . Auth::empresa() . "\n";
        echo "Estrutura ID: $estruturaId\n\n";
        
        // 1. Verificar estrutura
        $estrutura = Database::queryOne(
            "SELECT * FROM estruturas_organizacionais WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $estruturaId, 'empresa_id' => Auth::empresa()]
        );
        
        echo "1. ESTRUTURA ORGANIZACIONAL:\n";
        if ($estrutura) {
            echo "✅ Estrutura encontrada: {$estrutura['nome_empresa']}\n";
            echo "- Total setores: {$estrutura['total_setores']}\n";
            echo "- Status: {$estrutura['status']}\n";
        } else {
            echo "❌ Estrutura não encontrada\n";
        }
        
        // 2. Verificar setores
        echo "\n2. SETORES:\n";
        $setores = Database::query(
            "SELECT * FROM setores_empresa WHERE estrutura_id = :estrutura_id ORDER BY nome_setor",
            ['estrutura_id' => $estruturaId]
        );
        
        foreach ($setores as $setor) {
            echo "Setor: {$setor['nome_setor']} (ID: {$setor['id']})\n";
            echo "- Total serviços: {$setor['total_servicos']}\n";
            echo "- Total SOPs: {$setor['total_sops']}\n";
            echo "- Status: {$setor['status']}\n";
            
            // 3. Verificar serviços do setor
            $servicos = Database::query(
                "SELECT id, nome_servico, codigo_servico, status, tem_sop, sop_id FROM servicos_setor 
                 WHERE setor_id = :setor_id ORDER BY nome_servico",
                ['setor_id' => $setor['id']]
            );
            
            echo "  Serviços (" . count($servicos) . "):\n";
            foreach ($servicos as $servico) {
                $statusIcon = match($servico['status']) {
                    'sop_gerado', 'aprovado' => '✅',
                    'detalhado' => '🔧',
                    'mapeado' => '📋',
                    default => '⚠️'
                };
                
                echo "    $statusIcon {$servico['nome_servico']} (ID: {$servico['id']})\n";
                echo "      - Código: {$servico['codigo_servico']}\n";
                echo "      - Status: {$servico['status']}\n";
                echo "      - Tem SOP: " . ($servico['tem_sop'] ? 'SIM' : 'NÃO') . "\n";
                echo "      - SOP ID: " . ($servico['sop_id'] ?? 'NULL') . "\n";
                
                // Verificar se o SOP realmente existe
                if ($servico['sop_id']) {
                    $sopExiste = Database::queryOne(
                        "SELECT id, titulo, status FROM sops WHERE id = :id",
                        ['id' => $servico['sop_id']]
                    );
                    
                    if ($sopExiste) {
                        echo "      - SOP existe: ✅ '{$sopExiste['titulo']}' ({$sopExiste['status']})\n";
                    } else {
                        echo "      - SOP existe: ❌ REFERÊNCIA QUEBRADA!\n";
                    }
                }
                echo "\n";
            }
            echo "\n";
        }
        
        echo "=== FIM DEBUG HIERARQUIA ===\n";
    }
    
    /**
     * Debug: Verificar configurações de API e testar conexão
     */
    public function debugApi(): void
    {
        Auth::proteger();
        
        header('Content-Type: text/plain; charset=utf-8');
        echo "=== DEBUG CONFIGURAÇÕES DE API ===\n\n";
        
        try {
            // Verificar configurações OpenAI
            $openaiKey = ApiHelper::config('openai_key');
            $openaiModelo = ApiHelper::config('openai_modelo', 'gpt-4o');
            $maxTokens = ApiHelper::config('openai_max_tokens', '8192');
            
            echo "1. CONFIGURAÇÕES OPENAI:\n";
            echo "- Chave configurada: " . ($openaiKey ? "✅ SIM (***" . substr($openaiKey, -4) . ")" : "❌ NÃO") . "\n";
            echo "- Modelo: $openaiModelo\n";
            echo "- Max tokens: $maxTokens\n\n";
            
            if (!$openaiKey) {
                echo "❌ ERRO: Chave OpenAI não configurada!\n";
                echo "Configure em: Admin > Configurações > APIs\n\n";
                return;
            }
            
            // Testar chamada simples
            echo "2. TESTE DE CONEXÃO:\n";
            $promptTeste = "Responda apenas com um JSON simples: {\"status\": \"ok\", \"mensagem\": \"API funcionando\"}";
            
            $resultado = ApiHelper::chamarOpenAI($promptTeste, $openaiModelo, true);
            
            if ($resultado['sucesso']) {
                echo "✅ Conexão com OpenAI: SUCESSO\n";
                echo "✅ Resposta válida recebida\n";
                if (isset($resultado['conteudo']['status'])) {
                    echo "✅ JSON parsing: OK\n";
                } else {
                    echo "⚠️ JSON parsing: Estrutura inesperada\n";
                }
            } else {
                echo "❌ Conexão com OpenAI: FALHA\n";
                echo "❌ Erro: " . ($resultado['erro'] ?? 'Erro desconhecido') . "\n";
            }
            
            echo "\n3. TESTE DE GERAÇÃO DE SOP:\n";
            // Testar com prompt similar ao SOP
            $promptSop = "Crie um SOP simples em formato JSON com as seguintes chaves: objetivo, escopo, procedimentos. Responda apenas com JSON válido.";
            
            $resultadoSop = ApiHelper::chamarOpenAI($promptSop, $openaiModelo, true);
            
            if ($resultadoSop['sucesso']) {
                echo "✅ Geração de SOP: SUCESSO\n";
                $conteudo = $resultadoSop['conteudo'];
                if (isset($conteudo['objetivo']) && isset($conteudo['escopo']) && isset($conteudo['procedimentos'])) {
                    echo "✅ Estrutura SOP: VÁLIDA\n";
                } else {
                    echo "⚠️ Estrutura SOP: Campos esperados ausentes\n";
                    echo "Campos presentes: " . implode(', ', array_keys($conteudo)) . "\n";
                }
            } else {
                echo "❌ Geração de SOP: FALHA\n";
                echo "❌ Erro: " . ($resultadoSop['erro'] ?? 'Erro desconhecido') . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== FIM DEBUG API ===\n";
    }
    
    /**
     * Corrigir referências quebradas de SOPs
     */
    public function corrigirReferencias(): void
    {
        Auth::proteger();
        
        header('Content-Type: text/plain; charset=utf-8');
        echo "=== CORRIGINDO REFERÊNCIAS DE SOPs ===\n\n";
        
        try {
            // 1. Encontrar serviços com sop_id que não existem na tabela sops
            $servicosQuebrados = Database::query(
                "SELECT ss.id, ss.nome_servico, ss.sop_id 
                 FROM servicos_setor ss 
                 LEFT JOIN sops s ON ss.sop_id = s.id 
                 WHERE ss.sop_id IS NOT NULL 
                 AND s.id IS NULL 
                 AND ss.empresa_id = :empresa_id",
                ['empresa_id' => Auth::empresa()]
            );
            
            echo "1. SERVIÇOS COM REFERÊNCIAS QUEBRADAS:\n";
            if ($servicosQuebrados) {
                echo "Encontrados " . count($servicosQuebrados) . " serviços com referências quebradas:\n";
                foreach ($servicosQuebrados as $servico) {
                    echo "- Serviço: {$servico['nome_servico']} (ID: {$servico['id']}) -> SOP ID inexistente: {$servico['sop_id']}\n";
                }
                
                // Corrigir referências quebradas
                foreach ($servicosQuebrados as $servico) {
                    Database::execute(
                        "UPDATE servicos_setor SET sop_id = NULL, tem_sop = 0, status = 'detalhado' WHERE id = :id",
                        ['id' => $servico['id']]
                    );
                    echo "✅ Corrigido: {$servico['nome_servico']}\n";
                }
                
            } else {
                echo "✅ Nenhuma referência quebrada encontrada\n";
            }
            
            // 2. Encontrar SOPs órfãos (não referenciados por nenhum serviço)
            echo "\n2. SOPs ÓRFÃOS (sem referência de serviços):\n";
            $sopsOrfaos = Database::query(
                "SELECT s.id, s.titulo 
                 FROM sops s 
                 LEFT JOIN servicos_setor ss ON s.id = ss.sop_id 
                 WHERE ss.sop_id IS NULL 
                 AND s.empresa_id = :empresa_id",
                ['empresa_id' => Auth::empresa()]
            );
            
            if ($sopsOrfaos) {
                echo "Encontrados " . count($sopsOrfaos) . " SOPs órfãos:\n";
                foreach ($sopsOrfaos as $sop) {
                    echo "- SOP: {$sop['titulo']} (ID: {$sop['id']})\n";
                }
                echo "💡 Estes SOPs não têm serviços vinculados e podem ser removidos manualmente se necessário.\n";
            } else {
                echo "✅ Nenhum SOP órfão encontrado\n";
            }
            
            // 3. Atualizar contadores dos setores
            echo "\n3. ATUALIZANDO CONTADORES DOS SETORES:\n";
            $setores = Database::query(
                "SELECT id, nome_setor FROM setores_empresa WHERE empresa_id = :empresa_id",
                ['empresa_id' => Auth::empresa()]
            );
            
            foreach ($setores as $setor) {
                // Contar serviços
                $totalServicos = Database::queryOne(
                    "SELECT COUNT(*) as total FROM servicos_setor WHERE setor_id = :setor_id",
                    ['setor_id' => $setor['id']]
                )['total'];
                
                // Contar SOPs
                $totalSOPs = Database::queryOne(
                    "SELECT COUNT(*) as total FROM servicos_setor WHERE setor_id = :setor_id AND tem_sop = 1",
                    ['setor_id' => $setor['id']]
                )['total'];
                
                // Atualizar contadores
                Database::execute(
                    "UPDATE setores_empresa SET total_servicos = :total_servicos, total_sops = :total_sops WHERE id = :id",
                    ['total_servicos' => $totalServicos, 'total_sops' => $totalSOPs, 'id' => $setor['id']]
                );
                
                echo "✅ {$setor['nome_setor']}: {$totalServicos} serviços, {$totalSOPs} SOPs\n";
            }
            
            echo "\n✅ CORREÇÃO CONCLUÍDA!\n";
            echo "Recomendação: Recarregue a página de gerenciamento hierárquico.\n";
            
        } catch (Exception $e) {
            echo "❌ ERRO: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Regenerar SOP individual completamente
     */
    public function regenerarSopIndividual(): void
    {
        Auth::proteger();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $sopId = (int) ($input['sop_id'] ?? 0);
            
            if (!$sopId) {
                throw new Exception('ID do SOP não informado');
            }
            
            Logger::info('Iniciando regeneração de SOP', [
                'sop_id' => $sopId,
                'empresa_id' => Auth::empresa(),
                'usuario' => Auth::usuario()['nome']
            ]);
            
            // Buscar dados do SOP atual
            $sop = Database::queryOne(
                "SELECT s.*, ss.codigo_servico, ss.nome_servico, ss.detalhamento_completo, se.nome_setor 
                 FROM sops s
                 INNER JOIN servicos_setor ss ON s.id = ss.sop_id
                 INNER JOIN setores_empresa se ON ss.setor_id = se.id
                 WHERE s.id = :id AND s.empresa_id = :empresa_id",
                ['id' => $sopId, 'empresa_id' => Auth::empresa()]
            );
            
            if (!$sop) {
                throw new Exception('SOP não encontrado ou não está associado a um serviço');
            }
            
            // Obter dados da empresa
            $empresa = Database::queryOne(
                "SELECT * FROM empresas WHERE id = :id",
                ['id' => Auth::empresa()]
            );
            
            if (!$empresa) {
                throw new Exception('Empresa não encontrada');
            }
            
            // Decodificar detalhamento do serviço
            $detalhamento = json_decode($sop['detalhamento_completo'], true);
            if (!$detalhamento) {
                throw new Exception('Detalhamento do serviço não encontrado ou corrompido. Reprocesse o serviço primeiro.');
            }
            
            Logger::info('Dados preparados para regeneração', [
                'servico' => $sop['nome_servico'],
                'setor' => $sop['nome_setor'],
                'tem_detalhamento' => !empty($detalhamento)
            ]);
            
            // Gerar novo conteúdo SOP usando IA
            $novoConteudo = $this->gerarConteudoSopCompleto($detalhamento, $empresa, $sop);
            
            if (!$novoConteudo) {
                throw new Exception('Erro na geração do conteúdo pela IA. Verifique as configurações de API.');
            }
            
            // Incrementar versão
            $versaoAtual = $sop['versao'] ?? '1.0';
            $partesVersao = explode('.', $versaoAtual);
            $novaVersao = $partesVersao[0] . '.' . ((int)($partesVersao[1] ?? 0) + 1);
            
            // Atualizar SOP no banco
            Database::execute(
                "UPDATE sops SET conteudo = :conteudo, atualizado_em = NOW(), versao = :versao WHERE id = :id",
                [
                    'conteudo' => $novoConteudo, 
                    'versao' => $novaVersao,
                    'id' => $sopId
                ]
            );
            
            Logger::info('SOP regenerado com sucesso', [
                'sop_id' => $sopId,
                'nova_versao' => $novaVersao,
                'empresa_id' => Auth::empresa(),
                'usuario' => Auth::usuario()['nome']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => "SOP regenerado com sucesso! Nova versão: {$novaVersao}",
                'nova_versao' => $novaVersao
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao regenerar SOP', [
                'erro' => $e->getMessage(),
                'sop_id' => $sopId ?? 0,
                'empresa_id' => Auth::empresa(),
                'trace' => $e->getTraceAsString()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Método privado para gerar conteúdo SOP completo em DUAS FASES
     */
    private function gerarConteudoSopCompleto(array $detalhamento, array $empresa, array $sop): ?string
    {
        try {
            // Buscar dados do diagnóstico relacionado ao SOP
            $diagnostico = Database::queryOne(
                "SELECT * FROM diagnosticos WHERE empresa_id = :empresa_id ORDER BY id DESC LIMIT 1",
                ['empresa_id' => Auth::empresa()]
            );
            
            if (!$diagnostico) {
                throw new Exception('Diagnóstico não encontrado');
            }
            
            // Preparar dados do serviço no formato esperado pelo método
            $servicoData = [
                'nome_servico' => $sop['nome_servico'],
                'nome_setor' => $sop['nome_setor'],
                'codigo_servico' => $sop['codigo_servico']
            ];
            
            Logger::info('INICIANDO GERAÇÃO EM DUAS FASES', [
                'servico' => $servicoData['nome_servico'],
                'setor' => $servicoData['nome_setor']
            ]);
            
            // **FASE 1: PROCEDIMENTOS OPERACIONAIS DETALHADOS**
            Logger::info('FASE 1: Gerando procedimentos operacionais');
            $promptProcedimentos = $this->criarPromptProcedimentosOperacionais($servicoData, $detalhamento, $empresa, $diagnostico);
            
            $respostaProcedimentos = ApiHelper::chamarOpenAI($promptProcedimentos, 'gpt-4o', true);
            
            if (!$respostaProcedimentos['sucesso']) {
                Logger::error('Erro na FASE 1 - Procedimentos', [
                    'erro' => $respostaProcedimentos['erro'] ?? 'Erro desconhecido'
                ]);
                throw new Exception('Erro na Fase 1 (Procedimentos): ' . ($respostaProcedimentos['erro'] ?? 'Erro desconhecido'));
            }
            
            $procedimentosOperacionais = $respostaProcedimentos['conteudo'];
            Logger::info('FASE 1 CONCLUÍDA: Procedimentos operacionais gerados');
            
            // **FASE 2: SITUAÇÕES CRÍTICAS E EMERGENCIAIS**
            Logger::info('FASE 2: Gerando situações críticas');
            $promptCriticas = $this->criarPromptSituacoesCriticas($servicoData, $detalhamento, $empresa, $diagnostico);
            
            $respostaCriticas = ApiHelper::chamarOpenAI($promptCriticas, 'gpt-4o', true);
            
            if (!$respostaCriticas['sucesso']) {
                Logger::error('Erro na FASE 2 - Situações Críticas', [
                    'erro' => $respostaCriticas['erro'] ?? 'Erro desconhecido'
                ]);
                throw new Exception('Erro na Fase 2 (Situações Críticas): ' . ($respostaCriticas['erro'] ?? 'Erro desconhecido'));
            }
            
            $situacoesCriticas = $respostaCriticas['conteudo'];
            Logger::info('FASE 2 CONCLUÍDA: Situações críticas geradas', [
                'size_resposta' => strlen(json_encode($situacoesCriticas)),
                'tem_gestao_situacoes' => isset($situacoesCriticas['gestao_situacoes_criticas']),
                'keys_situacoes' => array_keys($situacoesCriticas)
            ]);
            
            // **COMBINAÇÃO DAS DUAS FASES**
            Logger::info('COMBINANDO as duas fases em SOP completo');
            
            // Debug detalhado das situações críticas recebidas
            Logger::info('DEBUG SITUAÇÕES CRÍTICAS RECEBIDAS', [
                'situacoes_raw_keys' => array_keys($situacoesCriticas),
                'tem_gestao_situacoes_criticas' => isset($situacoesCriticas['gestao_situacoes_criticas']),
                'estrutura_gestao_situacoes' => isset($situacoesCriticas['gestao_situacoes_criticas']) ? array_keys($situacoesCriticas['gestao_situacoes_criticas']) : null,
                'cenarios_detalhados_count' => count($situacoesCriticas['gestao_situacoes_criticas']['cenarios_criticos_detalhados'] ?? []),
                'scripts_especificos_count' => count($situacoesCriticas['gestao_situacoes_criticas']['scripts_situacoes_especificas'] ?? [])
            ]);
            
            // Combinar os dados das duas fases em um SOP único
            $sopCompleto = [
                // Dados principais dos procedimentos operacionais
                'sop_titulo' => $servicoData['codigo_servico'] . ' - ' . $servicoData['nome_servico'],
                'objetivo' => $procedimentosOperacionais['objetivo'] ?? 'Objetivo não especificado',
                'escopo' => $procedimentosOperacionais['escopo'] ?? 'Escopo não especificado',
                'responsaveis' => $procedimentosOperacionais['responsaveis'] ?? [],
                'competencias_requeridas' => $procedimentosOperacionais['competencias_operacionais_requeridas'] ?? [],
                'pre_requisitos' => $procedimentosOperacionais['pre_requisitos_operacionais'] ?? [],
                'recursos_necessarios' => $procedimentosOperacionais['recursos_operacionais_necessarios'] ?? [],
                
                // Procedimentos operacionais detalhados
                'procedimentos' => $procedimentosOperacionais['procedimentos_operacionais_detalhados'] ?? [],
                
                // Checklists operacionais
                'checklists' => $procedimentosOperacionais['checklists_operacionais'] ?? [],
                
                // Scripts de comunicação operacional
                'scripts_comunicacao' => $procedimentosOperacionais['scripts_comunicacao_operacionais'] ?? [],
                
                // Indicadores de performance
                'indicadores_performance' => $procedimentosOperacionais['indicadores_performance_operacionais'] ?? [],
                
                // **SITUAÇÕES CRÍTICAS E EMERGENCIAIS (FASE 2)**
                'gestao_situacoes_fora_controle' => [
                    'cenarios_criticos_obrigatorios' => $situacoesCriticas['gestao_situacoes_criticas']['cenarios_criticos_detalhados'] ?? [],
                    'scripts_situacoes_dificeis' => $situacoesCriticas['gestao_situacoes_criticas']['scripts_situacoes_especificas'] ?? []
                ],
                'matriz_riscos_servico' => $situacoesCriticas['matriz_riscos_servico'] ?? [],
                'treinamento_gestao_crises' => $situacoesCriticas['treinamento_gestao_crises'] ?? [],
                
                // Documentação
                'anexos_referencias' => $procedimentosOperacionais['documentacao_operacional'] ?? [],
                
                // Metadados
                'versao' => '2.0 - Duas Fases (Operacional + Críticas)',
                'data_criacao' => date('d/m/Y H:i:s'),
                'fases_geradas' => [
                    'fase_1_procedimentos' => 'Concluída',
                    'fase_2_situacoes_criticas' => 'Concluída'
                ]
            ];
            
            // Debug do mapeamento das situações críticas
            Logger::info('DEBUG MAPEAMENTO SITUAÇÕES CRÍTICAS FINAL', [
                'cenarios_mapeados' => count($situacoesCriticas['gestao_situacoes_criticas']['cenarios_criticos_detalhados'] ?? []),
                'scripts_mapeados' => count($situacoesCriticas['gestao_situacoes_criticas']['scripts_situacoes_especificas'] ?? []),
                'estrutura_final_tem_gestao' => isset($sopCompleto['gestao_situacoes_fora_controle']),
                'estrutura_final_tem_cenarios' => !empty($sopCompleto['gestao_situacoes_fora_controle']['cenarios_criticos_obrigatorios']),
                'estrutura_final_tem_scripts' => !empty($sopCompleto['gestao_situacoes_fora_controle']['scripts_situacoes_dificeis'])
            ]);
            
            // Validar se as seções essenciais estão presentes
            $secoesObrigatorias = ['objetivo', 'escopo', 'procedimentos', 'responsaveis'];
            $secoesFaltando = [];
            
            foreach ($secoesObrigatorias as $secao) {
                if (!isset($sopCompleto[$secao]) || empty($sopCompleto[$secao])) {
                    $secoesFaltando[] = $secao;
                }
            }
            
            if (!empty($secoesFaltando)) {
                Logger::warning('SOP combinado com seções faltando', [
                    'secoes_faltando' => $secoesFaltando
                ]);
                
                // Adicionar seções mínimas se necessário
                foreach ($secoesFaltando as $secao) {
                    switch ($secao) {
                        case 'procedimentos':
                            $sopCompleto['procedimentos'] = [
                                [
                                    'fase' => 'Execução Padrão',
                                    'descricao' => 'Execução principal do serviço',
                                    'passos_operacionais_detalhados' => [
                                        [
                                            'passo' => 1,
                                            'acao_operacional' => 'Executar processo conforme definição',
                                            'detalhamento_operacional_completo' => 'Seguir procedimentos específicos do setor com atenção aos padrões de qualidade.',
                                            'responsavel_operacional' => 'Responsável do Setor',
                                            'tempo_operacional_estimado' => '30min'
                                        ]
                                    ]
                                ]
                            ];
                            break;
                        case 'responsaveis':
                            $sopCompleto['responsaveis'] = [
                                'executor_principal' => 'Colaborador do Setor ' . $servicoData['nome_setor'],
                                'supervisor_operacional' => 'Supervisor do Setor ' . $servicoData['nome_setor'],
                                'aprovador_final' => 'Gestor do Setor ' . $servicoData['nome_setor']
                            ];
                            break;
                    }
                }
            }
            
            Logger::info('SOP COMPLETO gerado com sucesso em duas fases', [
                'total_procedimentos' => count($sopCompleto['procedimentos']),
                'tem_situacoes_criticas' => !empty($sopCompleto['gestao_situacoes_fora_controle']),
                'tem_cenarios_criticos' => !empty($sopCompleto['gestao_situacoes_fora_controle']['cenarios_criticos_obrigatorios']),
                'tem_scripts_dificeis' => !empty($sopCompleto['gestao_situacoes_fora_controle']['scripts_situacoes_dificeis']),
                'total_cenarios' => count($sopCompleto['gestao_situacoes_fora_controle']['cenarios_criticos_obrigatorios'] ?? []),
                'total_scripts' => count($sopCompleto['gestao_situacoes_fora_controle']['scripts_situacoes_dificeis'] ?? []),
                'size_kb' => round(strlen(json_encode($sopCompleto)) / 1024, 2)
            ]);
            
            // Retornar o JSON como string para salvar no banco
            return json_encode($sopCompleto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            Logger::error('Erro na geração de conteúdo SOP em duas fases', [
                'erro' => $e->getMessage(),
                'detalhamento_keys' => array_keys($detalhamento),
                'servico' => $servicoData ?? 'indefinido',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Criar prompt para PROCEDIMENTOS OPERACIONAIS DETALHADOS (Primeira Fase)
     */
    private function criarPromptProcedimentosOperacionais(array $servico, array $detalhamento, array $empresa, array $diagnostico): string
    {
        $nomeEmpresa = json_decode($diagnostico['respostas'], true)['nome_empresa'] ?? $empresa['nome'] ?? 'Empresa';
        $nomeServico = $servico['nome_servico'];
        $nomeSetor = $servico['nome_setor'];
        $codigoServico = $servico['codigo_servico'];
        
        return "GERAÇÃO DE SOP - FASE 1: PROCEDIMENTOS OPERACIONAIS DETALHADOS

# INFORMAÇÕES DO SERVIÇO
- **Código**: {$codigoServico}
- **Serviço**: {$nomeServico}
- **Setor**: {$nomeSetor}
- **Empresa**: {$nomeEmpresa}

# DETALHAMENTO COMPLETO DO SERVIÇO
" . json_encode($detalhamento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

# FOCO DESTA FASE: PROCEDIMENTOS OPERACIONAIS EXTENSOS

Esta é a **PRIMEIRA FASE** da geração do SOP. O foco é criar **PROCEDIMENTOS OPERACIONAIS ULTRA DETALHADOS** que sirvam como manual completo de treinamento operacional.

## INSTRUÇÕES CRÍTICAS:

### 1. **FOCO EXCLUSIVO EM PROCEDIMENTOS NORMAIS**
- **NÃO incluir** situações críticas ou emergências nesta fase
- **CONCENTRAR TODO O ESFORÇO** em detalhar os procedimentos operacionais normais
- **MÁXIMO DETALHAMENTO** para cada passo operacional
- **Scripts completos** para todas as interações normais

### 2. **DETALHAMENTO OPERACIONAL EXTENSO**
- **Mínimo 7-10 passos** por fase operacional
- **Mínimo 200 palavras** por passo detalhado
- **Scripts word-by-word** para todas as comunicações
- **Procedimentos passo-a-passo minuciosos**

## ESTRUTURA JSON PARA PROCEDIMENTOS OPERACIONAIS:

```json
{
  \"sop_titulo\": \"SOP {$codigoServico} - {$nomeServico} (Procedimentos Operacionais)\",
  \"objetivo\": \"OBRIGATÓRIO MÍNIMO 4 FRASES DETALHADAS: Objetivo operacional completo explicando propósito específico, resultados mensuráveis esperados, impacto direto no negócio e benefícios para o cliente operacional.\",
  \"escopo\": \"OBRIGATÓRIO MÍNIMO 4 FRASES DETALHADAS: Definição técnica completa do escopo operacional incluindo o que está incluído, o que está excluído, limitações específicas, interfaces com outros processos operacionais.\",
  \"responsaveis\": {
    \"executor_principal\": \"Cargo específico com competências operacionais requeridas\",
    \"supervisor_operacional\": \"Cargo de quem supervisiona operacionalmente e valida qualidade\",
    \"aprovador_final\": \"Cargo de quem aprova o resultado operacional\"
  },
  \"competencias_operacionais_requeridas\": [
    \"Competência operacional específica com nível requerido\",
    \"Conhecimento técnico detalhado com aplicação operacional\",
    \"Habilidade prática com contexto de uso operacional\",
    \"Certificação ou qualificação operacional se aplicável\"
  ],
  \"pre_requisitos_operacionais\": [
    \"Pré-requisito operacional específico com validação obrigatória\",
    \"Conhecimento operacional necessário com profundidade requerida\",
    \"Acesso/permissão específica operacional com justificativa\",
    \"Ferramenta/sistema operacional com versão e configuração\"
  ],
  \"recursos_operacionais_necessarios\": {
    \"sistemas_operacionais\": [\"Sistema específico operacional com função detalhada\", \"Plataforma operacional categoria X para finalidade Y\"],
    \"equipamentos_operacionais\": [\"Equipamento operacional com especificações\", \"Ferramenta operacional categoria X\"],
    \"documentos_operacionais\": [\"Formulário operacional específico com campos\", \"Template operacional X para situação Y\"],
    \"materiais_operacionais\": [\"Material físico operacional com especificação\", \"Insumo operacional categoria X\"]
  },
  \"procedimentos_operacionais_detalhados\": [
    {
      \"fase\": \"NOME DA FASE OPERACIONAL (ex: Preparação Operacional Inicial)\",
      \"descricao_operacional\": \"OBRIGATÓRIO MÍNIMO 3 FRASES: Descrição operacional detalhada da importância desta fase, objetivos operacionais específicos e impacto no resultado final.\",
      \"metodologia_operacional\": \"Framework operacional específico utilizado (ex: Metodologia PDCA Operacional, Framework ITIL Operacional)\",
      \"passos_operacionais_detalhados\": [
        {
          \"passo\": 1,
          \"acao_operacional\": \"TÍTULO DA AÇÃO OPERACIONAL ESPECÍFICA (ex: Verificar e validar dados operacionais do cliente)\",
          \"detalhamento_operacional_completo\": \"OBRIGATÓRIO MÍNIMO 200 PALAVRAS: Detalhar exatamente como executar operacionalmente passo-a-passo. Incluir: que ferramentas operacionais usar, que informações operacionais coletar, que validações operacionais realizar, como registrar operacionalmente, procedimentos de verificação operacional, métodos de conferência operacional. Para comunicação: scripts word-by-word completos, tom de voz, postura, perguntas específicas a fazer, como conduzir a conversa operacionalmente.\",
          \"responsavel_operacional\": \"Cargo operacional específico com nível de competência\",
          \"tempo_operacional_estimado\": \"Tempo operacional específico (ex: 8-12 minutos para casos normais, 15-20 para complexos)\",
          \"criterios_qualidade_operacionais\": \"MÍNIMO 4 CRITÉRIOS OPERACIONAIS ESPECÍFICOS: Como validar operacionalmente se foi executado com padrão profissional\",
          \"scripts_operacionais_completos\": \"OBRIGATÓRIO SCRIPTS WORD-BY-WORD OPERACIONAIS: Frases exatas de abertura operacional, desenvolvimento operacional e fechamento operacional. Para processos: comandos operacionais específicos, procedimentos operacionais detalhados\",
          \"metodologias_operacionais\": \"MÍNIMO 2 TÉCNICAS OPERACIONAIS: Técnicas operacionais profissionais, frameworks operacionais reconhecidos\",
          \"validacoes_operacionais\": \"MÍNIMO 4 VALIDAÇÕES OPERACIONAIS: Checklist operacional específico com critérios de aceitação mensuráveis\",
          \"ferramentas_operacionais\": \"Lista específica de ferramentas operacionais, sistemas operacionais e recursos operacionais necessários\",
          \"observacoes_operacionais\": \"MÍNIMO 3 DICAS OPERACIONAIS: Insights operacionais, armadilhas operacionais comuns, dicas operacionais avançadas\"
        }
      ]
    }
  ],
  \"checklists_operacionais\": {
    \"pre_execucao_operacional\": [
      \"MÍNIMO 5 ITENS: Item operacional específico com critério de validação operacional\",
      \"Verificação operacional detalhada com método operacional específico\"
    ],
    \"durante_execucao_operacional\": [
      \"MÍNIMO 5 ITENS: Controle operacional específico com frequência operacional\",
      \"Monitoramento operacional detalhado com métricas operacionais\"
    ],
    \"pos_execucao_operacional\": [
      \"MÍNIMO 5 ITENS: Validação operacional final específica\",
      \"Documentação operacional obrigatória detalhada\"
    ]
  },
  \"scripts_comunicacao_operacionais\": {
    \"abordagem_inicial_operacional\": \"MÍNIMO 150 PALAVRAS: Script operacional completo para primeiro contato operacional\",
    \"apresentacao_servico_operacional\": \"MÍNIMO 150 PALAVRAS: Modelo operacional de apresentação com argumentação operacional\",
    \"condução_processo_operacional\": \"MÍNIMO 200 PALAVRAS: Scripts operacionais para conduzir o processo operacional\",
    \"finalizacao_operacional\": \"MÍNIMO 100 PALAVRAS: Modelo operacional de encerramento com próximos passos operacionais\"
  },
  \"indicadores_performance_operacionais\": [
    {
      \"nome_kpi_operacional\": \"KPI operacional específico e mensurável\",
      \"formula_calculo_operacional\": \"Fórmula operacional exata de cálculo\",
      \"meta_operacional\": \"Meta operacional específica com justificativa\",
      \"frequencia_medicao_operacional\": \"Frequência operacional realista\",
      \"responsavel_medicao_operacional\": \"Quem mede operacionalmente e reporta\"
    }
  ],
  \"documentacao_operacional\": [
    \"Template operacional específico com campos operacionais\",
    \"Formulário operacional detalhado com validações operacionais\",
    \"Checklist operacional específico com critérios operacionais\"
  ],
  \"versao\": \"1.0 - Procedimentos Operacionais\",
  \"data_criacao\": \"" . date('d/m/Y') . "\"
}
```

## REQUISITOS OBRIGATÓRIOS PARA PROCEDIMENTOS OPERACIONAIS:

### 📋 **QUANTIDADE MÍNIMA OBRIGATÓRIA:**
- **MÍNIMO 4 fases operacionais** (Preparação, Execução Inicial, Execução Principal, Finalização)
- **MÍNIMO 7 passos operacionais** por fase
- **MÍNIMO 200 palavras** por detalhamento operacional de passo
- **MÍNIMO 5 scripts operacionais** de comunicação
- **MÍNIMO 4 critérios operacionais** de qualidade por passo

### 🎯 **FOCO OPERACIONAL EXCLUSIVO:**
- **Procedimentos normais** do dia-a-dia operacional
- **Fluxos padrão** de execução operacional
- **Comunicação rotineira** operacional
- **Validações normais** operacionais
- **Documentação padrão** operacional

### ⚠️ **ATENÇÃO - NÃO INCLUIR NESTA FASE:**
- Situações críticas ou emergenciais
- Gestão de crises
- Protocolos de emergência
- Escalações críticas
- Procedimentos de contenção

**IMPORTANTE**: Esta é apenas a PRIMEIRA FASE. Concentre todo o esforço em detalhar ao máximo os procedimentos operacionais normais. Uma segunda fase tratará das situações críticas separadamente.

**TERMINOLOGIA**: Use SEMPRE terminologia genérica. NUNCA mencione marcas comerciais.

Responda APENAS com o JSON válido dos procedimentos operacionais, sem explicações adicionais.";
    }

    /**
     * Criar prompt para SITUAÇÕES CRÍTICAS E EMERGENCIAIS (Segunda Fase)
     */
    private function criarPromptSituacoesCriticas(array $servico, array $detalhamento, array $empresa, array $diagnostico): string
    {
        $nomeEmpresa = json_decode($diagnostico['respostas'], true)['nome_empresa'] ?? $empresa['nome'] ?? 'Empresa';
        $nomeServico = $servico['nome_servico'];
        $nomeSetor = $servico['nome_setor'];
        $codigoServico = $servico['codigo_servico'];
        
        return "GERAÇÃO DE SOP - FASE 2: SITUAÇÕES CRÍTICAS E EMERGENCIAIS

# INFORMAÇÕES DO SERVIÇO
- **Código**: {$codigoServico}
- **Serviço**: {$nomeServico}
- **Setor**: {$nomeSetor}
- **Empresa**: {$nomeEmpresa}

# DETALHAMENTO COMPLETO DO SERVIÇO
" . json_encode($detalhamento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

# FOCO DESTA FASE: SITUAÇÕES CRÍTICAS E GESTÃO DE EMERGÊNCIAS

Esta é a **SEGUNDA FASE** da geração do SOP. O foco é criar **PROTOCOLOS ULTRA DETALHADOS** para todas as situações que podem fugir do controle normal.

## INSTRUÇÕES CRÍTICAS:

### 1. **FOCO EXCLUSIVO EM SITUAÇÕES CRÍTICAS**
- **MAPEAR TODAS** as situações que podem sair do controle
- **DETALHAR EXTENSAMENTE** cada cenário crítico
- **SCRIPTS WORD-BY-WORD** para comunicação em crises
- **PROTOCOLOS MINUCIOSOS** de emergência

### 2. **SITUAÇÕES CRÍTICAS OBRIGATÓRIAS POR CONTEXTO**
🔥 **ATENDIMENTO**: Cliente irritado, ameaças, reclamações públicas, constrangimentos
💰 **FINANCEIRO**: Inadimplência, disputas, cobranças difíceis, execução judicial
⚙️ **TÉCNICO**: Sistemas caídos, falhas massivas, ataques, equipamentos quebrados
📋 **PROCESSOS**: Erros graves, não conformidades, acidentes, perda de certificações
🚨 **GESTÃO**: Crises virais, greves, desastres, saída de pessoas-chave

## ESTRUTURA JSON PARA SITUAÇÕES CRÍTICAS:

```json
{
  \"sop_titulo\": \"SOP {$codigoServico} - {$nomeServico} (Situações Críticas)\",
  \"gestao_situacoes_criticas\": {
    \"cenarios_criticos_detalhados\": [
      {
        \"tipo_crise\": \"Categoria da situação crítica (atendimento, técnico, financeiro, operacional)\",
        \"situacao_especifica\": \"MÍNIMO 100 PALAVRAS: Descrição detalhada da situação que fugiu do controle normal com contexto específico\",
        \"sinais_identificacao\": [
          \"MÍNIMO 7 SINAIS ESPECÍFICOS de como identificar que a situação saiu do controle\",
          \"Indicadores comportamentais observáveis e mensuráveis\",
          \"Sinais técnicos concretos e verificáveis\",
          \"Métricas de alerta específicas do contexto\"
        ],
        \"acao_imediata_contencao\": \"MÍNIMO 200 PALAVRAS: Primeira ação passo-a-passo para conter imediatamente. Incluir postura física, tom de voz, primeiras frases exatas, movimento corporal, controle emocional próprio, técnicas de respiração se necessário\",
        \"script_comunicacao_crise\": \"MÍNIMO 300 PALAVRAS: Script word-by-word completo. Frases EXATAS de abertura (como iniciar), desenvolvimento (como conduzir), tratamento de objeções (como responder), técnicas de acalmar (frases específicas), fechamento (como encerrar). Incluir variações para diferentes níveis de gravidade\",
        \"tecnicas_desescalacao\": \"MÍNIMO 200 PALAVRAS: Metodologias psicológicas específicas - linguagem corporal (como se posicionar), tom de voz (volume e ritmo), técnicas de espelhamento (como aplicar), validação emocional (frases exatas), redirecionamento de foco (como desviar atenção), técnicas de ancoragem emocional\",
        \"niveis_gravidade\": {
          \"leve\": \"Como lidar quando situação está no início da escalada\",
          \"moderada\": \"Procedimentos quando situação está em desenvolvimento\",
          \"severa\": \"Protocolos para situação completamente fora de controle\"
        },
        \"quando_escalar\": \"CRITÉRIOS ESPECÍFICOS E MENSURÁVEIS: Sinais comportamentais exatos, tempo decorrido específico, nível de ameaça mensurável, impacto no negócio quantificado, indicadores de risco específicos\",
        \"procedimento_pos_crise\": \"MÍNIMO 150 PALAVRAS: Follow-up detalhado (quando fazer, como fazer), documentação específica (que campos preencher), análise de causa raiz (metodologia), melhorias preventivas (ações concretas), comunicação interna (para quem reportar)\",
        \"tempo_resposta_especifico\": \"Prazos específicos: contenção inicial (X minutos), escalação (X minutos), resolução (X minutos)\"
      }
    ],
    \"scripts_situacoes_especificas\": {
      \"cliente_extremamente_irritado\": \"MÍNIMO 400 PALAVRAS: Script ultra completo word-by-word. Abertura empática (frases exatas), investigação do problema (perguntas específicas), validação emocional (como validar), propostas de solução (como apresentar), técnicas de fechamento (frases de encerramento). Incluir variações para diferentes tipos de personalidade\",
      \"cobranca_cliente_resistente\": \"MÍNIMO 350 PALAVRAS: Abordagem completa passo-a-passo. Técnicas de aproximação (como iniciar), argumentação (argumentos específicos), negociação (técnicas de concessão), pressão psicológica positiva (como aplicar), propostas de acordo (modelos específicos)\",
      \"sistema_completamente_indisponivel\": \"MÍNIMO 300 PALAVRAS: Protocolo completo de comunicação. Como informar o problema (frases exatas), gerenciar expectativas (como explicar), oferecer alternativas (que opções dar), manter relacionamento (como preservar confiança), atualizações periódicas (frequência e conteúdo)\",
      \"erro_grave_empresa_cometeu\": \"MÍNIMO 350 PALAVRAS: Metodologia de recuperação completa. Como assumir responsabilidade (frases exatas), comunicar transparência (nível de detalhes), propor reparação (tipos de compensação), recuperar confiança (ações específicas), prevenir reincidência (garantias)\",
      \"prazo_critico_perdido\": \"MÍNIMO 250 PALAVRAS: Estratégias de comunicação específicas. Como comunicar atraso (timing e frases), justificar sem desculpas (técnicas de explicação), negociar nova data (como propor), manter credibilidade (ações de recuperação)\",
      \"qualidade_fortemente_questionada\": \"MÍNIMO 300 PALAVRAS: Técnicas de defesa profissional. Como manter autoridade técnica (postura e argumentos), apresentar evidências (que mostrar), reconhecer limitações quando necessário (como admitir), propor melhorias (ações específicas)\"
    },
    \"protocolos_emergencia_criticos\": {
      \"escalacao_imediata\": \"Quando e como acionar supervisão imediata com scripts exatos\",
      \"comunicacao_interna_crise\": \"Protocolos de comunicação interna durante emergências\",
      \"documentacao_obrigatoria_crise\": \"Que registros fazer obrigatoriamente em cada tipo de crise\",
      \"acompanhamento_pos_emergencia\": \"Procedimentos de follow-up após resolução de emergências\"
    }
  },
  \"matriz_riscos_servico\": [
    {
      \"risco_identificado\": \"Risco específico relacionado a este serviço\",
      \"probabilidade\": \"Alta/Média/Baixa com justificativa\",
      \"impacto\": \"Alto/Médio/Baixo com descrição específica\",
      \"sinais_antecipacao\": [\"Como identificar antes que vire crise\"],
      \"acoes_preventivas\": [\"Ações específicas para prevenir\"]
    }
  ],
  \"treinamento_gestao_crises\": {
    \"competencias_necessarias\": [\"Competência específica para gestão de crises deste serviço\"],
    \"simulacoes_recomendadas\": [\"Exercícios práticos para preparar a equipe\"],
    \"atualizacao_protocolos\": \"Frequência e método de atualização dos protocolos\"
  },
  \"versao\": \"1.0 - Situações Críticas\",
  \"data_criacao\": \"" . date('d/m/Y') . "\"
}
```

## REQUISITOS OBRIGATÓRIOS PARA SITUAÇÕES CRÍTICAS:

### 🚨 **QUANTIDADE MÍNIMA OBRIGATÓRIA:**
- **MÍNIMO 8 cenários críticos** detalhados por serviço
- **MÍNIMO 6 scripts específicos** para situações difíceis
- **MÍNIMO 300 palavras** por script de situação crítica
- **MÍNIMO 5 níveis de escalação** com critérios específicos

### 🎯 **DETALHAMENTO CRÍTICO OBRIGATÓRIO:**
- **Scripts word-by-word**: Frases exatas, não resumos
- **Técnicas psicológicas**: Metodologias científicas específicas
- **Critérios mensuráveis**: Indicadores quantificáveis de escalação
- **Múltiplos níveis**: Leve, moderada, severa para cada situação
- **Pós-crise**: Procedimentos completos de recovery

### ⚡ **SITUAÇÕES OBRIGATÓRIAS A MAPEAR:**
- Cliente agressivo ou ameaçador
- Sistemas indisponíveis por horas
- Cobrança de inadimplente resistente
- Erro grave que prejudicou cliente
- Prazo crítico não cumprido
- Qualidade questionada publicamente
- Vazamento de informações
- Acidente ou problema de segurança

**TERMINOLOGIA**: Use SEMPRE terminologia genérica. NUNCA mencione marcas comerciais.

Responda APENAS com o JSON válido das situações críticas, sem explicações adicionais.";
    }

    /**
     * Buscar progresso da hierarquia
     */
    private function buscarProgressoHierarquia(int $estruturaId): ?array
    {
        return Database::queryOne(
            "SELECT * FROM progresso_hierarquico WHERE estrutura_id = ?",
            [$estruturaId]
        );
    }
    
    /**
     * Buscar hierarquia completa com setores e serviços
     */
    private function buscarHierarquiaCompleta(int $estruturaId): array
    {
        try {
            Logger::info('INICIANDO buscarHierarquiaCompleta', ['estrutura_id' => $estruturaId]);
            
            // Buscar estrutura básica
            $estrutura = $this->buscarEstruturaTemporaria($estruturaId);
            if (!$estrutura) {
                Logger::warning('Estrutura não encontrada', ['estrutura_id' => $estruturaId]);
                return [];
            }
            
            // Buscar setores organizados
            $setores = Database::query(
                "SELECT DISTINCT
                    s.id,
                    s.nome_setor,
                    s.tipo_setor,
                    s.descricao,
                    COUNT(sm.id) as total_servicos,
                    COUNT(CASE WHEN sm.status = 'sop_gerado' OR sm.status = 'aprovado' THEN 1 END) as com_sop
                 FROM setores_organizacionais s
                 LEFT JOIN servicos_mapeados sm ON s.id = sm.setor_id
                 WHERE s.estrutura_id = :estrutura_id
                 GROUP BY s.id, s.nome_setor, s.tipo_setor, s.descricao
                 ORDER BY 
                    CASE s.tipo_setor
                        WHEN 'core' THEN 1
                        WHEN 'apoio' THEN 2
                        WHEN 'estrategico' THEN 3
                        ELSE 4
                    END,
                    s.nome_setor",
                ['estrutura_id' => $estruturaId]
            );
            
            Logger::info('SETORES ENCONTRADOS', ['total' => count($setores)]);
            
            $hierarquia = [
                'estrutura' => $estrutura,
                'setores' => []
            ];
            
            foreach ($setores as $setor) {
                // Buscar serviços do setor
                $servicos = Database::query(
                    "SELECT 
                        sm.*,
                        s.sop_id,
                        CASE 
                            WHEN s.id IS NOT NULL THEN s.status
                            ELSE sm.status
                        END as status_final
                     FROM servicos_mapeados sm
                     LEFT JOIN sops s ON sm.id = s.servico_id
                     WHERE sm.setor_id = :setor_id
                     ORDER BY sm.categoria, sm.nome_servico",
                    ['setor_id' => $setor['id']]
                );
                
                Logger::info('SERVIÇOS DO SETOR', [
                    'setor' => $setor['nome_setor'],
                    'total_servicos' => count($servicos)
                ]);
                
                $hierarquia['setores'][] = [
                    'id' => $setor['id'],
                    'nome_setor' => $setor['nome_setor'],
                    'tipo_setor' => $setor['tipo_setor'],
                    'descricao' => $setor['descricao'],
                    'stats' => [
                        'total_servicos' => $setor['total_servicos'],
                        'com_sop' => $setor['com_sop']
                    ],
                    'servicos' => array_map(function($servico) {
                        return [
                            'id' => $servico['id'],
                            'nome_servico' => $servico['nome_servico'],
                            'codigo_servico' => $servico['codigo_servico'],
                            'categoria' => $servico['categoria'],
                            'criticidade' => $servico['criticidade'],
                            'frequencia' => $servico['frequencia'],
                            'status' => $servico['status_final'] ?? $servico['status'],
                            'sop_id' => $servico['sop_id']
                        ];
                    }, $servicos)
                ];
            }
            
            Logger::info('HIERARQUIA COMPLETA CRIADA', [
                'total_setores' => count($hierarquia['setores']),
                'total_servicos' => array_sum(array_column($hierarquia['setores'], 'stats'))
            ]);
            
            return $hierarquia;
            
        } catch (Exception $e) {
            Logger::error('ERRO em buscarHierarquiaCompleta', [
                'erro' => $e->getMessage(),
                'estrutura_id' => $estruturaId,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    /**
     * Criar prompt para detalhamento de serviço
     */
    private function criarPromptDetalhamentoServico(array $servico, array $contextoSetor, array $respostasDiagnostico): string
    {
        $nomeEmpresa = $respostasDiagnostico['nome_empresa'] ?? 'Empresa';
        $segmento = $respostasDiagnostico['segmento'] ?? 'Geral';
        $nomeSetor = $servico['nome_setor'];
        $nomeServico = $servico['nome_servico'];
        
        return "DETALHAMENTO PROFISSIONAL DE SERVIÇO EMPRESARIAL

# CONTEXTO DA EMPRESA
- **Nome**: {$nomeEmpresa}
- **Segmento**: {$segmento}
- **Setor em análise**: {$nomeSetor}
- **Serviço para detalhar**: {$nomeServico}

# INFORMAÇÕES DO DIAGNÓSTICO
" . $this->extrairInformacoesRelevantes($respostasDiagnostico) . "

# CONTEXTO DO SETOR
" . json_encode($contextoSetor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

# TAREFA
Você precisa detalhar PROFUNDAMENTE o serviço '{$nomeServico}' no setor '{$nomeSetor}' desta empresa específica.

## ESTRUTURA OBRIGATÓRIA DA RESPOSTA (JSON):
```json
{
  \"servico\": \"{$nomeServico}\",
  \"setor\": \"{$nomeSetor}\",
  \"objetivo_principal\": \"[Qual o objetivo principal deste serviço?]\",
  \"responsabilidades\": [
    \"[Lista das principais responsabilidades]\",
    \"[Cada responsabilidade em um item]\"
  ],
  \"processos_detalhados\": [
    {
      \"nome_processo\": \"[Nome do processo]\",
      \"descricao\": \"[Descrição detalhada]\",
      \"etapas\": [
        \"[Etapa 1: descrição]\",
        \"[Etapa 2: descrição]\",
        \"[Etapa N: descrição]\"
      ],
      \"recursos_necessarios\": [\"[Lista de recursos]\"],
      \"tempo_estimado\": \"[Tempo estimado]\",
      \"frequencia\": \"[diária/semanal/mensal/sob_demanda]\"
    }
  ],
  \"integracao_setores\": [
    {
      \"setor\": \"[Nome do setor]\",
      \"tipo_integracao\": \"[entrada/saída/bidirecional]\",
      \"descricao\": \"[Como interagem]\"
    }
  ],
  \"recursos_principais\": [
    {
      \"tipo\": \"[sistema/ferramenta/pessoa/documento]\",
      \"nome\": \"[Nome do recurso]\",
      \"funcao\": \"[Para que é usado]\"
    }
  ],
  \"problemas_comuns\": [
    {
      \"problema\": \"[Descrição do problema]\",
      \"impacto\": \"[Alto/Médio/Baixo]\",
      \"solucao_nivel1\": \"[Solução imediata/operacional]\",
      \"solucao_nivel2\": \"[Solução tática/supervisão]\",
      \"solucao_nivel3\": \"[Solução estratégica/direção]\"
    }
  ],
  \"indicadores_desempenho\": [
    {
      \"kpi\": \"[Nome do KPI]\",
      \"unidade_medida\": \"[%/unidades/tempo/valor]\",
      \"meta_sugerida\": \"[Valor meta]\",
      \"frequencia_medicao\": \"[Frequência]\"
    }
  ],
  \"nivel_criticidade\": \"[Alta/Média/Baixa]\",
  \"complexidade\": \"[Simples/Média/Alta]\",
  \"observacoes_especiais\": \"[Observações específicas desta empresa]\"
}
```

**IMPORTANTE**:
- Baseie-se nas informações REAIS da empresa
- Seja ESPECÍFICO para o segmento {$segmento}
- Considere o porte e características desta empresa
- Forneça soluções PRÁTICAS e aplicáveis
- Use linguagem profissional mas acessível
- RESPONDA APENAS COM O JSON VÁLIDO, SEM EXPLICAÇÕES ADICIONAIS";
    }
    
    /**
     * Criar prompt para geração de SOP baseado no detalhamento
     */
    private function criarPromptGeracaoSopDetalhado(array $servico, array $detalhamento, array $diagnostico): string
    {
        $nomeEmpresa = json_decode($diagnostico['respostas'], true)['nome_empresa'] ?? 'Empresa';
        $nomeServico = $servico['nome_servico'];
        $nomeSetor = $servico['nome_setor'];
        $codigoServico = $servico['codigo_servico'];
        
        return "GERAÇÃO DE SOP (PROCEDIMENTO OPERACIONAL PADRÃO)

# INFORMAÇÕES DO SERVIÇO
- **Código**: {$codigoServico}
- **Serviço**: {$nomeServico}
- **Setor**: {$nomeSetor}
- **Empresa**: {$nomeEmpresa}

# DETALHAMENTO COMPLETO DO SERVIÇO
" . json_encode($detalhamento, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

# TAREFA
Crie um SOP (Standard Operating Procedure) COMPLETO e PROFISSIONAL para este serviço.

**REQUISITOS IMPORTANTES**:
- Use o detalhamento fornecido como base
- Linguagem clara e objetiva  
- Passos numerados e detalhados
- Considere o contexto REAL da empresa
- Incluir pontos de controle de qualidade
- Definir responsabilidades claras
- Prever situações de exceção baseadas no detalhamento
- Foco na aplicabilidade prática

Responda APENAS com o JSON válido do SOP completo, sem explicações adicionais";
    }
    
    /**
     * Processar resposta de detalhamento da IA
     */
    private function processarRespostaDetalhamentoIA(string $respostaIA, array $servico): array
    {
        // Tentar extrair JSON da resposta
        $json = $this->extrairJsonDaResposta($respostaIA);
        
        if ($json) {
            return $json;
        }
        
        // Se não conseguir extrair JSON, criar estrutura básica
        Logger::warning('Não foi possível extrair JSON do detalhamento', [
            'servico_id' => $servico['id'],
            'resposta_ia' => substr($respostaIA, 0, 500)
        ]);
        
        return [
            'servico' => $servico['nome_servico'],
            'setor' => $servico['nome_setor'],
            'objetivo_principal' => 'Executar ' . $servico['nome_servico'] . ' com qualidade',
            'responsabilidades' => ['Executar procedimentos padrão'],
            'processos_detalhados' => [],
            'nivel_criticidade' => 'Média'
        ];
    }
    
    /**
     * Método de debug para testar geração de situações críticas
     */
    public function debugSituacoesCriticas(): void
    {
        Auth::proteger();
        
        // Dados de teste
        $servicoTeste = [
            'nome_servico' => 'Atendimento ao Cliente',
            'nome_setor' => 'Comercial',
            'codigo_servico' => 'COM-ATE-001'
        ];
        
        $detalhamentoTeste = [
            'objetivo_principal' => 'Atender clientes com excelência',
            'responsabilidades' => ['Atender chamadas', 'Resolver problemas'],
            'nivel_criticidade' => 'Alta'
        ];
        
        $empresaTeste = ['nome' => 'Empresa Teste'];
        
        $diagnosticoTeste = [
            'respostas' => json_encode(['nome_empresa' => 'Empresa Teste'])
        ];
        
        try {
            Logger::info('INICIANDO DEBUG - Geração de Situações Críticas');
            
            $prompt = $this->criarPromptSituacoesCriticas($servicoTeste, $detalhamentoTeste, $empresaTeste, $diagnosticoTeste);
            
            Logger::info('PROMPT GERADO', [
                'prompt_size' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 500)
            ]);
            
            $resposta = ApiHelper::chamarOpenAI($prompt, 'gpt-4o', true);
            
            Logger::info('RESPOSTA DA API', [
                'sucesso' => $resposta['sucesso'],
                'tem_conteudo' => !empty($resposta['conteudo']),
                'tipo_conteudo' => gettype($resposta['conteudo']),
                'keys_resposta' => is_array($resposta['conteudo']) ? array_keys($resposta['conteudo']) : 'não é array',
                'erro' => $resposta['erro']
            ]);
            
            if ($resposta['sucesso'] && !empty($resposta['conteudo'])) {
                $situacoes = $resposta['conteudo'];
                
                $analise = [
                    'tem_gestao_situacoes' => isset($situacoes['gestao_situacoes_criticas']),
                    'tem_cenarios' => isset($situacoes['gestao_situacoes_criticas']['cenarios_criticos_detalhados']),
                    'tem_scripts' => isset($situacoes['gestao_situacoes_criticas']['scripts_situacoes_especificas']),
                    'total_cenarios' => count($situacoes['gestao_situacoes_criticas']['cenarios_criticos_detalhados'] ?? []),
                    'total_scripts' => count($situacoes['gestao_situacoes_criticas']['scripts_situacoes_especificas'] ?? [])
                ];
                
                Logger::info('ANÁLISE DA ESTRUTURA DE SITUAÇÕES CRÍTICAS', $analise);
                
                echo json_encode([
                    'debug' => true,
                    'resposta_api' => $resposta,
                    'analise_estrutura' => $analise,
                    'estrutura_completa' => $situacoes
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'debug' => true,
                    'erro' => 'Falha na geração',
                    'detalhes' => $resposta
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro no debug de situações críticas', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            echo json_encode([
                'debug' => true,
                'erro' => $e->getMessage()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    /**
     * Interface principal de gerenciamento hierárquico
     */
    public function gerenciarHierarquia(): void
    {
        try {
            Logger::info('ACESSANDO gerenciarHierarquia', [
                'url_params' => $_GET,
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id()
            ]);
            
            Auth::proteger();
            
            Logger::info('AUTENTICAÇÃO OK - prosseguindo', [
                'diagnostico_id' => $_GET['diagnostico_id'] ?? 'não informado',
                'estrutura_id' => $_GET['estrutura_id'] ?? 'não informado'
            ]);

            $diagnosticoId = (int) ($_GET['diagnostico_id'] ?? 0);
            $estruturaId = (int) ($_GET['estrutura_id'] ?? 0);
            
            Logger::info('PARÂMETROS PROCESSADOS', [
                'diagnostico_id' => $diagnosticoId,
                'estrutura_id' => $estruturaId
            ]);
            
            if (!$diagnosticoId || !$estruturaId) {
                Logger::warning('PARÂMETROS INVÁLIDOS', [
                    'diagnostico_id' => $diagnosticoId,
                    'estrutura_id' => $estruturaId
                ]);
                Flash::set('erro', 'Parâmetros obrigatórios não informados.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }

            // Buscar dados da estrutura
            Logger::info('BUSCANDO ESTRUTURA TEMPORÁRIA');
            $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
            
            if (!$estruturaData) {
                Logger::warning('ESTRUTURA NÃO ENCONTRADA', ['estrutura_id' => $estruturaId]);
                // Se não encontrou a estrutura, pode ser que o processo não foi iniciado ainda
                // Vamos redirecionar para a página inicial do SOP para iniciar o processo
                Flash::set('info', 'Estrutura não encontrada. Inicie o processo de geração de SOPs primeiro.');
                header('Location: ' . APP_URL . '/sop?diagnostico_id=' . $diagnosticoId);
                exit;
            }
            
            Logger::info('ESTRUTURA ENCONTRADA - BUSCANDO DIAGNÓSTICO');

            // Buscar diagnóstico
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
            
            if (!$diagnostico) {
                Logger::warning('DIAGNÓSTICO NÃO ENCONTRADO', ['diagnostico_id' => $diagnosticoId]);
                Flash::set('erro', 'Diagnóstico não encontrado.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }
            
            Logger::info('DIAGNÓSTICO ENCONTRADO - VERIFICANDO PERMISSÕES');

            // Verificar permissão
            if (Auth::perfil() !== 'ADMIN_HOLDING' && $diagnostico['usuario_id'] != Auth::id()) {
                Logger::warning('PERMISSÃO NEGADA', [
                    'user_perfil' => Auth::perfil(),
                    'user_id' => Auth::id(),
                    'diagnostico_usuario_id' => $diagnostico['usuario_id']
                ]);
                Flash::set('erro', 'Sem permissão para acessar este diagnóstico.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }
            
            Logger::info('PERMISSÕES OK - PREPARANDO DADOS PARA VIEW');

            // Buscar hierarquia completa se a estrutura existir
            $hierarquia = [];
            $estruturaExiste = false;
            
            if (!empty($estruturaData['estrutura'])) {
                $estruturaExiste = true;
                $hierarquia = $this->buscarHierarquiaCompleta($estruturaId);
                Logger::info('HIERARQUIA CARREGADA', [
                    'total_setores' => count($hierarquia['setores'] ?? [])
                ]);
            }

            $dados = [
                'diagnostico' => $diagnostico,
                'diagnostico_id' => $diagnosticoId,
                'estrutura_id' => $estruturaId,
                'estrutura_existe' => $estruturaExiste,
                'estrutura' => $estruturaData['estrutura'] ?? [],
                'hierarquia' => $hierarquia,
                'progresso' => $this->buscarProgressoHierarquia($estruturaId) ?? [],
                'csrf_token' => Csrf::gerar()
            ];
            
            Logger::info('DADOS PREPARADOS - CARREGANDO VIEW', [
                'view_file' => 'app/Views/sop/gerenciar-hierarquia.php',
                'estrutura_keys' => array_keys($estruturaData['estrutura'] ?? [])
            ]);

            require VIEW_PATH . '/sop/gerenciar-hierarquia.php';
            
            Logger::info('VIEW CARREGADA COM SUCESSO');
            
        } catch (Exception $e) {
            Logger::error('ERRO CRÍTICO em gerenciarHierarquia', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'diagnostico_id' => $diagnosticoId ?? 'indefinido',
                'estrutura_id' => $estruturaId ?? 'indefinido',
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Em caso de erro crítico, exibir página de erro amigável
            http_response_code(500);
            echo "<h1>Erro Interno</h1>";
            echo "<p>Ocorreu um erro inesperado: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='" . APP_URL . "/sop'>Voltar aos SOPs</a></p>";
            exit;
        }
    }
    
    /**
     * Ver detalhes completos de um serviço
     */
    public function verDetalhesServico(): void
    {
        try {
            Logger::info('ACESSANDO verDetalhesServico', [
                'url_params' => $_GET,
                'user_id' => Auth::id(),
                'servico_id' => $_GET['servico_id'] ?? 'não informado'
            ]);
            
            Auth::proteger();
            
            $servicoId = (int) ($_GET['servico_id'] ?? 0);
            
            Logger::info('PARÂMETROS PROCESSADOS', ['servico_id' => $servicoId]);
            
            if (!$servicoId) {
                Logger::warning('ID DO SERVIÇO NÃO INFORMADO');
                Flash::set('erro', 'ID do serviço não informado.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }

            Logger::info('BUSCANDO DADOS DO SERVIÇO');
            
            // Buscar dados do serviço completo com JOINs para obter todas as informações necessárias
            $servico = Database::queryOne(
                "SELECT 
                    sm.*,
                    so.nome_setor,
                    so.tipo_setor,
                    eh.nicho,
                    e.nome as nome_empresa,
                    s.id as sop_id,
                    s.status as sop_status,
                    s.criado_em as sop_gerado_em,
                    COALESCE(sd.detalhamento, '') as detalhamento_json_raw
                 FROM servicos_mapeados sm
                 LEFT JOIN setores_organizacionais so ON sm.setor_id = so.id
                 LEFT JOIN estruturas_hierarquicas eh ON so.estrutura_id = eh.id
                 LEFT JOIN empresas e ON sm.empresa_id = e.id
                 LEFT JOIN sops s ON sm.id = s.servico_id
                 LEFT JOIN servicos_detalhados sd ON sm.id = sd.servico_id
                 WHERE sm.id = :id 
                   AND sm.empresa_id = :empresa_id",
                [
                    'id' => $servicoId,
                    'empresa_id' => Auth::empresa()
                ]
            );
            
            Logger::info('BUSCA COMPLETA EM servicos_mapeados', [
                'encontrado' => !empty($servico),
                'empresa_id' => Auth::empresa()
            ]);
            
            // Se não encontrar em servicos_mapeados, tentar em servicos_detalhados
            if (!$servico) {
                Logger::info('TENTANDO servicos_detalhados');
                $servico = Database::queryOne(
                    "SELECT 
                        sd.*,
                        so.nome_setor,
                        so.tipo_setor,
                        eh.nicho,
                        e.nome as nome_empresa,
                        s.id as sop_id,
                        s.status as sop_status,
                        s.criado_em as sop_gerado_em,
                        sd.detalhamento as detalhamento_json_raw
                     FROM servicos_detalhados sd
                     LEFT JOIN setores_organizacionais so ON sd.setor_id = so.id
                     LEFT JOIN estruturas_hierarquicas eh ON so.estrutura_id = eh.id
                     LEFT JOIN empresas e ON sd.empresa_id = e.id
                     LEFT JOIN sops s ON sd.servico_id = s.servico_id
                     WHERE sd.id = :id 
                       AND sd.empresa_id = :empresa_id",
                    [
                        'id' => $servicoId,
                        'empresa_id' => Auth::empresa()
                    ]
                );
                
                Logger::info('BUSCA EM servicos_detalhados', [
                    'encontrado' => !empty($servico)
                ]);
            }
            
            if (!$servico) {
                Logger::warning('SERVIÇO NÃO ENCONTRADO EM NENHUMA TABELA', [
                    'servico_id' => $servicoId,
                    'empresa_id' => Auth::empresa()
                ]);
                Flash::set('erro', 'Serviço não encontrado. Verifique se o serviço existe e se você tem permissão para acessá-lo.');
                header('Location: ' . APP_URL . '/sop');
                exit;
            }
            
            Logger::info('SERVIÇO ENCONTRADO - PROCESSANDO DETALHAMENTO');

            // Processar e enriquecer dados do serviço
            if (!empty($servico['detalhamento_json_raw'])) {
                $detalhamento = json_decode($servico['detalhamento_json_raw'], true);
                if ($detalhamento) {
                    $servico['detalhamento_json'] = $detalhamento;
                    Logger::info('DETALHAMENTO DECODIFICADO', [
                        'detalhamento_keys' => array_keys($detalhamento)
                    ]);
                }
            }
            
            // Garantir campos obrigatórios com valores padrão
            $servico = array_merge([
                'codigo_servico' => 'N/A',
                'nome_servico' => 'Serviço sem nome',
                'categoria' => 'operacional',
                'criticidade' => 'media',
                'frequencia' => 'diaria',
                'complexidade' => 'media',
                'origem' => 'manual',
                'status' => 'mapeado',
                'nome_setor' => 'Não definido',
                'tipo_setor' => 'operacional',
                'nicho' => 'geral',
                'nome_empresa' => 'Empresa',
                'descricao_resumida' => '',
                'audio_transcricao' => '',
                'detalhamento_json' => null,
                'sop_id' => null,
                'criado_em' => date('Y-m-d H:i:s'),
                'detalhado_em' => null,
                'sop_gerado_em' => null,
                'atualizado_em' => null,
                'diagnostico_id' => $servico['estrutura_id'] ?? 0
            ], $servico);
            
            Logger::info('DADOS DO SERVIÇO PROCESSADOS', [
                'servico_id' => $servico['id'],
                'nome' => $servico['nome_servico'],
                'setor' => $servico['nome_setor'],
                'status' => $servico['status'],
                'tem_detalhamento' => !empty($servico['detalhamento_json'])
            ]);
            
            Logger::info('BUSCANDO SOP RELACIONADO');

            // Buscar SOP relacionado se existir
            $sop = null;
            if ($servico['sop_id']) {
                $sop = Database::queryOne(
                    "SELECT * FROM sops WHERE id = :sop_id AND empresa_id = :empresa_id",
                    [
                        'sop_id' => $servico['sop_id'],
                        'empresa_id' => Auth::empresa()
                    ]
                );
                
                Logger::info('BUSCA DE SOP POR ID', [
                    'sop_id' => $servico['sop_id'],
                    'sop_encontrado' => !empty($sop)
                ]);
            }
            
            // Se não encontrou pelo ID, tentar buscar por servico_id
            if (!$sop) {
                $sop = Database::queryOne(
                    "SELECT * FROM sops WHERE servico_id = :servico_id AND empresa_id = :empresa_id",
                    [
                        'servico_id' => $servicoId,
                        'empresa_id' => Auth::empresa()
                    ]
                );
                
                Logger::info('BUSCA DE SOP POR SERVICO_ID', [
                    'servico_id' => $servicoId,
                    'sop_encontrado' => !empty($sop)
                ]);
            }

            if ($sop && !empty($sop['conteudo'])) {
                $sopConteudo = json_decode($sop['conteudo'], true);
                if ($sopConteudo) {
                    $sop['conteudo_array'] = $sopConteudo;
                    Logger::info('CONTEÚDO SOP DECODIFICADO', [
                        'sop_keys' => array_keys($sopConteudo)
                    ]);
                }
            }

            $dados = [
                'servico' => $servico,
                'sop' => $sop,
                'csrf_token' => Csrf::gerar()
            ];
            
            Logger::info('CARREGANDO VIEW ver-detalhes-servico.php');

            require VIEW_PATH . '/sop/ver-detalhes-servico.php';
            
            Logger::info('VIEW CARREGADA COM SUCESSO');
            
        } catch (Exception $e) {
            Logger::error('ERRO CRÍTICO em verDetalhesServico', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'servico_id' => $servicoId ?? 'indefinido',
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            // Em caso de erro crítico, exibir página de erro amigável
            http_response_code(500);
            echo "<h1>Erro Interno</h1>";
            echo "<p>Ocorreu um erro inesperado: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><a href='" . APP_URL . "/sop'>Voltar aos SOPs</a></p>";
            exit;
        }
    }
    
    /**
     * Método de teste para verificar se o roteamento está funcionando
     */
    public function testeRota(): void
    {
        // Não usar Auth::proteger() para testar roteamento básico
        echo json_encode([
            'status' => 'sucesso',
            'timestamp' => date('Y-m-d H:i:s'),
            'rota' => 'sop/teste-rota',
            'metodo' => __METHOD__,
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::check() ? Auth::id() : null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'não definido',
            'get_params' => $_GET
        ]);
        exit;
    }

    /**
     * Debug específico para gerenciarHierarquia - sem Auth para teste
     */
    public function debugGerenciarHierarquia(): void
    {
        try {
            Logger::info('DEBUG gerenciarHierarquia INICIADO', [
                'get_params' => $_GET,
                'post_params' => $_POST,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'não definido',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'não definido'
            ]);

            // Não usar Auth::proteger() temporariamente para debug
            $diagnosticoId = (int) ($_GET['diagnostico_id'] ?? 0);
            $estruturaId = (int) ($_GET['estrutura_id'] ?? 0);
            
            $result = [
                'status' => 'debug_success',
                'timestamp' => date('Y-m-d H:i:s'),
                'diagnostico_id' => $diagnosticoId,
                'estrutura_id' => $estruturaId,
                'auth_status' => Auth::check() ? 'authenticated' : 'not_authenticated',
                'user_id' => Auth::check() ? Auth::id() : null
            ];

            if ($diagnosticoId && $estruturaId) {
                // Tentar buscar estrutura
                $estruturaData = $this->buscarEstruturaTemporaria($estruturaId);
                $result['estrutura_encontrada'] = !empty($estruturaData);
                
                if ($estruturaData) {
                    $result['estrutura_keys'] = array_keys($estruturaData);
                }
                
                // Tentar buscar diagnóstico
                $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
                $result['diagnostico_encontrado'] = !empty($diagnostico);
                
                if ($diagnostico) {
                    $result['diagnostico_empresa_id'] = $diagnostico['empresa_id'];
                    $result['diagnostico_usuario_id'] = $diagnostico['usuario_id'];
                }
            }
            
            Logger::info('DEBUG gerenciarHierarquia RESULT', $result);
            
            echo json_encode($result, JSON_PRETTY_PRINT);
            exit;
            
        } catch (Exception $e) {
            Logger::error('ERRO no DEBUG gerenciarHierarquia', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            echo json_encode([
                'status' => 'debug_error',
                'erro' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Debug específico para verDetalhesServico - sem Auth para teste
     */
    public function debugVerDetalhesServico(): void
    {
        try {
            Logger::info('DEBUG verDetalhesServico INICIADO', [
                'get_params' => $_GET,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'não definido'
            ]);

            // Não usar Auth::proteger() temporariamente para debug
            $servicoId = (int) ($_GET['servico_id'] ?? 0);
            
            $result = [
                'status' => 'debug_success',
                'timestamp' => date('Y-m-d H:i:s'),
                'servico_id' => $servicoId,
                'auth_status' => Auth::check() ? 'authenticated' : 'not_authenticated',
                'user_id' => Auth::check() ? Auth::id() : null
            ];

            if ($servicoId) {
                // Tentar buscar serviço em servicos_detalhados
                $servico = Database::queryOne(
                    "SELECT * FROM servicos_detalhados WHERE id = :id",
                    ['id' => $servicoId]
                );
                
                $result['servico_encontrado_detalhados'] = !empty($servico);
                
                if (!$servico) {
                    // Tentar em servicos_mapeados
                    $servico = Database::queryOne(
                        "SELECT * FROM servicos_mapeados WHERE id = :id",
                        ['id' => $servicoId]
                    );
                    $result['servico_encontrado_mapeados'] = !empty($servico);
                }
                
                if ($servico) {
                    $result['servico_data'] = [
                        'nome' => $servico['nome_servico'] ?? 'não definido',
                        'empresa_id' => $servico['empresa_id'] ?? 'não definido',
                        'status' => $servico['status'] ?? 'não definido'
                    ];
                }
            }
            
            Logger::info('DEBUG verDetalhesServico RESULT', $result);
            
            echo json_encode($result, JSON_PRETTY_PRINT);
            exit;
            
        } catch (Exception $e) {
            Logger::error('ERRO no DEBUG verDetalhesServico', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            echo json_encode([
                'status' => 'debug_error',
                'erro' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }
}
