<?php
/**
 * Catálogo Completo de Serviços por Setor e por Nicho
 * O Consultor — Sistema Operacional Empresarial
 *
 * Estrutura determinística (SEM IA) usada para montar a estrutura organizacional
 * de uma empresa: 10 setores BASE (sempre) + setores específicos do nicho.
 *
 * Formato:
 *   'NOME DO SETOR' => [
 *       'tipo' => 'core'|'apoio'|'estrategico'|'especifico',
 *       'descricao' => '...',
 *       'subcategorias' => [
 *           'Nome da Subcategoria' => ['Serviço 1', 'Serviço 2', ...],
 *           ...
 *       ]
 *   ]
 *
 * Uso: CatalogoServicos::base() e CatalogoServicos::porNicho($nicho)
 */

class CatalogoServicos
{
    /**
     * Retorna os 10 setores BASE (aplicáveis a qualquer empresa).
     */
    public static function base(): array
    {
        return [
            // ===================== 1. CAPTAÇÃO / MARKETING =====================
            'CAPTAÇÃO / MARKETING' => [
                'tipo' => 'apoio',
                'descricao' => 'Geração de leads, tráfego, prospecção, branding e comunicação.',
                'subcategorias' => [
                    'Estratégia e Planejamento' => [
                        'Diagnóstico de marketing (auditoria dos canais atuais)',
                        'Definição de posicionamento e proposta de valor',
                        'Definição de público-alvo e personas',
                        'Planejamento de marketing anual/trimestral',
                        'Definição de budget e alocação por canal',
                        'Benchmarking de concorrentes',
                        'Definição de metas e OKRs de marketing',
                        'Escolha e implementação de stack de marketing (martech)',
                        'Plano de lançamento de produto/serviço (go-to-market)',
                    ],
                    'Branding e Identidade' => [
                        'Criação/redesign de logotipo e identidade visual',
                        'Manual de marca (brand guidelines)',
                        'Naming (criação de nomes de marca/produto/linha)',
                        'Definição de tom de voz e discurso de marca',
                        'Campanhas de reconhecimento de marca (brand awareness)',
                        'Monitoramento de percepção de marca (brand tracking)',
                    ],
                    'Conteúdo e Produção Criativa' => [
                        'Planejamento editorial (calendário de conteúdo)',
                        'Redação de posts para redes sociais',
                        'Produção de vídeos (institucionais, reels, anúncios)',
                        'Design gráfico (peças para redes, banners, materiais impressos)',
                        'Produção fotográfica (still, produto, institucional)',
                        'Copywriting para anúncios e landing pages',
                        'Produção de podcasts',
                        'Criação e execução de webinars/lives',
                        'Produção de e-books, whitepapers e materiais ricos',
                        'Legendagem e localização de conteúdo para outros idiomas/mercados',
                    ],
                    'Mídia Paga / Performance' => [
                        'Gestão de campanhas Google Ads (Search, Display, YouTube, Shopping, Performance Max)',
                        'Gestão de campanhas Meta Ads (Facebook/Instagram)',
                        'Gestão de campanhas LinkedIn Ads, TikTok Ads, Pinterest Ads',
                        'Definição e otimização de públicos/segmentações',
                        'Testes A/B de criativos e copy',
                        'Gestão de orçamento e lances (bid management)',
                        'Remarketing / retargeting',
                        'Configuração de pixels, tags e conversões',
                        'Otimização de ROAS/CPA/CPL',
                        'Mídia programática e display em portais/redes de anúncios',
                    ],
                    'SEO e Marketing Orgânico' => [
                        'Auditoria técnica de SEO',
                        'Pesquisa e mapeamento de palavras-chave',
                        'Otimização on-page (títulos, meta descriptions, estrutura de conteúdo)',
                        'Link building / SEO off-page',
                        'SEO local (Google Meu Negócio, avaliações locais)',
                        'Otimização de velocidade e Core Web Vitals',
                        'Estratégia de blog para tráfego orgânico',
                        'Monitoramento de rankings e tráfego orgânico',
                    ],
                    'Marketing de Relacionamento / CRM de Marketing' => [
                        'Segmentação de base de contatos',
                        'Fluxos de automação de e-mail (nutrição, boas-vindas, reativação, carrinho abandonado)',
                        'Criação e gestão de newsletters',
                        'Marketing via WhatsApp/SMS',
                        'Programas de fidelidade e indicação (referral)',
                        'Gestão de comunidade (grupos, fórum de usuários, redes sociais)',
                        'Personalização de comunicação com base em dados comportamentais',
                    ],
                    'Eventos, Parcerias e Assessoria de Imprensa' => [
                        'Organização de eventos próprios (lançamentos, workshops, feiras internas)',
                        'Participação em feiras e eventos do setor',
                        'Assessoria de imprensa e relacionamento com veículos',
                        'Gestão de crise de imagem/reputação',
                        'Parcerias estratégicas e co-marketing',
                        'Marketing de influenciadores (prospecção, negociação, gestão de campanha)',
                        'Gestão de patrocínios',
                    ],
                    'Analytics e Relatórios' => [
                        'Configuração de ferramentas de analytics (GA4, tag manager)',
                        'Relatórios de performance de campanhas por canal',
                        'Dashboards executivos de marketing',
                        'Análise de atribuição multicanal',
                        'Cálculo e acompanhamento de CAC, LTV e ROI de marketing',
                        'Pesquisa de mercado e de satisfação de campanhas',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Migração de conta/plataforma de anúncios (troca de gestor de tráfego, migração de pixel/conversões)',
                        'Handover de conta ao trocar de agência (transição de histórico, criativos, aprendizados)',
                        'Auditoria de marketing herdado (avaliar o que a gestão anterior fez, o que manter/descartar)',
                        'Rebranding completo (mudança de nome, marca ou posicionamento)',
                        'Recuperação de conta suspensa/banida (Meta, Google)',
                        'Retomada de canais abandonados (perfil parado, site desatualizado)',
                        'Reengajamento de base de leads/clientes inativos (win-back de marketing)',
                        'Gestão de crise de imagem (boicote, escândalo, viralização negativa)',
                        'Descontinuação planejada de campanha/canal',
                    ],
                ],
            ],

            // ===================== 2. COMERCIAL / VENDAS =====================
            'COMERCIAL / VENDAS' => [
                'tipo' => 'core',
                'descricao' => 'Prospecção, qualificação, negociação, fechamento e expansão de contas.',
                'subcategorias' => [
                    'Pré-venda / Geração e Qualificação (SDR/BDR)' => [
                        'Prospecção outbound (cold call, cold e-mail, cold message/LinkedIn)',
                        'Atendimento e triagem de leads inbound',
                        'Qualificação de leads (BANT, GPCT, ICP fit)',
                        'Agendamento de reuniões/demonstrações',
                        'Cadência de follow-up de pré-venda',
                        'Enriquecimento de dados de leads',
                        'Lead scoring e priorização de fila',
                    ],
                    'Vendas / Fechamento' => [
                        'Apresentação comercial / pitch de vendas',
                        'Demonstração de produto/serviço',
                        'Elaboração de proposta comercial',
                        'Elaboração de orçamento/cotação',
                        'Negociação comercial (condições, prazos, descontos)',
                        'Fechamento de contrato/pedido',
                        'Gestão de objeções',
                        'Venda consultiva / diagnóstico de necessidade (B2B)',
                        'Venda casada / montagem de pacotes promocionais',
                    ],
                    'Gestão de Propostas e Contratos' => [
                        'Elaboração e revisão de contratos de venda',
                        'Aprovação interna de descontos/condições especiais (alçadas)',
                        'Gestão de assinatura eletrônica de contratos',
                        'Controle de vigência e renovação de contratos',
                        'Gestão de tabela de preços e política comercial',
                    ],
                    'Pós-venda Comercial / Expansão de Conta (Farmer)' => [
                        'Onboarding comercial do novo cliente',
                        'Acompanhamento de satisfação pós-compra',
                        'Upsell (venda de upgrade/plano superior)',
                        'Cross-sell (venda de produtos/serviços complementares)',
                        'Gestão de renovação de contratos recorrentes',
                        'Programa de fidelização comercial',
                        'Gestão de reclamações comerciais (antes de escalar a suporte)',
                        'Follow-up comercial recorrente com carteira ativa (contato periódico fora do ciclo de renovação)',
                        'Cadência de follow-up pós-fechamento (1ª semana, 30, 60, 90 dias)',
                        'Mapeamento de gatilhos de upsell (aumento de uso/consumo, vencimento de plano, aniversário de contrato)',
                        'Mapeamento de gatilhos de cross-sell por perfil de compra/consumo do cliente',
                        'Campanhas periódicas de upsell e cross-sell (trimestrais/sazonais)',
                        'Ofertas de expansão baseadas em ciclo de vida do cliente (customer lifecycle)',
                        'Contato proativo para avaliar satisfação e identificar riscos de cancelamento',
                    ],
                    'Gestão de Canais e Parcerias' => [
                        'Recrutamento de revendedores/parceiros/franqueados',
                        'Treinamento de canais de venda',
                        'Gestão de política de canal (preços, território, comissão)',
                        'Co-participação em vendas com parceiros',
                        'Auditoria de desempenho de canais',
                    ],
                    'Inteligência Comercial' => [
                        'Análise de concorrência comercial',
                        'Pesquisa de precificação de mercado',
                        'Definição de território/carteira por vendedor',
                        'Segmentação de carteira de clientes (curva ABC)',
                        'Definição de metas e quotas de vendas',
                    ],
                    'Gestão de Performance e Compensação' => [
                        'Definição de plano de comissionamento',
                        'Cálculo e pagamento de comissões',
                        'Gestão de ranking/gamificação de vendas',
                        'Treinamento e capacitação de vendedores (técnicas, produto)',
                        'Avaliação de performance individual',
                        'Elaboração de relatórios e forecast de vendas',
                        'Gestão de CRM (funil, pipeline, higienização de dados)',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Herança de carteira de cliente (troca de vendedor)',
                        'Handover de negociação em andamento entre vendedores',
                        'Recuperação de clientes perdidos (win-back comercial)',
                        'Retomada de propostas paradas/esquecidas',
                        'Due diligence comercial (venda da empresa, fusão ou aquisição)',
                        'Transferência de carteira por aquisição de empresa/concorrente',
                        'Encerramento/rescisão de contrato comercial (offboarding do cliente)',
                        'Gestão de disputas contratuais e renegociação forçada',
                        'Auditoria e limpeza de pipeline herdado (CRM de gestão anterior)',
                        'Sucessão de conta-chave (saída de gerente de contas estratégico)',
                    ],
                ],
            ],
        ] + self::baseParte2();
    }

