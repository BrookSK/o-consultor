<?php
/**
 * JornadaCliente - Helper para gerenciar fluxo integrado do cliente
 * Cliente → Diagnóstico → Manual Operacional → Plano de Ação → Central de Conteúdo → Máquina
 */

class JornadaCliente
{
    const ETAPAS = [
        'diagnostico' => [
            'ordem' => 1,
            'titulo' => 'Diagnóstico Empresarial',
            'descricao' => 'Complete o diagnóstico para identificar o nível de maturidade',
            'url' => '/diagnostico/novo',
            'icone' => '📊',
            'requisitos' => []
        ],
        'manual_operacional' => [
            'ordem' => 2,
            'titulo' => 'Manual Operacional',
            'descricao' => 'Crie SOPs baseados no seu diagnóstico',
            'url' => '/manual-operacional',
            'icone' => '📋',
            'requisitos' => ['diagnostico']
        ],
        'plano_acao' => [
            'ordem' => 3,
            'titulo' => 'Plano de Ação',
            'descricao' => 'Defina prioridades e ações baseadas nos SOPs',
            'url' => '/plano-de-acao/novo',
            'icone' => '🎯',
            'requisitos' => ['diagnostico', 'manual_operacional']
        ],
        'central_conteudo' => [
            'ordem' => 4,
            'titulo' => 'Central de Conteúdo',
            'descricao' => 'Acesse conteúdos personalizados para seu negócio',
            'url' => '/central-de-conteudo',
            'icone' => '📚',
            'requisitos' => ['diagnostico']
        ],
        'maquina_conteudo' => [
            'ordem' => 5,
            'titulo' => 'Máquina de Conteúdo',
            'descricao' => 'Gere conteúdos com IA usando dados da sua jornada',
            'url' => '/maquina-de-conteudo',
            'icone' => '🤖',
            'requisitos' => ['diagnostico', 'central_conteudo']
        ]
    ];

    /**
     * Verifica status das etapas para um cliente
     */
    public static function verificarStatusEtapas(int $empresaId): array
    {
        $status = [];
        
        // Verificar diagnóstico
        $diagnostico = Database::queryOne(
            "SELECT id FROM diagnosticos WHERE empresa_id = :empresa_id AND status = 'concluido' LIMIT 1",
            ['empresa_id' => $empresaId]
        );
        $status['diagnostico'] = !empty($diagnostico);

        // Verificar manual operacional (ao menos 1 SOP ativo)
        $sop = Database::queryOne(
            "SELECT id FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo' LIMIT 1",
            ['empresa_id' => $empresaId]
        );
        $status['manual_operacional'] = !empty($sop);

        // Verificar plano de ação
        $plano = Database::queryOne(
            "SELECT id FROM planos WHERE empresa_id = :empresa_id AND status IN ('ativo', 'concluido') LIMIT 1",
            ['empresa_id' => $empresaId]
        );
        $status['plano_acao'] = !empty($plano);

        // Verificar central de conteúdo (perfil configurado)
        $perfilBusca = Database::queryOne(
            "SELECT id FROM empresa_perfil_busca WHERE empresa_id = :empresa_id LIMIT 1",
            ['empresa_id' => $empresaId]
        );
        $status['central_conteudo'] = !empty($perfilBusca);

        // Verificar máquina de conteúdo (marca criada)
        $marca = Database::queryOne(
            "SELECT id FROM marcas WHERE empresa_id = :empresa_id AND ativo = 1 LIMIT 1",
            ['empresa_id' => $empresaId]
        );
        $status['maquina_conteudo'] = !empty($marca);

        return $status;
    }

    /**
     * Encontra próxima etapa disponível
     */
    public static function proximaEtapa(int $empresaId): ?array
    {
        $status = self::verificarStatusEtapas($empresaId);
        
        foreach (self::ETAPAS as $chave => $etapa) {
            // Se esta etapa não está concluída
            if (!($status[$chave] ?? false)) {
                // Verificar se todos os requisitos estão atendidos
                $requisitosAtendidos = true;
                foreach ($etapa['requisitos'] as $requisito) {
                    if (!($status[$requisito] ?? false)) {
                        $requisitosAtendidos = false;
                        break;
                    }
                }
                
                if ($requisitosAtendidos) {
                    return array_merge($etapa, ['chave' => $chave]);
                }
            }
        }
        
        return null; // Jornada completa
    }

    /**
     * Calcula percentual de conclusão da jornada
     */
    public static function calcularProgresso(int $empresaId): array
    {
        $status = self::verificarStatusEtapas($empresaId);
        
        $totalEtapas = count(self::ETAPAS);
        $etapasConcluidas = array_sum($status);
        $percentual = $totalEtapas > 0 ? round(($etapasConcluidas / $totalEtapas) * 100) : 0;
        
        return [
            'total' => $totalEtapas,
            'concluidas' => $etapasConcluidas,
            'percentual' => $percentual,
            'status' => $status,
            'proxima_etapa' => self::proximaEtapa($empresaId)
        ];
    }

