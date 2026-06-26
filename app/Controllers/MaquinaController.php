<?php
/**
 * MaquinaController — Máquina de Conteúdo com geração de imagens
 * Geração de carrosséis, posts e stories com IA (GPT + DALL-E)
 */

class MaquinaController
{
    public function index(): void
    {
        Auth::proteger();
        $dados = ['marcas' => $this->getMarcasMock()];
        require VIEW_PATH . '/maquina/index.php';
    }

    public function novaMarca(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        $dados = [];
        require VIEW_PATH . '/maquina/nova-marca.php';
    }

    public function salvarMarca(): void
    {
        Auth::proteger();
        Csrf::verificar();
        // Em produção: salvar marca + uploads
        Logger::acao('Nova marca cadastrada', ['nome' => $_POST['nome'] ?? '']);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Marca cadastrada!', 'redirect' => APP_URL . '/maquina-de-conteudo/marca?id=1']);
        exit;
    }

    public function marca(): void
    {
        Auth::proteger();
        $dados = [
            'marca' => $this->getMarcaDetalheMock(),
            'conteudos' => $this->getConteudosMock(),
            'noticias' => $this->getNoticiasMock(),
        ];
        require VIEW_PATH . '/maquina/marca.php';
    }

    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $tipo = htmlspecialchars(trim($_POST['tipo'] ?? ''));
        $tema = htmlspecialchars(trim($_POST['tema'] ?? ''));
        $objetivo = htmlspecialchars(trim($_POST['objetivo'] ?? ''));
        $estiloImagem = htmlspecialchars(trim($_POST['estilo_imagem'] ?? 'ia'));

        if (empty($tema)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o tema.']);
            exit;
        }

        // Dados da marca (em produção: ler do banco)
        $marca = $this->getMarcaDetalheMock();

        // 1. Gerar texto via IA
        $prompt = ApiHelper::buildPromptConteudo(
            ['prompt_master' => $marca['prompt_dalle'], 'prompt_dalle' => $marca['prompt_dalle']],
            $tipo ?: 'carrossel',
            $tema,
            $objetivo
        );
        $resIA = ApiHelper::chamarAnalise($prompt, true);

        if ($resIA['sucesso'] && is_array($resIA['conteudo'])) {
            $resultado = $resIA['conteudo'];
            $resultado['tipo'] = $tipo ?: 'carrossel';
            $resultado['tema'] = $tema;
            $resultado['status'] = 'rascunho';

            // 2. Gerar imagens (se estilo = IA)
            if ($estiloImagem === 'ia' && isset($resultado['slides'])) {
                foreach ($resultado['slides'] as &$slide) {
                    if (!empty($slide['prompt_imagem'])) {
                        $imgResult = ApiHelper::gerarImagem(
                            $marca['prompt_dalle'] . ' — ' . $slide['prompt_imagem'] . ' Não incluir texto na imagem.',
                            '1024x1024'
                        );
                        $slide['imagem_url'] = $imgResult['sucesso'] ? $imgResult['url'] : 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Imagem';
                    }
                }
                unset($slide);
            }
        } else {
            // Fallback: usar mock
            $resultado = $this->gerarConteudoMock($tipo, $tema, $objetivo);
        }