    /**
     * Setores base 3 a 10 (continuação de base()).
     */
    private static function baseParte2(): array
    {
        return [
            // ===================== 3. FINANCEIRO =====================
            'FINANCEIRO' => [
                'tipo' => 'core',
                'descricao' => 'Contas a pagar/receber, tesouraria, contabilidade, fiscal e controladoria.',
                'subcategorias' => [
                    'Contas a Pagar' => [
                        'Cadastro de fornecedores e dados bancários',
                        'Recebimento e conferência de notas fiscais/boletos',
                        'Programação de pagamentos (calendário financeiro)',
                        'Aprovação de pagamentos por alçada',
                        'Execução de pagamentos (PIX, TED, boleto)',
                        'Gestão de adiantamento a fornecedores',
                        'Controle de contratos recorrentes (assinaturas, aluguéis, serviços)',
                        'Negociação de prazos com fornecedores',
                    ],
                    'Contas a Receber e Cobrança' => [
                        'Emissão de boletos/faturas',
                        'Emissão de notas fiscais',
                        'Conciliação de recebimentos',
                        'Régua de cobrança (lembretes e notificações)',
                        'Negociação de dívidas e parcelamento',
                        'Gestão de inadimplência',
                        'Protesto e cobrança judicial (acionamento jurídico)',
                        'Análise de crédito de novos clientes',
                    ],
                    'Tesouraria e Fluxo de Caixa' => [
                        'Elaboração de fluxo de caixa diário/semanal/mensal',
                        'Projeção de fluxo de caixa futuro',
                        'Conciliação bancária',
                        'Gestão de múltiplas contas bancárias',
                        'Aplicação de excedente de caixa (investimentos de curto prazo)',
                        'Gestão de capital de giro',
                        'Antecipação de recebíveis (desconto de duplicatas/cartão)',
                        'Gestão de câmbio (operações internacionais)',
                    ],
                    'Contabilidade e Fechamento' => [
                        'Escrituração contábil',
                        'Fechamento contábil mensal/anual',
                        'Elaboração de balanço patrimonial',
                        'Elaboração de DRE (Demonstrativo de Resultado)',
                        'Conciliação de contas contábeis',
                        'Apuração de resultado por centro de custo',
                        'Elaboração de demonstrações financeiras para terceiros (bancos, sócios)',
                    ],
                    'Fiscal / Tributário' => [
                        'Apuração de impostos (ICMS, ISS, PIS/COFINS, IRPJ/CSLL etc.)',
                        'Entrega de obrigações acessórias (SPED, EFD, DCTF)',
                        'Planejamento tributário (regime tributário, elisão fiscal)',
                        'Gestão de benefícios fiscais/incentivos',
                        'Auditoria fiscal preventiva',
                        'Defesa em autuações e processos administrativos fiscais',
                    ],
                    'Controladoria e Planejamento Orçamentário (FP&A)' => [
                        'Elaboração de orçamento anual (budget)',
                        'Acompanhamento orçado x realizado',
                        'Análise de custos e formação de preço (precificação)',
                        'Análise de rentabilidade por produto/serviço/cliente',
                        'Elaboração de business case / análise de investimento',
                        'Relatórios gerenciais para diretoria',
                        'Modelagem financeira e projeções',
                    ],
                    'Crédito, Financiamento e Investimentos' => [
                        'Gestão de empréstimos e financiamentos',
                        'Negociação com bancos e instituições financeiras',
                        'Gestão de covenants financeiros',
                        'Suporte a captação de investimento (equity/dívida)',
                        'Gestão de garantias (fiança, aval, penhor)',
                    ],
                    'Auditoria, Compliance Financeiro e Riscos' => [
                        'Auditoria interna de processos financeiros',
                        'Auditoria externa (parecer de auditor independente)',
                        'Gestão de riscos financeiros (câmbio, crédito, liquidez)',
                        'Compliance financeiro (políticas internas, alçadas)',
                        'Prevenção a fraudes financeiras internas',
                    ],
                    'Societário / Relação com Investidores' => [
                        'Gestão de atas societárias e alterações contratuais',
                        'Distribuição de lucros e dividendos',
                        'Relatórios periódicos a investidores/sócios',
                        'Suporte a rodadas de investimento (cap table, valuation)',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Migração de sistema financeiro/ERP (dados, histórico, integrações)',
                        'Troca de contador/escritório contábil (handover de escrituração)',
                        'Due diligence financeira (M&A, entrada de investidor)',
                        'Regularização de passivo fiscal/tributário herdado',
                        'Renegociação/recuperação judicial de dívidas antigas',
                        'Fechamento de exercício herdado (contabilidade em atraso/desorganizada)',
                        'Auditoria externa pontual motivada por evento societário',
                        'Prestação de contas após mudança de gestão/sócios',
                        'Encerramento de CNPJ / baixa de empresa',
                        'Conciliação de exercícios anteriores não fechados',
                        'Aspectos financeiros de cisão, fusão ou incorporação societária',
                    ],
                ],
            ],

            // ===================== 4. ATENDIMENTO =====================
            'ATENDIMENTO' => [
                'tipo' => 'core',
                'descricao' => 'Canais de atendimento, agendamentos, SAC, experiência do cliente e retenção.',
                'subcategorias' => [
                    'Canais de Atendimento' => [
                        'Atendimento telefônico (0800, PABX, call center)',
                        'Atendimento via WhatsApp Business',
                        'Atendimento via chat no site',
                        'Atendimento via e-mail',
                        'Atendimento via redes sociais (DM, comentários)',
                        'Atendimento presencial/balcão',
                        'Atendimento via aplicativo próprio',
                    ],
                    'Gestão de Agendamentos' => [
                        'Marcação de horários/serviços',
                        'Confirmação e lembrete de agendamento',
                        'Gestão de encaixes e lista de espera',
                        'Remarcação e cancelamento',
                        'Gestão de no-show (não comparecimento)',
                    ],
                    'SAC / Ouvidoria / Gestão de Reclamações' => [
                        'Registro e triagem de reclamações',
                        'Resolução de reclamações em 1º nível',
                        'Escalonamento para ouvidoria',
                        'Resposta a reclamações em canais externos (Reclame Aqui, Procon)',
                        'Acompanhamento de prazos regulatórios de resposta (quando aplicável)',
                    ],
                    'Experiência do Cliente (CX)' => [
                        'Pesquisa de satisfação (NPS, CSAT, CES)',
                        'Mapeamento de jornada do cliente',
                        'Identificação e correção de pontos de atrito',
                        'Programas de relacionamento e encantamento',
                        'Gestão de feedback e sugestões de clientes',
                    ],
                    'Atendimento Automatizado / Self-service' => [
                        'Configuração e manutenção de chatbot',
                        'Criação e manutenção de FAQ/central de ajuda',
                        'URA (Unidade de Resposta Audível)',
                        'Automação de respostas a perguntas frequentes',
                        'Portal de autoatendimento do cliente',
                    ],
                    'Gestão de Equipe de Atendimento' => [
                        'Escala e dimensionamento de equipe (staffing)',
                        'Monitoria de qualidade (escuta de ligações, avaliação de chats)',
                        'Treinamento de atendentes (script, produto, soft skills)',
                        'Gestão de metas e indicadores (TMA, TME, first call resolution)',
                    ],
                    'Follow-up e Sucesso do Cliente (Retenção Proativa)' => [
                        'Follow-up periódico com clientes ativos (cadência de contato pós-venda/pós-atendimento)',
                        'Contato proativo de satisfação ("como está sendo sua experiência")',
                        'Check-in de acompanhamento após entrega do produto/execução do serviço',
                        'Calendário de contatos por ciclo de vida do cliente (7, 30, 60, 90 dias)',
                        'Monitoramento de sinais de insatisfação/risco de cancelamento (indícios de churn)',
                        'Pesquisa de satisfação proativa (fora do gatilho de reclamação)',
                        'Ações de reengajamento de clientes que reduziram ou pararam de interagir',
                        'Ligação de cortesia em datas relevantes (aniversário, pós-garantia, renovação próxima)',
                        'Identificação de oportunidades de upsell/cross-sell durante o contato',
                        'Registro estruturado do histórico de contatos proativos',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Migração de plataforma de atendimento (CRM, help desk, chatbot)',
                        'Handover de base de clientes por fusão/aquisição',
                        'Assunção de atendimento terceirizado de outro fornecedor',
                        'Gestão de crise (pico de reclamações, recall, crise pública)',
                        'Recuperação de clientes detratores (pós-NPS baixo)',
                        'Retomada de atendimento pós-interrupção (sistema fora do ar, greve)',
                        'Transição de equipe terceirizada de call center',
                        'Atendimento emergencial fora do horário padrão (plantão)',
                    ],
                ],
            ],

            // ===================== 5. SUPORTE =====================
            'SUPORTE' => [
                'tipo' => 'apoio',
                'descricao' => 'Central de chamados, suporte técnico em camadas, SLA e base de conhecimento.',
                'subcategorias' => [
                    'Triagem e Central de Chamados' => [
                        'Abertura de chamados/tickets (múltiplos canais)',
                        'Classificação e priorização por severidade/impacto',
                        'Roteamento para fila/especialista correto',
                        'Acompanhamento e atualização de status ao cliente',
                    ],
                    'Suporte Técnico em Camadas' => [
                        'Suporte N1 (dúvidas simples, primeiro contato)',
                        'Suporte N2 (problemas técnicos intermediários)',
                        'Suporte N3 (problemas complexos, com engenharia/desenvolvimento)',
                        'Suporte remoto (acesso a distância)',
                        'Suporte on-site (visita técnica presencial)',
                    ],
                    'Gestão de SLA e Qualidade' => [
                        'Definição e monitoramento de SLA por severidade',
                        'Relatórios de cumprimento de SLA',
                        'Pesquisa de satisfação pós-atendimento (CSAT de suporte)',
                        'Auditoria de qualidade de tickets fechados',
                    ],
                    'Base de Conhecimento e Documentação' => [
                        'Criação e manutenção de artigos de ajuda',
                        'Documentação de erros conhecidos e soluções (KEDB)',
                        'Manuais e guias de uso do produto/serviço',
                        'Vídeos tutoriais de suporte',
                    ],
                    'Suporte Proativo / Monitoramento' => [
                        'Monitoramento de sistemas/equipamentos (alertas automáticos)',
                        'Manutenção preventiva remota',
                        'Comunicação proativa de instabilidades (status page)',
                        'Análise de causa raiz de incidentes recorrentes (RCA)',
                    ],
                    'Treinamento e Capacitação de Clientes' => [
                        'Treinamento de novos usuários no produto/serviço',
                        'Webinars e materiais de capacitação',
                        'Certificação de usuários avançados',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Migração de sistema/ferramenta de suporte (troca de help desk)',
                        'Handover de ticket entre analistas/times (passagem de plantão)',
                        'Resgate de backlog antigo (chamados parados há meses)',
                        'Suporte a sistema legado sem documentação',
                        'Transição de fornecedor terceirizado de suporte',
                        'Suporte emergencial pós-falha grave (war room)',
                        'Reabertura e reanálise de casos mal resolvidos',
                    ],
                ],
            ],

            // ===================== 6. OPERACIONAL / PRODUÇÃO =====================
            'OPERACIONAL / PRODUÇÃO' => [
                'tipo' => 'core',
                'descricao' => 'PCP, execução operacional, estoque, logística interna, manutenção e qualidade.',
                'subcategorias' => [
                    'Planejamento e Controle da Produção (PCP)' => [
                        'Programação de ordens de produção/serviço',
                        'Balanceamento de linha/capacidade',
                        'Sequenciamento de tarefas',
                        'Planejamento de demanda (previsão)',
                        'Gestão de gargalos produtivos',
                    ],
                    'Execução Operacional' => [
                        'Execução dos processos-fim do negócio (produção/prestação do serviço)',
                        'Padronização de processos (elaboração de SOPs operacionais)',
                        'Controle de produtividade (indicadores de eficiência — OEE)',
                        'Gestão de turnos e escalas de produção',
                        'Setup e troca de linha/processo',
                    ],
                    'Gestão de Estoque e Suprimentos' => [
                        'Controle de estoque de matéria-prima/insumos',
                        'Controle de estoque de produto acabado',
                        'Inventário periódico e cíclico',
                        'Gestão de reposição (ponto de pedido, estoque mínimo)',
                        'Gestão de obsolescência/perdas',
                    ],
                    'Logística Interna e Expedição' => [
                        'Movimentação interna de materiais',
                        'Armazenagem e endereçamento',
                        'Separação de pedidos (picking)',
                        'Embalagem (packing)',
                        'Expedição e despacho',
                        'Gestão de devoluções de insumos/produtos',
                    ],
                    'Manutenção' => [
                        'Manutenção preventiva de equipamentos/instalações',
                        'Manutenção corretiva (chamados de quebra)',
                        'Manutenção preditiva (monitoramento de condição)',
                        'Gestão de plano de manutenção (calendário)',
                        'Gestão de peças de reposição',
                    ],
                    'Controle de Qualidade Operacional' => [
                        'Inspeção de processo/produto',
                        'Gestão de não conformidades de produção',
                        'Ações corretivas e preventivas',
                        'Controle de retrabalho e refugo',
                    ],
                    'Segurança Operacional' => [
                        'Elaboração de normas e procedimentos de segurança',
                        'Inspeção de condições de risco no processo',
                        'Investigação de incidentes/acidentes operacionais',
                        'Treinamento de segurança operacional',
                    ],
                    'Gestão de Fornecedores e Terceiros' => [
                        'Homologação e avaliação de fornecedores',
                        'Gestão de contratos de terceirização',
                        'Auditoria de fornecedores críticos',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Handover de turno/equipe (passagem de plantão operacional)',
                        'Retomada de operação após parada (greve, falta de insumo, acidente)',
                        'Assunção de linha/planta de fornecedor que faliu ou foi adquirido',
                        'Due diligence operacional (M&A)',
                        'Integração operacional pós-fusão (unificação de processos)',
                        'Plano de contingência / continuidade de negócio (BCP)',
                        'Encerramento de linha de produção/unidade',
                        'Transição emergencial de fornecedor crítico',
                    ],
                ],
            ],

            // ===================== 7. RH / GESTÃO DE PESSOAS =====================
            'RH / GESTÃO DE PESSOAS' => [
                'tipo' => 'apoio',
                'descricao' => 'Recrutamento, onboarding, treinamento, desempenho, DP e cultura.',
                'subcategorias' => [
                    'Recrutamento e Seleção' => [
                        'Definição de perfil de vaga (job description)',
                        'Divulgação de vagas',
                        'Triagem de currículos',
                        'Entrevistas (RH e gestor)',
                        'Aplicação de testes/dinâmicas',
                        'Verificação de referências',
                        'Elaboração de proposta de contratação',
                        'Recrutamento interno (promoções/transferências)',
                    ],
                    'Onboarding / Integração' => [
                        'Preparação de posto de trabalho/equipamentos',
                        'Programa de integração institucional',
                        'Apresentação de políticas e cultura',
                        'Acompanhamento dos primeiros 30/60/90 dias',
                    ],
                    'Treinamento e Desenvolvimento' => [
                        'Levantamento de necessidades de treinamento (LNT)',
                        'Elaboração de trilhas de aprendizagem',
                        'Treinamentos técnicos e comportamentais',
                        'Programas de liderança',
                        'Mentoria e coaching interno',
                        'Gestão de plataforma de e-learning (LMS)',
                    ],
                    'Gestão de Desempenho' => [
                        'Definição de metas individuais (OKR/KPI)',
                        'Avaliação de desempenho (90/180/360 graus)',
                        'Feedback estruturado (1:1s)',
                        'Planos de desenvolvimento individual (PDI)',
                        'Gestão de PIP (plano de melhoria de performance)',
                    ],
                    'Remuneração, Cargos e Benefícios' => [
                        'Estrutura de cargos e salários',
                        'Pesquisa salarial de mercado',
                        'Administração de benefícios (VR, VT, plano de saúde, seguro de vida)',
                        'Programas de remuneração variável (bônus, PLR)',
                        'Gestão de equity/stock options (quando aplicável)',
                    ],
                    'Departamento Pessoal' => [
                        'Admissão (documentação, contrato de trabalho)',
                        'Controle de ponto e jornada',
                        'Gestão de férias',
                        'Folha de pagamento',
                        'Rescisão contratual',
                        'Gestão de afastamentos (INSS, licenças)',
                        'Obrigações trabalhistas acessórias (eSocial, FGTS)',
                    ],
                    'Cultura, Clima e Comunicação Interna' => [
                        'Pesquisa de clima organizacional',
                        'Planos de ação a partir da pesquisa',
                        'Comunicação interna (newsletters, murais, comunicados)',
                        'Programas de reconhecimento (employee recognition)',
                        'Eventos internos e datas comemorativas',
                        'Employer branding / marca empregadora',
                    ],
                    'Saúde e Segurança do Trabalho' => [
                        'Exames admissionais/periódicos/demissionais',
                        'Gestão de CIPA',
                        'Elaboração de PCMSO/PGR',
                        'Programas de qualidade de vida e bem-estar',
                        'Gestão de afastamentos por saúde',
                    ],
                    'Offboarding e Desligamento' => [
                        'Comunicação de desligamento',
                        'Entrevista de desligamento',
                        'Cálculo e pagamento de rescisão',
                        'Devolução de equipamentos/acessos',
                        'Revogação de acessos a sistemas',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Assunção de equipe herdada (novo gestor assume time de outra liderança)',
                        'Transição/sucessão de liderança',
                        'Integração de equipes pós-fusão/aquisição',
                        'Auditoria e regularização de passivo trabalhista',
                        'Desligamento em massa / plano de demissão voluntária (PDV)',
                        'Recolocação de colaboradores desligados (outplacement)',
                        'Investigação de denúncias (assédio, conduta, compliance)',
                        'Reestruturação organizacional (reorganograma)',
                    ],
                ],
            ],

            // ===================== 8. ADMINISTRATIVO =====================
            'ADMINISTRATIVO' => [
                'tipo' => 'apoio',
                'descricao' => 'Gestão documental, compras administrativas, facilities, patrimônio e compliance.',
                'subcategorias' => [
                    'Gestão Documental e Contratual' => [
                        'Elaboração e revisão de contratos diversos',
                        'Gestão de vigência e renovação de contratos',
                        'Arquivo físico e digital de documentos',
                        'Gestão de assinatura eletrônica',
                        'Controle de procurações',
                    ],
                    'Compras e Suprimentos Administrativos' => [
                        'Compra de materiais de escritório',
                        'Compra/contratação de serviços gerais (limpeza, segurança, manutenção)',
                        'Gestão de fornecedores administrativos',
                        'Cotação e negociação de contratos administrativos',
                    ],
                    'Facilities / Infraestrutura Predial' => [
                        'Manutenção predial (elétrica, hidráulica, ar-condicionado)',
                        'Gestão de limpeza e conservação',
                        'Gestão de segurança patrimonial (portaria, câmeras)',
                        'Gestão de espaço físico (layout, mudanças internas)',
                        'Gestão de recepção',
                    ],
                    'Gestão de Patrimônio e Ativos' => [
                        'Cadastro e controle de ativos fixos',
                        'Inventário patrimonial periódico',
                        'Manutenção e baixa de ativos',
                        'Gestão de frota de veículos administrativos',
                    ],
                    'Secretaria Executiva / Apoio à Diretoria' => [
                        'Agenda executiva',
                        'Organização de viagens corporativas',
                        'Organização de reuniões e atas',
                        'Suporte administrativo à diretoria/sócios',
                    ],
                    'Seguros e Gestão de Riscos Administrativos' => [
                        'Contratação e gestão de apólices (predial, veículos, RC, D&O)',
                        'Acionamento de sinistros',
                        'Análise de cobertura e renovação anual',
                    ],
                    'Compliance Administrativo e Regulatório' => [
                        'Gestão de licenças e alvarás de funcionamento',
                        'Gestão de certidões negativas',
                        'Compliance com regulações setoriais',
                        'Código de conduta e ética interna',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Mudança de sede / transição de endereço',
                        'Encerramento de filial/unidade',
                        'Digitalização e organização de arquivo físico legado',
                        'Due diligence administrativa (M&A)',
                        'Handover de contratos ao trocar de fornecedor-chave',
                        'Auditoria documental (contratos vencidos, pendências)',
                        'Regularização de documentação legada (alvarás vencidos)',
                    ],
                ],
            ],

            // ===================== 9. TI / INFRAESTRUTURA =====================
            'TI / INFRAESTRUTURA' => [
                'tipo' => 'apoio',
                'descricao' => 'Infraestrutura, sistemas, segurança da informação, helpdesk, LGPD e DevOps.',
                'subcategorias' => [
                    'Infraestrutura e Redes' => [
                        'Gestão de servidores (físicos e virtuais)',
                        'Gestão de rede local (LAN/Wi-Fi)',
                        'Gestão de infraestrutura em nuvem (AWS, Azure, GCP)',
                        'Gestão de links de internet e conectividade',
                        'Gestão de datacenter/salas de servidor',
                        'Virtualização de servidores',
                    ],
                    'Sistemas e Desenvolvimento' => [
                        'Desenvolvimento de sistemas internos sob medida',
                        'Manutenção evolutiva e corretiva de sistemas',
                        'Integração entre sistemas (APIs, middlewares, ETL)',
                        'Gestão de banco de dados (administração, performance, backup)',
                        'Gestão de versionamento de código (Git)',
                        'Testes de software (QA)',
                        'Gestão de ambientes (dev, homologação, produção)',
                    ],
                    'Segurança da Informação' => [
                        'Gestão de antivírus e endpoint protection',
                        'Gestão de firewall e segurança de perímetro',
                        'Gestão de backup e recuperação de dados',
                        'Gestão de identidade e acesso (IAM)',
                        'Pentest e análise de vulnerabilidades',
                        'Resposta a incidentes de segurança (CSIRT)',
                        'Política de segurança da informação',
                    ],
                    'Suporte Interno / Helpdesk' => [
                        'Suporte a estações de trabalho (hardware/software)',
                        'Gestão de contas de e-mail e ferramentas colaborativas',
                        'Instalação e configuração de equipamentos',
                        'Gestão de inventário de TI (parque de máquinas)',
                        'Suporte a impressoras e periféricos',
                    ],
                    'Governança de Dados e LGPD' => [
                        'Mapeamento de dados pessoais (data mapping)',
                        'Elaboração de políticas de privacidade',
                        'Gestão de solicitações de titulares (LGPD)',
                        'Relatório de impacto à proteção de dados (RIPD)',
                        'Gestão de incidentes de vazamento de dados',
                    ],
                    'Gestão de Fornecedores e Contratos de TI' => [
                        'Gestão de contratos de licenciamento de software',
                        'Gestão de contratos de nuvem/hospedagem',
                        'Avaliação e contratação de fornecedores de TI',
                        'Gestão de renovação de certificados e domínios',
                    ],
                    'DevOps / Operações de TI' => [
                        'CI/CD (integração e entrega contínua)',
                        'Monitoramento de performance e uptime (observabilidade)',
                        'Gestão de logs',
                        'Automação de infraestrutura (IaC)',
                        'Testes de carga e stress',
                        'Gestão de incidentes (ITSM) e de mudanças (change management)',
                        'Plano de recuperação de desastres (DRP) e testes de restauração',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Migração de sistemas (servidores, banco de dados, ERP, cloud)',
                        'Migração de dados entre plataformas',
                        'Handover/transição de projeto (documentação de entrega, repasse de conhecimento)',
                        'Assunção de projeto abandonado ou de dev/empresa substituída ("resgate de código")',
                        'Auditoria de código herdado (code audit em sistema de terceiros)',
                        'Refatoração e modernização de sistema legado (legacy modernization)',
                        'Identificação e priorização de débito técnico',
                        'Documentação técnica retroativa (sistema nunca documentado)',
                        'Onboarding técnico de novos desenvolvedores no time',
                        'Offboarding técnico (revogação de acessos, transferência de credenciais/domínios)',
                        'Recuperação de acesso perdido (contas/servidores sem credenciais documentadas)',
                        'Due diligence técnica (M&A, aquisição de empresa/sistema)',
                        'Descontinuação de sistemas (sunset/desligamento de produto)',
                        'Terceirização e gestão de squads externos (outsourcing de desenvolvimento)',
                    ],
                ],
            ],

            // ===================== 10. QUALIDADE / MELHORIA CONTÍNUA =====================
            'QUALIDADE / MELHORIA CONTÍNUA' => [
                'tipo' => 'estrategico',
                'descricao' => 'Gestão de processos e SOPs, auditoria, indicadores, melhoria contínua e riscos.',
                'subcategorias' => [
                    'Gestão de Processos e SOPs' => [
                        'Mapeamento de processos (as-is)',
                        'Redesenho de processos (to-be)',
                        'Elaboração e padronização de SOPs',
                        'Revisão periódica de procedimentos',
                        'Gestão de versionamento de documentos de qualidade',
                    ],
                    'Auditoria e Certificações' => [
                        'Auditoria interna de processos',
                        'Preparação para auditoria externa/certificação',
                        'Gestão de certificações (ISO 9001, ISO 14001, boas práticas setoriais)',
                        'Auditoria de fornecedores (qualidade)',
                    ],
                    'Indicadores e Gestão à Vista' => [
                        'Definição de indicadores (KPIs) por setor',
                        'Construção de dashboards e relatórios',
                        'Reuniões de análise crítica de indicadores',
                        'Gestão à vista (quadros visuais de acompanhamento)',
                    ],
                    'Melhoria Contínua e Projetos' => [
                        'Projetos de melhoria (Kaizen, 5S, Lean, Seis Sigma)',
                        'Gestão de sugestões de melhoria (colaboradores)',
                        'Análise de causa raiz (Ishikawa, 5 porquês)',
                        'Gestão de projetos de inovação de processo',
                    ],
                    'Gestão de Riscos e Não Conformidades' => [
                        'Identificação e classificação de riscos',
                        'Plano de ação para não conformidades (PDCA/8D)',
                        'Gestão de reclamações relacionadas à qualidade',
                        'Matriz de riscos corporativos',
                    ],
                    'Transições, Handover e Casos Especiais' => [
                        'Auditoria de certificação (renovação/manutenção)',
                        'Retomada de processo após não conformidade grave/auditoria reprovada',
                        'Transição de responsável pela qualidade (handover do sistema de gestão)',
                        'Reestruturação de processos herdados de gestão anterior',
                        'Recertificação após mudança de escopo/unidade',
                    ],
                ],
            ],
        ];
    }