    /**
     * Gera dados contextuais para alimentar IA
     */
    public static function extrairDadosContextuais(int $empresaId): array
    {
        $contexto = [
            'empresa_id' => $empresaId,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Cada bloco abaixo é tolerante a falhas: em bancos com schema diferente,
        // uma coluna/tabela ausente NÃO deve derrubar a geração de conteúdo.
        // O contexto da jornada é opcional (enriquece o prompt, não é obrigatório).

        // Dados do diagnóstico
        try {
            $diagnostico = Database::queryOne(
                "SELECT respostas, pontuacao FROM diagnosticos WHERE empresa_id = :empresa_id AND status = 'concluido' ORDER BY criado_em DESC LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            if ($diagnostico) {
                $contexto['diagnostico'] = [
                    'respostas' => json_decode($diagnostico['respostas'], true),
                    'pontuacao' => $diagnostico['pontuacao'],
                    'nivel_maturidade' => $diagnostico['pontuacao']
                ];
            }
        } catch (\Throwable $e) {
            // schema opcional; ignora silenciosamente
        }

        // Dados dos SOPs (a coluna 'tags' pode não existir em bancos antigos)
        try {
            $contexto['sops'] = Database::query(
                "SELECT titulo, departamento FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo'",
                ['empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            // schema opcional; ignora silenciosamente
        }

        // Dados do plano de ação
        try {
            $plano = Database::queryOne(
                "SELECT objetivo as objetivos, NULL as areas_foco FROM planos WHERE empresa_id = :empresa_id AND status IN ('ativo', 'concluido') ORDER BY criado_em DESC LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            if ($plano) {
                $contexto['plano_acao'] = [
                    'objetivos' => json_decode($plano['objetivos'] ?? '[]', true),
                    'areas_foco' => json_decode($plano['areas_foco'] ?? '[]', true)
                ];
            }
        } catch (\Throwable $e) {
            // schema opcional; ignora silenciosamente
        }

        // Perfil de busca de conteúdo
        try {
            $perfilBusca = Database::queryOne(
                "SELECT palavras_chave, sites_referencia FROM empresa_perfil_busca WHERE empresa_id = :empresa_id LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            if ($perfilBusca) {
                $contexto['perfil_conteudo'] = [
                    'palavras_chave' => json_decode($perfilBusca['palavras_chave'] ?? '[]', true),
                    'sites_referencia' => explode(',', (string) ($perfilBusca['sites_referencia'] ?? ''))
                ];
            }
        } catch (\Throwable $e) {
            // schema opcional; ignora silenciosamente
        }

        // Marca registrada
        try {
            $marca = Database::queryOne(
                "SELECT nome, arquetipo, tom_voz, palavras_chave FROM marcas WHERE empresa_id = :empresa_id AND ativo = 1 LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            if ($marca) {
                $contexto['marca'] = [
                    'nome' => $marca['nome'],
                    'arquetipo' => $marca['arquetipo'],
                    'tom_voz' => $marca['tom_voz'] ?? null,
                    'palavras_chave' => json_decode($marca['palavras_chave'] ?? '[]', true)
                ];
            }
        } catch (\Throwable $e) {
            // schema opcional; ignora silenciosamente
        }

        return $contexto;
    }

    /**
     * Widget de navegação para incluir nas views
     */
    public static function renderWidgetNavegacao(int $empresaId): string
    {
        $progresso = self::calcularProgresso($empresaId);
        $proximaEtapa = $progresso['proxima_etapa'];
        
        if (!$proximaEtapa && $progresso['percentual'] < 100) {
            return ''; // Nenhuma etapa disponível ainda
        }

        ob_start();
        ?>
        <!-- Widget Jornada do Cliente -->
        <div class="bg-gradient-to-r from-primary to-blue-700 rounded-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold">🚀 Sua Jornada Digital</h3>
                    <p class="text-white/80 text-sm">
                        <?php if ($progresso['percentual'] === 100): ?>
                            Parabéns! Jornada completa. Continue utilizando as ferramentas.
                        <?php else: ?>
                            <?= $progresso['concluidas'] ?>/<?= $progresso['total'] ?> etapas concluídas
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold"><?= $progresso['percentual'] ?>%</div>
                    <div class="text-sm text-white/80">Concluído</div>
                </div>
            </div>
            
            <!-- Barra de progresso -->
            <div class="w-full bg-white/20 rounded-full h-2 mb-4">
                <div class="bg-white h-2 rounded-full transition-all" style="width: <?= $progresso['percentual'] ?>%"></div>
            </div>
            
            <?php if ($proximaEtapa && $progresso['percentual'] < 100): ?>
            <!-- Próxima etapa -->
            <div class="bg-white/10 rounded-lg p-4 mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl"><?= $proximaEtapa['icone'] ?></span>
                    <div class="flex-1">
                        <h4 class="font-semibold"><?= htmlspecialchars($proximaEtapa['titulo']) ?></h4>
                        <p class="text-sm text-white/80"><?= htmlspecialchars($proximaEtapa['descricao']) ?></p>
                    </div>
                    <a href="<?= APP_URL . $proximaEtapa['url'] ?>" 
                       class="bg-white text-primary px-4 py-2 rounded-lg font-medium text-sm hover:bg-white/90 transition">
                        Continuar →
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Etapas em linha -->
            <div class="flex items-center gap-3 overflow-x-auto pb-2">
                <?php foreach (self::ETAPAS as $chave => $etapa): 
                    $concluida = $progresso['status'][$chave] ?? false;
                    $disponivel = true;
                    foreach ($etapa['requisitos'] as $req) {
                        if (!($progresso['status'][$req] ?? false)) {
                            $disponivel = false;
                            break;
                        }
                    }
                ?>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <div class="flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium
                        <?php if ($concluida): ?>
                            bg-white/20 text-white
                        <?php elseif ($disponivel): ?>
                            bg-white/10 text-white/80 border border-white/30
                        <?php else: ?>
                            bg-white/5 text-white/50
                        <?php endif; ?>">
                        <span><?= $etapa['icone'] ?></span>
                        <span><?= htmlspecialchars($etapa['titulo']) ?></span>
                        <?php if ($concluida): ?>
                            <span>✓</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($etapa['ordem'] < count(self::ETAPAS)): ?>
                        <div class="w-4 h-0.5 bg-white/30"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}