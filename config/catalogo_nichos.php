<?php
/**
 * Catálogo de setores ESPECÍFICOS por nicho.
 * Cada entrada é acrescentada aos 10 setores BASE quando a empresa é daquele nicho.
 * Chaves normalizadas (minúsculas, sem acento).
 *
 * Formato idêntico ao catálogo base: 'SETOR' => ['tipo','descricao','subcategorias'=>['Sub'=>[serviços]]]
 */

return [

    // ============================ CONSTRUÇÃO ============================
    'construcao' => [
        'ORÇAMENTAÇÃO E ENGENHARIA DE CUSTOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Levantamento de custos, composições, BDI e viabilidade.',
            'subcategorias' => [
                'Orçamentação' => [
                    'Levantamento de quantitativos (planilha de quantitativos)',
                    'Cotação de materiais junto a fornecedores',
                    'Cotação e negociação de mão de obra/subempreiteiros',
                    'Composição de custos unitários (CPU)',
                    'Cálculo de BDI (Benefícios e Despesas Indiretas)',
                    'Elaboração de curva ABC de insumos',
                    'Análise de viabilidade financeira do empreendimento',
                    'Orçamento paramétrico (fase de estudo preliminar)',
                    'Orçamento executivo (fase de projeto executivo)',
                    'Revisão e reajuste de orçamento (INCC, índices setoriais)',
                    'Elaboração de planilha orçamentária para financiamento (Caixa, bancos)',
                    'Engenharia de valor (redução de custo sem perda de qualidade)',
                    'Gestão de aditivos contratuais (mudanças de escopo)',
                ],
            ],
        ],
        'SUPRIMENTOS / COMPRAS DE OBRA' => [
            'tipo' => 'especifico',
            'descricao' => 'Compras, homologação de fornecedores e almoxarifado de canteiro.',
            'subcategorias' => [
                'Compras de Obra' => [
                    'Cadastro e homologação de fornecedores',
                    'Cotação comparativa (mapa de cotação)',
                    'Negociação de prazos e condições de pagamento',
                    'Emissão de pedidos de compra',
                    'Acompanhamento de entrega (follow-up com fornecedor)',
                    'Conferência de recebimento de material (quantidade/qualidade)',
                    'Gestão de almoxarifado de canteiro',
                    'Controle de devolução/troca de material com defeito',
                    'Gestão de contratos de locação de equipamentos (betoneira, andaimes, gruas)',
                    'Importação de materiais/equipamentos (quando aplicável)',
                ],
            ],
        ],
        'PROJETOS / ENGENHARIA' => [
            'tipo' => 'especifico',
            'descricao' => 'Projetos, compatibilização, aprovação e cálculo estrutural.',
            'subcategorias' => [
                'Projetos' => [
                    'Elaboração/compatibilização de projetos (arquitetônico, estrutural, elétrico, hidráulico)',
                    'Aprovação de projeto junto a órgãos competentes',
                    'Gestão de revisões de projeto',
                    'Elaboração de memorial descritivo',
                    'Modelagem BIM',
                    'Cálculo estrutural e dimensionamento',
                ],
            ],
        ],
        'GESTÃO DE OBRAS / CAMPO' => [
            'tipo' => 'especifico',
            'descricao' => 'Execução, cronograma, medições e controle de campo.',
            'subcategorias' => [
                'Gestão de Campo' => [
                    'Elaboração de cronograma físico-financeiro',
                    'Diário de obra (RDO)',
                    'Medição de etapas concluídas (boletim de medição)',
                    'Gestão de mão de obra própria e subempreiteiros',
                    'Fiscalização e vistoria técnica',
                    'Controle de qualidade de execução (checklist por etapa)',
                    'Gestão de retrabalho',
                    'Controle de avanço físico da obra',
                    'Gestão de canteiro de obras (organização, logística interna)',
                    'Entrega técnica ao cliente/síndico',
                    'Assunção de obra parada/abandonada por outra construtora (levantamento, replanejamento)',
                    'Handover de obra entre engenheiros/responsáveis técnicos',
                ],
            ],
        ],
        'SEGURANÇA DO TRABALHO (SESMT)' => [
            'tipo' => 'especifico',
            'descricao' => 'Segurança, NRs, EPIs e prevenção de acidentes.',
            'subcategorias' => [
                'Segurança do Trabalho' => [
                    'Elaboração de PPRA/PGR',
                    'Elaboração de PCMSO',
                    'Treinamentos de NRs (NR-18, NR-35 etc.)',
                    'Inspeção de EPIs e condições de risco',
                    'Investigação e registro de acidentes (CAT)',
                    'Auditoria de segurança em subempreiteiras',
                    'Gestão de DDS (Diálogo Diário de Segurança)',
                ],
            ],
        ],
        'LEGALIZAÇÃO, DOCUMENTAÇÃO E APROVAÇÕES' => [
            'tipo' => 'especifico',
            'descricao' => 'Alvarás, habite-se, ART/RRT e regularização.',
            'subcategorias' => [
                'Legalização' => [
                    'Aprovação de projeto na prefeitura',
                    'Emissão de alvará de construção',
                    'Emissão de habite-se',
                    'Averbação da construção no cartório de imóveis',
                    'Regularização de obra irregular',
                    'Gestão de ART/RRT (responsabilidade técnica)',
                    'Licenciamento ambiental (quando aplicável)',
                ],
            ],
        ],
        'PÓS-OBRA / ASSISTÊNCIA TÉCNICA' => [
            'tipo' => 'especifico',
            'descricao' => 'Garantia, patologias e assistência ao cliente.',
            'subcategorias' => [
                'Pós-obra' => [
                    'Atendimento a chamados de garantia',
                    'Vistoria de patologias construtivas',
                    'Reparo de itens em garantia',
                    'Entrega de manual do proprietário (uso e manutenção)',
                ],
            ],
        ],
    ],

    // ============================ SAÚDE ============================
    'saude' => [
        'AGENDAMENTO E RECEPÇÃO' => [
            'tipo' => 'especifico',
            'descricao' => 'Marcação, triagem, check-in e gestão de fila.',
            'subcategorias' => [
                'Recepção' => [
                    'Marcação de consultas/exames/procedimentos',
                    'Confirmação de agendamento (SMS/WhatsApp/telefone)',
                    'Gestão de encaixes e urgências',
                    'Triagem/classificação de risco na chegada',
                    'Check-in e cadastro de paciente',
                    'Gestão de lista de espera',
                    'Emissão de senha e organização de fila',
                ],
            ],
        ],
        'PRONTUÁRIO, COMPLIANCE E REGULATÓRIO' => [
            'tipo' => 'especifico',
            'descricao' => 'Prontuário eletrônico, LGPD saúde e conformidade sanitária.',
            'subcategorias' => [
                'Prontuário e Compliance' => [
                    'Gestão de prontuário eletrônico do paciente (PEP)',
                    'Controle de acesso e sigilo de dados sensíveis (LGPD saúde)',
                    'Auditoria de conformidade regulatória (ANVISA, vigilância sanitária)',
                    'Gestão de consentimento informado',
                    'Gestão de protocolos de biossegurança',
                    'Notificação compulsória de doenças (quando aplicável)',
                    'Migração de prontuário entre sistemas',
                    'Transferência de prontuário entre clínicas/profissionais (handover de paciente)',
                ],
            ],
        ],
        'CORPO CLÍNICO / ASSISTENCIAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Escalas, protocolos clínicos e gestão assistencial.',
            'subcategorias' => [
                'Corpo Clínico' => [
                    'Escala médica e de plantões',
                    'Protocolo clínico e prescrição',
                    'Passagem de plantão (handover clínico entre equipes)',
                    'Solicitação e acompanhamento de exames complementares',
                    'Encaminhamento para especialistas',
                    'Educação médica continuada da equipe',
                    'Gestão de comitês clínicos (ética, infecção hospitalar)',
                    'Assunção de pacientes de médico que saiu/se aposentou',
                ],
            ],
        ],
        'FATURAMENTO E CONVÊNIOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Faturamento TISS, glosas e convênios.',
            'subcategorias' => [
                'Faturamento' => [
                    'Faturamento de consultas/procedimentos particulares',
                    'Faturamento TISS para convênios/planos de saúde',
                    'Gestão e recurso de glosas',
                    'Negociação de tabelas com operadoras',
                    'Auditoria de contas médicas',
                    'Gestão de reembolso ao paciente',
                ],
            ],
        ],
        'FARMÁCIA E SUPRIMENTOS MÉDICOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Estoque de medicamentos, controlados e materiais.',
            'subcategorias' => [
                'Farmácia' => [
                    'Controle de estoque de medicamentos e materiais',
                    'Gestão de medicamentos controlados (rastreabilidade)',
                    'Dispensação de medicamentos',
                    'Gestão de validade e descarte de medicamentos vencidos',
                    'Compras de materiais médico-hospitalares',
                ],
            ],
        ],
        'CENTRAL DE REGULAÇÃO / INTERNAÇÃO' => [
            'tipo' => 'especifico',
            'descricao' => 'Gestão de leitos, internações e fluxo de pacientes.',
            'subcategorias' => [
                'Regulação' => [
                    'Gestão de leitos (ocupação, previsão de alta)',
                    'Regulação de internações e transferências',
                    'Gestão de altas hospitalares',
                    'Gestão de fluxo de pacientes críticos (UTI/emergência)',
                ],
            ],
        ],
    ],

    // ============================ E-COMMERCE ============================
    'ecommerce' => [
        'GESTÃO DE CATÁLOGO E MARKETPLACE' => [
            'tipo' => 'especifico',
            'descricao' => 'Catálogo, marketplaces, precificação e reputação.',
            'subcategorias' => [
                'Catálogo e Marketplace' => [
                    'Cadastro de produtos (título, descrição, fotos, atributos)',
                    'Otimização de anúncios/SEO de marketplace',
                    'Gestão de precificação e monitoramento de concorrência',
                    'Gestão de avaliações e reputação (reviews)',
                    'Integração com múltiplos marketplaces (hub de integração)',
                    'Gestão de promoções e cupons',
                    'Curadoria de mix de produtos',
                    'Migração de plataforma (Shopify, VTEX, Magento etc.) com histórico',
                ],
            ],
        ],
        'LOGÍSTICA E FULFILLMENT' => [
            'tipo' => 'especifico',
            'descricao' => 'Picking, packing, fretes e logística reversa.',
            'subcategorias' => [
                'Fulfillment' => [
                    'Separação de pedidos (picking)',
                    'Embalagem (packing)',
                    'Gestão de transportadoras e frete (cotação, contratação)',
                    'Gestão de estoque multicanal (site + marketplaces + loja física)',
                    'Rastreamento e comunicação de status de entrega',
                    'Gestão de fulfillment terceirizado (3PL) e handover de operador logístico',
                    'Gestão de devoluções logísticas (logística reversa)',
                ],
            ],
        ],
        'PÓS-VENDA / TROCAS E DEVOLUÇÕES' => [
            'tipo' => 'especifico',
            'descricao' => 'Trocas, reembolsos, garantia e chargeback.',
            'subcategorias' => [
                'Pós-venda' => [
                    'Análise e aprovação de solicitações de troca/devolução',
                    'Reembolso e estorno',
                    'Gestão de garantia de produtos',
                    'Atendimento a disputas em marketplace/cartão (chargeback)',
                ],
            ],
        ],
        'GROWTH E CRO (OTIMIZAÇÃO DE CONVERSÃO)' => [
            'tipo' => 'especifico',
            'descricao' => 'Otimização de funil, testes A/B e recorrência.',
            'subcategorias' => [
                'Growth / CRO' => [
                    'Otimização de funil de conversão do site',
                    'Testes A/B de página de produto/checkout',
                    'Recuperação de carrinho abandonado',
                    'Gestão de programa de assinatura/recorrência',
                    'Personalização de vitrine (recomendação de produtos)',
                ],
            ],
        ],
        'GESTÃO DE PAGAMENTOS E ANTIFRAUDE' => [
            'tipo' => 'especifico',
            'descricao' => 'Gateways, meios de pagamento, antifraude e conciliação.',
            'subcategorias' => [
                'Pagamentos' => [
                    'Integração de gateways de pagamento',
                    'Gestão de meios de pagamento (cartão, Pix, boleto, carteiras digitais)',
                    'Análise antifraude de pedidos',
                    'Conciliação financeira de vendas online',
                    'Gestão de split de pagamento (marketplace próprio)',
                ],
            ],
        ],
    ],

    // ============================ EDUCAÇÃO ============================
    'educacao' => [
        'PEDAGÓGICO / COORDENAÇÃO PEDAGÓGICA' => [
            'tipo' => 'especifico',
            'descricao' => 'Currículo, material didático e acompanhamento pedagógico.',
            'subcategorias' => [
                'Pedagógico' => [
                    'Elaboração de plano curricular/plano de aula',
                    'Produção e curadoria de material didático',
                    'Avaliação de aprendizagem (provas, trabalhos, critérios)',
                    'Acompanhamento pedagógico individual do aluno',
                    'Formação continuada de professores',
                    'Gestão de projeto político-pedagógico (PPP)',
                    'Adequação curricular (educação inclusiva/necessidades especiais)',
                    'Assunção de turma no meio do ano letivo (professor substituto)',
                ],
            ],
        ],
        'SECRETARIA ACADÊMICA' => [
            'tipo' => 'especifico',
            'descricao' => 'Matrículas, documentos, frequência e notas.',
            'subcategorias' => [
                'Secretaria' => [
                    'Matrícula e rematrícula',
                    'Emissão de documentos (histórico, declaração, certificado, diploma)',
                    'Controle de frequência e notas',
                    'Gestão de transferências (entrada/saída de alunos)',
                    'Gestão de calendário escolar/acadêmico',
                    'Emissão de boletins',
                    'Migração de sistema acadêmico (histórico de alunos, notas antigas)',
                ],
            ],
        ],
        'CORPO DOCENTE' => [
            'tipo' => 'especifico',
            'descricao' => 'Alocação, avaliação e gestão de professores.',
            'subcategorias' => [
                'Docente' => [
                    'Contratação e alocação de professores/disciplinas',
                    'Avaliação de desempenho docente',
                    'Gestão de substituições e faltas de professores',
                    'Planejamento de carga horária',
                ],
            ],
        ],
        'RELACIONAMENTO COM FAMÍLIAS / ALUNOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Comunicação com famílias, mensalidades e satisfação.',
            'subcategorias' => [
                'Relacionamento' => [
                    'Reuniões de pais e mestres',
                    'Comunicação de ocorrências disciplinares',
                    'Gestão de mensalidades e inadimplência escolar',
                    'Pesquisa de satisfação de famílias',
                    'Programa de bolsas e descontos',
                ],
            ],
        ],
    ],

    // ============================ ALIMENTAÇÃO ============================
    'alimentacao' => [
        'COZINHA / PRODUÇÃO DE ALIMENTOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Ficha técnica, boas práticas e gestão de cardápio.',
            'subcategorias' => [
                'Cozinha' => [
                    'Elaboração de ficha técnica e padronização de receitas',
                    'Controle de validade e estoque (PEPS/FIFO)',
                    'Boas práticas de manipulação de alimentos (vigilância sanitária)',
                    'Gestão de mise en place',
                    'Controle de perdas e desperdício',
                    'Gestão de cardápio (engenharia de cardápio, custo por prato)',
                    'Controle de temperatura e armazenamento (câmaras frias)',
                    'Handover de cozinha entre chefs (padronização ao trocar responsável)',
                ],
            ],
        ],
        'SALÃO / ATENDIMENTO NO LOCAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Atendimento à mesa, comandas e reservas.',
            'subcategorias' => [
                'Salão' => [
                    'Atendimento à mesa e anotação de pedidos',
                    'Gestão de comandas e fechamento de conta',
                    'Gestão de reservas',
                    'Controle de tempo de espera/fila',
                    'Montagem e organização de salão/mise en place de sala',
                    'Gestão de eventos e reservas de grupos grandes',
                ],
            ],
        ],
        'DELIVERY' => [
            'tipo' => 'especifico',
            'descricao' => 'Pedidos por app, roteirização e embalagem.',
            'subcategorias' => [
                'Delivery' => [
                    'Gestão de pedidos por aplicativos (iFood, Rappi etc.)',
                    'Gestão de pedidos por canal próprio (site/WhatsApp)',
                    'Roteirização e gestão de entregadores',
                    'Embalagem adequada para transporte',
                    'Controle de tempo de entrega',
                    'Gestão de avaliações em apps de delivery',
                ],
            ],
        ],
        'BAR / GESTÃO DE BEBIDAS' => [
            'tipo' => 'especifico',
            'descricao' => 'Estoque de bebidas e carta de drinks.',
            'subcategorias' => [
                'Bar' => [
                    'Controle de estoque de bebidas',
                    'Gestão de carta de drinks/vinhos',
                    'Controle de perdas (quebra, vazamento)',
                ],
            ],
        ],
        'COMPRAS E ESTOQUE DE INSUMOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Compra de perecíveis e controle de recebimento.',
            'subcategorias' => [
                'Compras de Insumos' => [
                    'Cotação e compra de insumos perecíveis',
                    'Gestão de fornecedores (hortifruti, carnes, laticínios)',
                    'Controle de recebimento e qualidade de insumos',
                ],
            ],
        ],
    ],

    // ============================ IMOBILIÁRIO ============================
    'imobiliario' => [
        'CAPTAÇÃO DE IMÓVEIS' => [
            'tipo' => 'especifico',
            'descricao' => 'Prospecção, avaliação e anúncio de imóveis.',
            'subcategorias' => [
                'Captação' => [
                    'Prospecção de proprietários (captação ativa)',
                    'Avaliação de imóvel (precificação de mercado)',
                    'Produção de fotos profissionais/tour virtual/vídeo',
                    'Elaboração de anúncio (portais imobiliários)',
                    'Vistoria inicial do imóvel',
                ],
            ],
        ],
        'VENDAS IMOBILIÁRIAS' => [
            'tipo' => 'especifico',
            'descricao' => 'Visitas, negociação, contrato e financiamento.',
            'subcategorias' => [
                'Vendas' => [
                    'Atendimento a interessados/visitas agendadas',
                    'Negociação de proposta de compra',
                    'Elaboração de contrato de compra e venda',
                    'Acompanhamento de financiamento bancário do comprador',
                    'Assessoria em documentação de transferência (escritura, registro)',
                ],
            ],
        ],
        'LOCAÇÃO / ADMINISTRAÇÃO DE IMÓVEIS' => [
            'tipo' => 'especifico',
            'descricao' => 'Contratos de locação, repasses e vistorias.',
            'subcategorias' => [
                'Locação' => [
                    'Elaboração de contrato de locação',
                    'Cadastro e análise de inquilino (fiador/seguro fiança)',
                    'Gestão de repasses ao proprietário',
                    'Gestão de inadimplência de aluguel',
                    'Vistoria de entrada e saída do imóvel',
                    'Gestão de manutenção do imóvel locado',
                    'Reajuste e renovação de contrato de locação',
                    'Assunção de carteira de imóveis de outra imobiliária (handover)',
                    'Migração de sistema de gestão imobiliária',
                ],
            ],
        ],
        'JURÍDICO IMOBILIÁRIO / DOCUMENTAÇÃO' => [
            'tipo' => 'especifico',
            'descricao' => 'Regularização, due diligence e despejos.',
            'subcategorias' => [
                'Jurídico Imobiliário' => [
                    'Regularização documental de imóveis',
                    'Due diligence imobiliária (para compra/venda)',
                    'Gestão de processos de despejo/retomada',
                    'Usucapião e regularização fundiária (quando aplicável)',
                ],
            ],
        ],
    ],

    // ============================ ADVOCACIA ============================
    'advocacia' => [
        'CONTENCIOSO' => [
            'tipo' => 'especifico',
            'descricao' => 'Gestão processual, prazos, peças e audiências.',
            'subcategorias' => [
                'Contencioso' => [
                    'Distribuição e protocolo de processos',
                    'Controle de prazos processuais',
                    'Elaboração de peças processuais (petições, recursos)',
                    'Preparo para audiências e sustentações orais',
                    'Acompanhamento de andamento processual',
                    'Cálculo e execução de sentença',
                    'Gestão de acordos e conciliações',
                    'Substabelecimento/assunção de processo de outro advogado (handover processual)',
                ],
            ],
        ],
        'CONSULTIVO' => [
            'tipo' => 'especifico',
            'descricao' => 'Pareceres, due diligence e consultivo recorrente.',
            'subcategorias' => [
                'Consultivo' => [
                    'Elaboração de pareceres jurídicos',
                    'Due diligence jurídica (M&A, imobiliária)',
                    'Atendimento consultivo recorrente (compliance, dúvidas do dia a dia)',
                    'Análise de riscos jurídicos de novos negócios/produtos',
                ],
            ],
        ],
        'SOCIETÁRIO / CONTRATOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Contratos, constituição de sociedades e reorganizações.',
            'subcategorias' => [
                'Societário' => [
                    'Elaboração e revisão de contratos diversos',
                    'Constituição e alteração de sociedades',
                    'Gestão de assembleias e atas societárias',
                    'Reorganização societária (fusão, cisão, incorporação)',
                ],
            ],
        ],
        'GESTÃO DE ESCRITÓRIO JURÍDICO' => [
            'tipo' => 'especifico',
            'descricao' => 'Honorários, banca de horas e sucessão de banca.',
            'subcategorias' => [
                'Gestão de Escritório' => [
                    'Controle de honorários e faturamento por processo/cliente',
                    'Gestão de banca de horas (contratos por hora)',
                    'Gestão de biblioteca jurídica/jurisprudência',
                    'Sucessão de banca (encerramento, transferência de carteira de clientes/processos)',
                ],
            ],
        ],
    ],

    // ============================ TECNOLOGIA ============================
    'tecnologia' => [
        'PRODUTO' => [
            'tipo' => 'especifico',
            'descricao' => 'Discovery, roadmap, requisitos e métricas de produto.',
            'subcategorias' => [
                'Produto' => [
                    'Descoberta de usuário (discovery, entrevistas)',
                    'Definição de roadmap e priorização (RICE, MoSCoW)',
                    'Especificação de requisitos (PRD, user stories)',
                    'Prototipação e testes de usabilidade',
                    'Gestão de backlog de produto',
                    'Análise de métricas de produto (ativação, retenção)',
                    'Comunicação de novidades/lançamentos (release notes)',
                ],
            ],
        ],
        'DESENVOLVIMENTO / ENGENHARIA' => [
            'tipo' => 'especifico',
            'descricao' => 'Desenvolvimento, code review, deploy e arquitetura.',
            'subcategorias' => [
                'Engenharia' => [
                    'Desenvolvimento de novas funcionalidades',
                    'Manutenção corretiva (correção de bugs)',
                    'Gestão de sprints e cerimônias ágeis (Scrum/Kanban)',
                    'Code review',
                    'Testes automatizados (unitário, integração, e2e)',
                    'Deploy e gestão de releases',
                    'Monitoramento de aplicação em produção',
                    'Arquitetura de software e decisões técnicas (ADRs)',
                    'Assunção de código-fonte de squad/empresa terceirizada anterior',
                    'Handover técnico completo na troca de fornecedor de desenvolvimento',
                ],
            ],
        ],
        'CUSTOMER SUCCESS' => [
            'tipo' => 'especifico',
            'descricao' => 'Onboarding, adoção, churn e expansão via CS.',
            'subcategorias' => [
                'Customer Success' => [
                    'Onboarding de novos clientes na plataforma',
                    'Acompanhamento de adoção/uso (health score)',
                    'Prevenção e gestão de churn',
                    'Renovação e expansão de contrato (upsell via CS)',
                    'Coleta de feedback de produto junto a clientes',
                    'Business reviews periódicos com clientes (QBR)',
                    'Transição de carteira de CS entre gestores de conta',
                ],
            ],
        ],
        'QA / GARANTIA DE QUALIDADE' => [
            'tipo' => 'especifico',
            'descricao' => 'Planos de teste, regressão e gestão de bugs.',
            'subcategorias' => [
                'QA' => [
                    'Elaboração de plano e casos de teste',
                    'Testes manuais e exploratórios',
                    'Testes de regressão',
                    'Gestão de bugs (triagem, priorização)',
                    'Testes de performance/carga',
                ],
            ],
        ],
        'DADOS / ANALYTICS' => [
            'tipo' => 'especifico',
            'descricao' => 'Engenharia de dados, dashboards e governança.',
            'subcategorias' => [
                'Dados' => [
                    'Modelagem e engenharia de dados',
                    'Construção de dashboards e relatórios de produto/negócio',
                    'Governança de dados internos',
                    'Suporte a decisões baseadas em dados (data-driven)',
                ],
            ],
        ],
    ],

    // ============================ BELEZA / ESTÉTICA ============================
    'beleza' => [
        'AGENDAMENTO E RECEPÇÃO' => [
            'tipo' => 'especifico',
            'descricao' => 'Marcação, encaixes e política de cancelamento.',
            'subcategorias' => [
                'Recepção' => [
                    'Marcação online/telefone/WhatsApp',
                    'Gestão de encaixes e fila de espera',
                    'Confirmação e lembrete de horário',
                    'Gestão de no-show e política de cancelamento',
                ],
            ],
        ],
        'EXECUÇÃO DE SERVIÇOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Protocolos de atendimento, anamnese e biossegurança.',
            'subcategorias' => [
                'Execução' => [
                    'Protocolo de atendimento por procedimento (corte, coloração, estética facial/corporal)',
                    'Ficha de anamnese do cliente',
                    'Controle de materiais/produtos usados por atendimento',
                    'Gestão de agenda de profissionais (cadeiras/salas)',
                    'Protocolos de biossegurança e esterilização de materiais',
                    'Assunção de clientela de profissional que saiu do salão/clínica',
                ],
            ],
        ],
        'FIDELIZAÇÃO E RELACIONAMENTO' => [
            'tipo' => 'especifico',
            'descricao' => 'Pacotes, fidelidade e reagendamento.',
            'subcategorias' => [
                'Fidelização' => [
                    'Pacotes de serviços e programas de fidelidade',
                    'Pós-atendimento e reagendamento automático',
                    'Campanhas de datas comemorativas',
                    'Indicação/referral de clientes',
                ],
            ],
        ],
        'ESTOQUE DE PRODUTOS E INSUMOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Controle de produtos profissionais e revenda.',
            'subcategorias' => [
                'Estoque' => [
                    'Controle de estoque de produtos de uso profissional',
                    'Gestão de venda de produtos (revenda ao cliente)',
                    'Compras e reposição de insumos',
                ],
            ],
        ],
    ],

    // ============================ FITNESS ============================
    'fitness' => [
        'GESTÃO DE ALUNOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Matrícula, avaliação física e acompanhamento.',
            'subcategorias' => [
                'Alunos' => [
                    'Matrícula e cadastro de novo aluno',
                    'Avaliação física inicial (bioimpedância, medidas)',
                    'Montagem de treino/plano de acompanhamento',
                    'Reavaliação física periódica',
                    'Controle de frequência/check-in',
                ],
            ],
        ],
        'AULAS E MODALIDADES' => [
            'tipo' => 'especifico',
            'descricao' => 'Grade de aulas, instrutores e personal.',
            'subcategorias' => [
                'Aulas' => [
                    'Grade e programação de aulas coletivas',
                    'Gestão de professores/instrutores por modalidade',
                    'Gestão de personal training',
                    'Eventos e competições internas',
                ],
            ],
        ],
        'RETENÇÃO E RELACIONAMENTO' => [
            'tipo' => 'especifico',
            'descricao' => 'Evolução do aluno, renovação e anti-evasão.',
            'subcategorias' => [
                'Retenção' => [
                    'Acompanhamento de evolução do aluno',
                    'Gestão de renovação de plano',
                    'Campanhas contra inadimplência/evasão (churn)',
                    'Programas de indicação de novos alunos',
                    'Handover de aluno entre personal trainers',
                ],
            ],
        ],
        'ESTRUTURA E EQUIPAMENTOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Manutenção de equipamentos e higienização.',
            'subcategorias' => [
                'Estrutura' => [
                    'Manutenção de equipamentos de musculação/cardio',
                    'Gestão de limpeza e higienização de aparelhos',
                    'Controle de capacidade/lotação por horário',
                ],
            ],
        ],
    ],

    // ============================ TURISMO ============================
    'turismo' => [
        'VENDAS DE PACOTES/VIAGENS' => [
            'tipo' => 'especifico',
            'descricao' => 'Cotação, roteiros, emissão e seguro viagem.',
            'subcategorias' => [
                'Vendas' => [
                    'Cotação com fornecedores (cias aéreas, hotéis, receptivos)',
                    'Montagem de roteiro personalizado',
                    'Emissão de vouchers e passagens',
                    'Venda de seguro viagem',
                    'Gestão de pagamento parcelado de pacotes',
                ],
            ],
        ],
        'ATENDIMENTO AO VIAJANTE' => [
            'tipo' => 'especifico',
            'descricao' => 'Suporte durante a viagem e emergências.',
            'subcategorias' => [
                'Atendimento' => [
                    'Suporte durante a viagem (concierge)',
                    'Gestão de imprevistos (cancelamentos, remarcações, overbooking)',
                    'Assistência em emergências (saúde, documentos perdidos)',
                    'Assunção de reserva/grupo de agência parceira que encerrou atividades',
                ],
            ],
        ],
        'OPERAÇÕES E FORNECEDORES' => [
            'tipo' => 'especifico',
            'descricao' => 'Negociação com fornecedores e operação de grupos.',
            'subcategorias' => [
                'Operações' => [
                    'Negociação com hotéis, cias aéreas e receptivos',
                    'Gestão de contratos com fornecedores turísticos',
                    'Montagem de operação de grupos/excursões',
                    'Gestão de guias e equipe de campo',
                ],
            ],
        ],
    ],

    // ============================ INDÚSTRIA ============================
    'industria' => [
        'PCP (PLANEJAMENTO E CONTROLE DA PRODUÇÃO)' => [
            'tipo' => 'especifico',
            'descricao' => 'Programação, capacidade fabril e MRP.',
            'subcategorias' => [
                'PCP' => [
                    'Programação de ordens de produção',
                    'Gestão de capacidade fabril',
                    'Planejamento de matéria-prima (MRP)',
                    'Sequenciamento de máquinas/linhas',
                ],
            ],
        ],
        'MANUTENÇÃO INDUSTRIAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Manutenção preventiva, preditiva e corretiva.',
            'subcategorias' => [
                'Manutenção' => [
                    'Manutenção preventiva de máquinas',
                    'Manutenção preditiva (sensores, análise de vibração)',
                    'Manutenção corretiva emergencial',
                    'Gestão de peças de reposição e sobressalentes',
                    'Gestão de paradas programadas (overhaul)',
                ],
            ],
        ],
        'CONTROLE DE QUALIDADE INDUSTRIAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Inspeção, não conformidades e calibração.',
            'subcategorias' => [
                'Qualidade Industrial' => [
                    'Inspeção de matéria-prima recebida',
                    'Inspeção de produto em processo e acabado',
                    'Gestão de não conformidades de produção',
                    'Calibração de instrumentos de medição',
                    'Laudos técnicos e certificados de qualidade',
                ],
            ],
        ],
        'ENGENHARIA DE PROCESSOS/INDUSTRIAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Tempos e métodos, layout e automação.',
            'subcategorias' => [
                'Engenharia de Processos' => [
                    'Estudo de tempos e métodos',
                    'Melhoria de layout fabril',
                    'Automação de processos produtivos',
                    'Assunção de linha de produção de fábrica adquirida (M&A industrial)',
                ],
            ],
        ],
        'MEIO AMBIENTE E SEGURANÇA INDUSTRIAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Licenciamento, resíduos, efluentes e NRs.',
            'subcategorias' => [
                'Meio Ambiente e Segurança' => [
                    'Licenciamento ambiental',
                    'Gestão de resíduos industriais',
                    'Tratamento de efluentes',
                    'Segurança industrial (NRs específicas — NR-12, NR-13 etc.)',
                ],
            ],
        ],
    ],

    // ============================ LOGÍSTICA ============================
    'logistica' => [
        'GESTÃO DE FROTA' => [
            'tipo' => 'especifico',
            'descricao' => 'Roteirização, rastreamento e gestão de motoristas.',
            'subcategorias' => [
                'Frota' => [
                    'Roteirização de veículos',
                    'Monitoramento em tempo real (rastreamento)',
                    'Manutenção preventiva/corretiva de frota',
                    'Gestão de motoristas (escala, documentação, jornada)',
                    'Controle de combustível e consumo',
                ],
            ],
        ],
        'ARMAZENAGEM' => [
            'tipo' => 'especifico',
            'descricao' => 'Gestão de CD, inventário (WMS) e cross-docking.',
            'subcategorias' => [
                'Armazenagem' => [
                    'Gestão de centro de distribuição (CD)',
                    'Controle de inventário (WMS)',
                    'Endereçamento e organização de estoque',
                    'Cross-docking',
                    'Gestão de mão de obra de armazém',
                ],
            ],
        ],
        'TRANSPORTE E DISTRIBUIÇÃO' => [
            'tipo' => 'especifico',
            'descricao' => 'Fretes, transportadoras, last mile e sinistros.',
            'subcategorias' => [
                'Transporte' => [
                    'Cotação e contratação de fretes',
                    'Gestão de transportadoras parceiras',
                    'Rastreamento de cargas',
                    'Gestão de última milha (last mile)',
                    'Gestão de sinistros/avarias no transporte',
                    'Handover de rota/carga entre transportadoras',
                ],
            ],
        ],
        'PLANEJAMENTO LOGÍSTICO' => [
            'tipo' => 'especifico',
            'descricao' => 'Malha logística, custos e migração de TMS/WMS.',
            'subcategorias' => [
                'Planejamento' => [
                    'Planejamento de malha logística',
                    'Gestão de custos logísticos (frete/CD)',
                    'Migração de sistema de gestão de transporte (TMS/WMS)',
                ],
            ],
        ],
    ],

    // ============================ CONSULTORIA ============================
    'consultoria' => [
        'GESTÃO DE PROJETOS DE CONSULTORIA' => [
            'tipo' => 'especifico',
            'descricao' => 'Escopo, cronograma, alocação e encerramento.',
            'subcategorias' => [
                'Projetos' => [
                    'Elaboração de escopo e proposta técnica',
                    'Cronograma e entregáveis do projeto',
                    'Alocação de consultores por projeto',
                    'Reuniões de status/acompanhamento (status report)',
                    'Encerramento e relatório final de projeto',
                    'Assunção de projeto de consultoria abandonado por outra consultoria',
                    'Handover de projeto entre consultores da mesma empresa',
                ],
            ],
        ],
        'RELACIONAMENTO COM CLIENTE / COMERCIAL DE CONSULTORIA' => [
            'tipo' => 'especifico',
            'descricao' => 'Diagnóstico, apresentação de resultados e propostas.',
            'subcategorias' => [
                'Relacionamento' => [
                    'Diagnóstico inicial (assessment)',
                    'Apresentação de resultados/relatórios',
                    'Gestão de expectativas e mudanças de escopo',
                    'Prospecção e proposta comercial de novos projetos',
                ],
            ],
        ],
        'METODOLOGIA E CONHECIMENTO (KNOWLEDGE MANAGEMENT)' => [
            'tipo' => 'especifico',
            'descricao' => 'Metodologias proprietárias e base de conhecimento.',
            'subcategorias' => [
                'Conhecimento' => [
                    'Desenvolvimento de metodologias proprietárias',
                    'Gestão de base de conhecimento interna (cases, templates)',
                    'Capacitação de consultores juniores',
                ],
            ],
        ],
    ],

    // ============================ FINANCEIRO / FINTECHS ============================
    'financeiro' => [
        'ANÁLISE DE CRÉDITO E RISCO' => [
            'tipo' => 'especifico',
            'descricao' => 'Score, política de crédito e monitoramento de carteira.',
            'subcategorias' => [
                'Crédito e Risco' => [
                    'Modelagem de score de crédito',
                    'Política e alçadas de aprovação de crédito',
                    'Análise de garantias',
                    'Monitoramento de carteira de crédito (risco)',
                    'Revisão de limites de crédito',
                ],
            ],
        ],
        'COMPLIANCE E PREVENÇÃO À FRAUDE' => [
            'tipo' => 'especifico',
            'descricao' => 'KYC, AML e reporte a reguladores.',
            'subcategorias' => [
                'Compliance' => [
                    'KYC (Know Your Customer) na abertura de conta',
                    'Monitoramento de transações suspeitas (AML)',
                    'Reporte a órgãos reguladores (COAF, Bacen)',
                    'Auditoria de compliance regulatório',
                ],
            ],
        ],
        'COBRANÇA' => [
            'tipo' => 'especifico',
            'descricao' => 'Régua, renegociação e recuperação de crédito.',
            'subcategorias' => [
                'Cobrança' => [
                    'Régua de cobrança automatizada',
                    'Negociação de dívidas e renegociação',
                    'Cobrança judicial/extrajudicial',
                    'Recuperação de crédito',
                ],
            ],
        ],
        'OPERAÇÕES FINANCEIRAS/BANCÁRIAS' => [
            'tipo' => 'especifico',
            'descricao' => 'Conciliação, liquidação e core bancário.',
            'subcategorias' => [
                'Operações' => [
                    'Conciliação de transações',
                    'Liquidação financeira',
                    'Gestão de contas e movimentações',
                    'Migração de core bancário/sistema de processamento',
                    'Assunção de carteira de crédito de outra instituição (portabilidade em lote)',
                ],
            ],
        ],
        'PRODUTO FINANCEIRO' => [
            'tipo' => 'especifico',
            'descricao' => 'Desenvolvimento e precificação de produtos financeiros.',
            'subcategorias' => [
                'Produto' => [
                    'Desenvolvimento de novos produtos financeiros',
                    'Precificação de produtos (taxas, tarifas)',
                    'Gestão regulatória de novos produtos',
                ],
            ],
        ],
    ],

    // ============================ MARKETING / AGÊNCIAS ============================
    'marketing' => [
        'PLANEJAMENTO DE CONTAS' => [
            'tipo' => 'especifico',
            'descricao' => 'Briefing, plano de campanha e planejamento de mídia.',
            'subcategorias' => [
                'Planejamento' => [
                    'Briefing com cliente',
                    'Elaboração de plano de campanha',
                    'Aprovação de estratégia com cliente',
                    'Planejamento de mídia (mix de canais)',
                ],
            ],
        ],
        'CRIAÇÃO / CONTEÚDO' => [
            'tipo' => 'especifico',
            'descricao' => 'Produção de peças, aprovação e conteúdo.',
            'subcategorias' => [
                'Criação' => [
                    'Produção de peças (design, copy, vídeo)',
                    'Revisão e aprovação de peças com cliente',
                    'Gestão de banco de referências/moodboard',
                    'Produção de conteúdo para redes do cliente',
                ],
            ],
        ],
        'MÍDIA PAGA (AGÊNCIA)' => [
            'tipo' => 'especifico',
            'descricao' => 'Gestão e otimização de campanhas do cliente.',
            'subcategorias' => [
                'Mídia Paga' => [
                    'Gestão de budget de campanhas do cliente',
                    'Otimização de campanhas',
                    'Relatórios de performance por cliente',
                ],
            ],
        ],
        'ATENDIMENTO A CLIENTES (ACCOUNT MANAGEMENT)' => [
            'tipo' => 'especifico',
            'descricao' => 'Status, renovação e handover de contas.',
            'subcategorias' => [
                'Account Management' => [
                    'Reunião de status/reporte mensal',
                    'Gestão de renovação de contrato',
                    'Gestão de múltiplos stakeholders do cliente',
                    'Handover de conta de cliente entre agências (transição de histórico)',
                ],
            ],
        ],
    ],

    // ============================ AUTOMOTIVO ============================
    'automotivo' => [
        'VENDAS DE VEÍCULOS' => [
            'tipo' => 'especifico',
            'descricao' => 'Negociação, test-drive, avaliação e documentação.',
            'subcategorias' => [
                'Vendas' => [
                    'Atendimento e negociação de venda (novos/seminovos)',
                    'Test-drive',
                    'Avaliação de veículo usado (troca)',
                    'Financiamento e documentação (DUT, transferência)',
                    'Entrega técnica do veículo',
                ],
            ],
        ],
        'OFICINA / PÓS-VENDA MECÂNICO' => [
            'tipo' => 'especifico',
            'descricao' => 'Ordem de serviço, diagnóstico e reparo.',
            'subcategorias' => [
                'Oficina' => [
                    'Abertura de ordem de serviço',
                    'Orçamento de reparo',
                    'Diagnóstico técnico do veículo',
                    'Execução de serviços mecânicos/elétricos',
                    'Controle de qualidade pós-reparo',
                    'Assunção de veículo em manutenção de oficina que fechou',
                ],
            ],
        ],
        'PEÇAS E ESTOQUE' => [
            'tipo' => 'especifico',
            'descricao' => 'Cotação, estoque e peças originais x paralelas.',
            'subcategorias' => [
                'Peças' => [
                    'Cotação e compra de peças',
                    'Controle de giro/estoque de peças',
                    'Gestão de peças originais x paralelas',
                ],
            ],
        ],
        'GARANTIA E RELACIONAMENTO COM MONTADORA' => [
            'tipo' => 'especifico',
            'descricao' => 'Garantia, recall e auditoria da montadora.',
            'subcategorias' => [
                'Garantia' => [
                    'Acionamento de garantia junto à montadora',
                    'Gestão de recall',
                    'Auditoria de garantia (montadora audita concessionária)',
                ],
            ],
        ],
    ],

    // ============================ AGRONEGÓCIO ============================
    'agronegocio' => [
        'PRODUÇÃO AGRÍCOLA/PECUÁRIA' => [
            'tipo' => 'especifico',
            'descricao' => 'Plantio, manejo, sanidade e colheita.',
            'subcategorias' => [
                'Produção' => [
                    'Planejamento de plantio/safra',
                    'Manejo de cultura (adubação, irrigação, controle de pragas)',
                    'Manejo animal (nutrição, reprodução, sanidade)',
                    'Controle sanitário e fitossanitário',
                    'Colheita e pós-colheita',
                    'Assunção de área/safra de produtor que encerrou operação (arrendamento)',
                ],
            ],
        ],
        'INSUMOS AGRÍCOLAS' => [
            'tipo' => 'especifico',
            'descricao' => 'Compra, armazenamento e aplicação de insumos.',
            'subcategorias' => [
                'Insumos' => [
                    'Compra de defensivos, fertilizantes, sementes/ração',
                    'Armazenamento de insumos',
                    'Aplicação de defensivos/fertilizantes',
                    'Gestão de receituário agronômico',
                ],
            ],
        ],
        'COMERCIALIZAÇÃO / TRADING' => [
            'tipo' => 'especifico',
            'descricao' => 'Venda de safra, hedge e cotações.',
            'subcategorias' => [
                'Comercialização' => [
                    'Negociação de venda de safra',
                    'Contratos futuros e hedge',
                    'Acompanhamento de cotações de commodities',
                    'Gestão de contratos com cooperativas/tradings',
                ],
            ],
        ],
        'LOGÍSTICA RURAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Transporte de safra, armazenagem e frota agrícola.',
            'subcategorias' => [
                'Logística Rural' => [
                    'Transporte de safra/insumos',
                    'Armazenagem em silos/armazéns',
                    'Gestão de frota agrícola',
                ],
            ],
        ],
        'MAQUINÁRIO E INFRAESTRUTURA RURAL' => [
            'tipo' => 'especifico',
            'descricao' => 'Manutenção de maquinário e infraestrutura.',
            'subcategorias' => [
                'Maquinário' => [
                    'Manutenção de maquinário agrícola',
                    'Gestão de infraestrutura (galpões, cercas, irrigação)',
                ],
            ],
        ],
    ],

    // ============================ ONG / TERCEIRO SETOR ============================
    'ong' => [
        'CAPTAÇÃO DE RECURSOS (FUNDRAISING)' => [
            'tipo' => 'especifico',
            'descricao' => 'Prospecção de doadores, editais e campanhas.',
            'subcategorias' => [
                'Fundraising' => [
                    'Prospecção de doadores (pessoa física/jurídica)',
                    'Elaboração e submissão de projetos/editais',
                    'Gestão de campanhas de doação',
                    'Relacionamento com financiadores/patrocinadores',
                    'Gestão de doações recorrentes',
                ],
            ],
        ],
        'GESTÃO DE PROJETOS SOCIAIS' => [
            'tipo' => 'especifico',
            'descricao' => 'Execução, indicadores de impacto e prestação de contas.',
            'subcategorias' => [
                'Projetos Sociais' => [
                    'Planejamento e execução de projetos',
                    'Monitoramento de indicadores de impacto social',
                    'Prestação de contas a financiadores',
                    'Avaliação de resultados/impacto (monitoramento e avaliação)',
                    'Assunção de projeto social de ONG que encerrou atividades',
                ],
            ],
        ],
        'VOLUNTARIADO' => [
            'tipo' => 'especifico',
            'descricao' => 'Recrutamento, escala e retenção de voluntários.',
            'subcategorias' => [
                'Voluntariado' => [
                    'Recrutamento e seleção de voluntários',
                    'Escala e gestão de atividades voluntárias',
                    'Reconhecimento e retenção de voluntários',
                    'Handover de base de doadores/beneficiários entre gestores',
                ],
            ],
        ],
        'COMPLIANCE COM ÓRGÃOS REGULADORES' => [
            'tipo' => 'especifico',
            'descricao' => 'Certidões, relatórios e prestação de contas.',
            'subcategorias' => [
                'Compliance' => [
                    'Emissão e renovação de certidões (CEBAS, utilidade pública)',
                    'Relatórios anuais de atividades e transparência',
                    'Prestação de contas ao Ministério Público/órgãos de fiscalização',
                ],
            ],
        ],
        'COMUNICAÇÃO E ADVOCACY' => [
            'tipo' => 'especifico',
            'descricao' => 'Comunicação institucional, impacto e incidência.',
            'subcategorias' => [
                'Comunicação' => [
                    'Comunicação institucional e redes sociais da causa',
                    'Relatório de impacto para divulgação',
                    'Advocacy e incidência política (quando aplicável)',
                ],
            ],
        ],
    ],
];