    /**
     * Retorna os setores específicos de um nicho (ou [] se não houver).
     * A chave do nicho é normalizada (minúsculas, sem acento).
     */
    public static function porNicho(string $nicho): array
    {
        $chave = self::normalizar($nicho);
        $mapa = self::mapaNichos();
        return $mapa[$chave] ?? [];
    }

    /**
     * Normaliza uma string de nicho para comparação (minúsculas sem acento).
     */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $de = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'];
        $para = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'];
        return str_replace($de, $para, $texto);
    }

    /**
     * Mapa de nichos -> setores específicos.
     * As chaves são normalizadas (sem acento, minúsculas). Aceita sinônimos.
     */
    private static function mapaNichos(): array
    {
        $c = self::catalogoNichos();
        // Aliases: mapear variações de 'segmento' do cadastro para a chave do catálogo
        $aliases = [
            'construcao' => 'construcao', 'construcao civil' => 'construcao', 'engenharia' => 'construcao',
            'saude' => 'saude', 'clinica' => 'saude', 'medicina' => 'saude', 'odontologia' => 'saude',
            'ecommerce' => 'ecommerce', 'e-commerce' => 'ecommerce', 'loja virtual' => 'ecommerce', 'varejo online' => 'ecommerce',
            'educacao' => 'educacao', 'ensino' => 'educacao', 'escola' => 'educacao', 'curso' => 'educacao',
            'alimentacao' => 'alimentacao', 'restaurante' => 'alimentacao', 'food service' => 'alimentacao', 'gastronomia' => 'alimentacao',
            'imobiliario' => 'imobiliario', 'imobiliaria' => 'imobiliario', 'imoveis' => 'imobiliario',
            'advocacia' => 'advocacia', 'juridico' => 'advocacia', 'escritorio de advocacia' => 'advocacia',
            'tecnologia' => 'tecnologia', 'tech' => 'tecnologia', 'software' => 'tecnologia', 'ti' => 'tecnologia', 'saas' => 'tecnologia',
            'beleza' => 'beleza', 'estetica' => 'beleza', 'salao' => 'beleza', 'barbearia' => 'beleza',
            'fitness' => 'fitness', 'academia' => 'fitness', 'crossfit' => 'fitness',
            'turismo' => 'turismo', 'agencia de viagens' => 'turismo', 'hotelaria' => 'turismo',
            'industria' => 'industria', 'fabrica' => 'industria', 'manufatura' => 'industria',
            'logistica' => 'logistica', 'transporte' => 'logistica', 'transportadora' => 'logistica',
            'consultoria' => 'consultoria', 'assessoria' => 'consultoria',
            'financeiro' => 'financeiro', 'fintech' => 'financeiro', 'banco' => 'financeiro', 'credito' => 'financeiro',
            'marketing' => 'marketing', 'agencia' => 'marketing', 'agencia de marketing' => 'marketing', 'publicidade' => 'marketing',
            'automotivo' => 'automotivo', 'oficina' => 'automotivo', 'concessionaria' => 'automotivo',
            'agronegocio' => 'agronegocio', 'agro' => 'agronegocio', 'rural' => 'agronegocio', 'fazenda' => 'agronegocio',
            'ong' => 'ong', 'terceiro setor' => 'ong', 'ongs' => 'ong',
        ];

        $mapa = [];
        foreach ($aliases as $alias => $chaveCatalogo) {
            if (isset($c[$chaveCatalogo])) {
                $mapa[$alias] = $c[$chaveCatalogo];
            }
        }
        return $mapa;
    }

    /**
     * Catálogo de setores específicos por nicho (chave normalizada).
     * Carregado de arquivo separado para manter este arquivo gerenciável.
     */
    private static function catalogoNichos(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = require __DIR__ . '/catalogo_nichos.php';
        }
        return $cache;
    }
}