        Session::set('conteudo_gerado', $resultado);
        Logger::acao('Conteúdo gerado via IA', ['tipo' => $tipo, 'tema' => $tema]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'conteudo' => $resultado,
            'mensagem' => 'Conteúdo gerado!',
        ]);
        exit;
    }

    public function editar(): void
    {
        Auth::proteger();
        $dados = ['conteudo' => Session::get('conteudo_gerado') ?? $this->gerarConteudoMock('carrossel', 'Gestão financeira para PMEs', 'educar')];
        require VIEW_PATH . '/maquina/editar.php';
    }

    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Conteúdo aprovado');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Conteúdo aprovado!']);
        exit;
    }

    public function regenerarImagem(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Imagem regenerada via DALL-E');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'imagem_url' => 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Imagem+Regenerada', 'mensagem' => 'Imagem regenerada!']);
        exit;
    }

    // ===== MOCKS =====

    private function getMarcasMock(): array
    {
        return [
            ['id' => 1, 'nome' => 'Tech Solutions', 'nicho' => 'Tecnologia/MSP', 'ultimo' => '2026-06-25', 'status' => 'ativo', 'avatar' => 'T'],
            ['id' => 2, 'nome' => 'Varejo Express', 'nicho' => 'Varejo', 'ultimo' => '2026-06-20', 'status' => 'ativo', 'avatar' => 'V'],
            ['id' => 3, 'nome' => 'FoodService', 'nicho' => 'Alimentação', 'ultimo' => null, 'status' => 'pendente', 'avatar' => 'F'],
        ];
    }

    private function getMarcaDetalheMock(): array
    {
        return [
            'id' => 1, 'nome' => 'Tech Solutions', 'nicho' => 'Tecnologia/MSP',
            'publico' => 'Empresários de PMEs que precisam de TI gerenciada',
            'tom' => 'Semiformal', 'arquetipo' => 'Sábio',
            'paleta' => ['#1E3A5F', '#E07B00', '#FFFFFF', '#F5F7FA', '#1a7a1a'],
            'fonte_principal' => 'Inter', 'fonte_secundaria' => 'Roboto Mono',
            'estilo_visual' => 'Tecnológico, clean, fundo escuro com destaques em laranja',
            'prompt_dalle' => 'Imagem tecnológica, clean, fundo azul escuro (#1E3A5F), elementos geométricos sutis, ícones de tecnologia/cloud/segurança em destaque, estilo corporativo moderno, sem texto sobreposto, iluminação suave gradiente',
            'brand_book_criado' => true,
        ];
    }

    private function getConteudosMock(): array
    {
        return [
            ['id' => 1, 'tipo' => 'carrossel', 'titulo' => '5 sinais de que sua empresa precisa de backup profissional', 'status' => 'aprovado', 'data' => '2026-06-25', 'slides' => 7],
            ['id' => 2, 'tipo' => 'post', 'titulo' => 'Você sabia? 60% das PMEs que sofrem ransomware fecham em 6 meses', 'status' => 'rascunho', 'data' => '2026-06-24', 'slides' => 1],
            ['id' => 3, 'tipo' => 'carrossel', 'titulo' => 'LGPD para empresas de TI: o que muda em 2026', 'status' => 'agendado', 'data' => '2026-06-26', 'slides' => 8],
            ['id' => 4, 'tipo' => 'story', 'titulo' => 'Dica rápida: como escolher um bom firewall', 'status' => 'publicado', 'data' => '2026-06-22', 'slides' => 1],
        ];
    }

    private function getNoticiasMock(): array
    {
        return [
            ['id' => 1, 'titulo' => 'Novas regras de LGPD para empresas de TI'],
            ['id' => 2, 'titulo' => 'Adoção de IA em MSPs cresce 340%'],
            ['id' => 4, 'titulo' => 'Ransomware mira PMEs brasileiras'],
        ];
    }

    private function gerarConteudoMock(string $tipo, string $tema, string $objetivo): array
    {
        $base = [
            'tipo' => $tipo ?: 'carrossel',
            'tema' => $tema,
            'objetivo' => $objetivo,
            'status' => 'rascunho',
            'legenda' => "📌 {$tema}\n\nVocê sabia que a maioria das empresas ainda não tem esse processo estruturado?\n\nNeste conteúdo, vamos te mostrar passo a passo como implementar de forma simples e eficiente.\n\n💡 Salve este post para consultar depois!\n\n#gestão #consultoria #negócios #empreendedorismo",
            'hashtags' => '#gestão #consultoria #pme #negócios #empreendedorismo #tecnologia',
        ];

        if ($tipo === 'carrossel' || $tipo === '') {
            $base['slides'] = [
                ['numero' => 1, 'tipo' => 'capa', 'texto' => $tema, 'imagem_url' => 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Capa'],
                ['numero' => 2, 'tipo' => 'conteudo', 'texto' => 'O cenário atual mostra que empresas que não se adaptam ficam para trás. Veja os dados:', 'imagem_url' => 'https://placehold.co/1080x1080/E07B00/ffffff?text=Slide+2'],
                ['numero' => 3, 'tipo' => 'conteudo', 'texto' => 'Passo 1: Faça um diagnóstico completo da situação atual da sua empresa.', 'imagem_url' => 'https://placehold.co/1080x1080/1a7a1a/ffffff?text=Slide+3'],
                ['numero' => 4, 'tipo' => 'conteudo', 'texto' => 'Passo 2: Identifique as prioridades e crie um plano de ação com prazos realistas.', 'imagem_url' => 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Slide+4'],
                ['numero' => 5, 'tipo' => 'conteudo', 'texto' => 'Passo 3: Documente seus processos em SOPs claros e treináveis.', 'imagem_url' => 'https://placehold.co/1080x1080/333333/ffffff?text=Slide+5'],
                ['numero' => 6, 'tipo' => 'conteudo', 'texto' => 'Passo 4: Monitore os KPIs e ajuste continuamente.', 'imagem_url' => 'https://placehold.co/1080x1080/E07B00/ffffff?text=Slide+6'],
                ['numero' => 7, 'tipo' => 'cta', 'texto' => '🚀 Quer estruturar sua empresa? Fale com a gente! Link na bio.', 'imagem_url' => 'https://placehold.co/1080x1080/1E3A5F/E07B00?text=CTA'],
            ];
        } else {
            $base['slides'] = [
                ['numero' => 1, 'tipo' => 'unico', 'texto' => $tema . "\n\nConteúdo gerado para engajamento.", 'imagem_url' => 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Post'],
            ];
        }

        return $base;
    }
}
