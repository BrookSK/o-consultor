<?php
/**
 * VideoController — Módulo Criador de Vídeos (Reels)
 *
 * Transforma um POST já criado (conteudos_marca) em um vídeo vertical,
 * reutilizando as imagens JÁ geradas do post. NÃO altera o fluxo de posts nem
 * a geração de imagens existente. Tudo é vinculado pelo ID do post (conteudo_id).
 */

class VideoController
{
    /** Garante que as tabelas do módulo existam (idempotente). */
    private function garantirTabelas(): void
    {
        Database::execute(
            "CREATE TABLE IF NOT EXISTS video_projetos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conteudo_id INT UNSIGNED NOT NULL,
                usuario_id INT UNSIGNED NOT NULL,
                estado_json MEDIUMTEXT NULL,
                video_url VARCHAR(500) NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NULL,
                UNIQUE KEY uk_conteudo (conteudo_id),
                INDEX idx_usuario (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        Database::execute(
            "CREATE TABLE IF NOT EXISTS fila_videos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                projeto_id INT UNSIGNED NOT NULL,
                conteudo_id INT UNSIGNED NOT NULL,
                status ENUM('pendente','processando','concluido','erro','cancelado') NOT NULL DEFAULT 'pendente',
                progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
                etapa VARCHAR(120) NULL,
                tentativas INT UNSIGNED NOT NULL DEFAULT 0,
                mensagem VARCHAR(500) NULL,
                video_url VARCHAR(500) NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NULL,
                INDEX idx_status (status),
                INDEX idx_projeto (projeto_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** Carrega o conteúdo/post do usuário logado (com as imagens dos slides). */
    private function carregarConteudo(int $conteudoId): ?array
    {
        try {
            $c = Database::queryOne(
                "SELECT id, marca_id, tipo, tema, slides, legenda, hashtags
                 FROM conteudos_marca WHERE id = :id AND usuario_id = :uid",
                ['id' => $conteudoId, 'uid' => Auth::id()]
            );
        } catch (\Throwable $e) {
            return null;
        }
        return $c ?: null;
    }

    /** Extrai as URLs das imagens já geradas do post (na ordem dos slides). */
    private function imagensDoConteudo(array $conteudo): array
    {
        $slides = json_decode($conteudo['slides'] ?? '[]', true) ?: [];
        $imgs = [];
        foreach ($slides as $s) {
            $u = trim((string) ($s['imagem_url'] ?? ''));
            if ($u !== '') $imgs[] = $u;
        }
        // Fallback: se os slides não têm imagem_url salva, busca na tabela
        // imagens_conteudo (imagens ativas geradas para este post, por slide).
        if (empty($imgs)) {
            try {
                $rows = Database::query(
                    "SELECT slide_index, caminho_local FROM imagens_conteudo
                     WHERE conteudo_id = :cid AND status = 'ativo' AND caminho_local IS NOT NULL
                     ORDER BY slide_index ASC, id DESC",
                    ['cid' => (int) $conteudo['id']]
                );
                $vistos = [];
                foreach ($rows as $r) {
                    $idx = (int) $r['slide_index'];
                    if (isset($vistos[$idx])) continue; // 1 por slide (a mais recente)
                    $cam = trim((string) ($r['caminho_local'] ?? ''));
                    if ($cam === '') continue;
                    $vistos[$idx] = true;
                    // caminho_local costuma ser relativo (/uploads/...); vira URL absoluta.
                    $imgs[] = (strpos($cam, 'http') === 0) ? $cam : (APP_URL . $cam);
                }
            } catch (\Throwable $e) { /* segue sem fallback */ }
        }
        return $imgs;
    }

    /**
     * Tela do Mini Editor de Vídeo. Abre pelo ID do post; cria o projeto se não
     * existir, ou reabre o estado salvo. Rota: /maquina-de-conteudo/video/{id}
     */
    public function editor(): void
    {
        Auth::proteger();
        $this->garantirTabelas();
        $conteudoId = (int) ($_GET['conteudo_id'] ?? 0);

        $conteudo = $this->carregarConteudo($conteudoId);
        if (!$conteudo) {
            Flash::set('erro', 'Conteúdo não encontrado.');
            header('Location: ' . APP_URL . '/maquina-de-conteudo');
            exit;
        }

        $imagens = $this->imagensDoConteudo($conteudo);

        // Projeto existente ou novo (vinculado ao post).
        $projeto = Database::queryOne(
            "SELECT id, estado_json, video_url FROM video_projetos WHERE conteudo_id = :cid AND usuario_id = :uid",
            ['cid' => $conteudoId, 'uid' => Auth::id()]
        );
        if (!$projeto) {
            Database::execute(
                "INSERT INTO video_projetos (conteudo_id, usuario_id, estado_json, criado_em) VALUES (:cid, :uid, NULL, NOW())",
                ['cid' => $conteudoId, 'uid' => Auth::id()]
            );
            $projeto = ['id' => (int) Database::lastInsertId(), 'estado_json' => null, 'video_url' => null];
        }

        // Estado salvo (ou monta um estado inicial a partir das imagens do post).
        $estado = json_decode((string) ($projeto['estado_json'] ?? ''), true);
        if (!is_array($estado) || empty($estado['imagens'])) {
            $estado = $this->estadoInicial($imagens, $conteudo);
        }

        $dados = [
            'conteudo' => $conteudo,
            'projeto_id' => (int) $projeto['id'],
            'imagens_post' => $imagens,
            'estado' => $estado,
            'video_url' => $projeto['video_url'] ?? '',
        ];
        require VIEW_PATH . '/maquina/video-editor.php';
    }

    /** Estado inicial do editor: cada imagem do post vira um clipe de 3s. */
    private function estadoInicial(array $imagens, array $conteudo): array
    {
        $clipes = [];
        foreach ($imagens as $u) {
            $clipes[] = ['url' => $u, 'duracao' => 3, 'transicao' => 'fade', 'movimento' => 'zoom_in'];
        }
        // Texto sugerido para narração = legenda do post (sem hashtags).
        $textoNarracao = trim(preg_replace('/#\S+/', '', (string) ($conteudo['legenda'] ?? '')));
        return [
            'imagens' => $clipes,
            'transicao_velocidade' => 0.5,
            'movimento_auto' => false,
            'narracao' => ['url' => '', 'volume' => 1.0, 'inicio' => 0, 'corte_inicio' => 0, 'corte_fim' => 0, 'texto' => $textoNarracao, 'voz_id' => '', 'versoes' => []],
            'musica' => ['url' => '', 'volume' => 0.5, 'loop' => true, 'fade_in' => 1, 'fade_out' => 1, 'inicio' => 0, 'corte_inicio' => 0, 'corte_fim' => 0, 'reduzir_na_narracao' => true],
            'textos' => [],
            'formato' => ['w' => 1080, 'h' => 1920, 'fps' => 30],
        ];
    }

    /** Salva o estado do projeto (JSON) vinculado ao post. */
    public function salvarProjeto(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $this->garantirTabelas();

        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $estadoJson = (string) ($_POST['estado'] ?? '');
        if ($conteudoId <= 0 || $estadoJson === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros inválidos.']);
            exit;
        }
        // Valida JSON.
        if (json_decode($estadoJson, true) === null) {
            echo json_encode(['sucesso' => false, 'erro' => 'Estado inválido.']);
            exit;
        }
        try {
            Database::execute(
                "INSERT INTO video_projetos (conteudo_id, usuario_id, estado_json, criado_em, atualizado_em)
                 VALUES (:cid, :uid, :e, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE estado_json = :e2, atualizado_em = NOW()",
                ['cid' => $conteudoId, 'uid' => Auth::id(), 'e' => $estadoJson, 'e2' => $estadoJson]
            );
            echo json_encode(['sucesso' => true]);
        } catch (\Throwable $ex) {
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar projeto.']);
        }
        exit;
    }

    /** Diretório de mídias do projeto (uploads/videos/{conteudoId}). */
    private function dirProjeto(int $conteudoId): string
    {
        $dir = PUBLIC_PATH . '/uploads/videos/' . $conteudoId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** Upload de áudio (narração ou música). campo: 'audio', tipo: narracao|musica */
    public function uploadAudio(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $tipo = ($_POST['tipo'] ?? 'narracao') === 'musica' ? 'musica' : 'narracao';
        if ($conteudoId <= 0 || empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['sucesso' => false, 'erro' => 'Arquivo inválido.']);
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
        $permit = ['mp3', 'wav', 'aac', 'm4a', 'ogg'];
        if (!in_array($ext, $permit, true)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Formato não permitido. Use: ' . implode(', ', $permit)]);
            exit;
        }
        $dir = $this->dirProjeto($conteudoId);
        if (!is_dir($dir) || !is_writable($dir)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão de escrita em uploads/videos. Ajuste as permissões da pasta no servidor.']);
            exit;
        }
        $nome = $tipo . '_' . uniqid() . '.' . $ext;
        $rel = '/uploads/videos/' . $conteudoId . '/' . $nome;
        $destino = $dir . '/' . $nome;
        // move_uploaded_file é o correto; fallback para copy caso o SAPI difira.
        if (!@move_uploaded_file($_FILES['audio']['tmp_name'], $destino) && !@copy($_FILES['audio']['tmp_name'], $destino)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar o áudio no servidor.']);
            exit;
        }
        Logger::acao('Áudio de vídeo enviado', ['conteudo_id' => $conteudoId, 'tipo' => $tipo]);
        echo json_encode(['sucesso' => true, 'url' => APP_URL . $rel]);
        exit;
    }

    /** Upload de imagem adicional (opcional — além das do post). */
    public function uploadImagem(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        if ($conteudoId <= 0 || empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['sucesso' => false, 'erro' => 'Arquivo inválido.']);
            exit;
        }
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Formato não permitido.']);
            exit;
        }
        $dir = $this->dirProjeto($conteudoId);
        $nome = 'img_' . uniqid() . '.' . $ext;
        $rel = '/uploads/videos/' . $conteudoId . '/' . $nome;
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . '/' . $nome)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar a imagem.']);
            exit;
        }
        echo json_encode(['sucesso' => true, 'url' => APP_URL . $rel]);
        exit;
    }

    /** Lista as vozes da ElevenLabs (JSON) para o seletor. */
    public function vozes(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');
        echo json_encode(ApiHelper::elevenLabsVozes());
        exit;
    }

    /** Gera a narração via ElevenLabs (backend) e devolve a URL do MP3. */
    public function gerarNarracao(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        @set_time_limit(180);

        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $texto = trim((string) ($_POST['texto'] ?? ''));
        $voz = trim((string) ($_POST['voz_id'] ?? ''));
        if ($conteudoId <= 0 || $texto === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o texto da narração.']);
            exit;
        }
        $dir = $this->dirProjeto($conteudoId);
        $nome = 'narracao_' . uniqid() . '.mp3';
        $abs = $dir . '/' . $nome;
        $rel = '/uploads/videos/' . $conteudoId . '/' . $nome;

        $res = ApiHelper::elevenLabsGerarNarracao($texto, $voz, $abs);
        if (empty($res['sucesso'])) {
            echo json_encode(['sucesso' => false, 'erro' => $res['erro'] ?? 'Falha ao gerar narração.']);
            exit;
        }
        Logger::acao('Narração ElevenLabs gerada', ['conteudo_id' => $conteudoId]);
        echo json_encode(['sucesso' => true, 'url' => APP_URL . $rel]);
        exit;
    }

    /** Enfileira a exportação do vídeo (FFmpeg roda em background). */
    public function exportar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $this->garantirTabelas();

        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $estadoJson = (string) ($_POST['estado'] ?? '');
        if ($conteudoId <= 0 || $estadoJson === '' || json_decode($estadoJson, true) === null) {
            echo json_encode(['sucesso' => false, 'erro' => 'Estado inválido.']);
            exit;
        }
        // Salva o estado antes de exportar (garante restauração).
        Database::execute(
            "INSERT INTO video_projetos (conteudo_id, usuario_id, estado_json, criado_em, atualizado_em)
             VALUES (:cid, :uid, :e, NOW(), NOW())
             ON DUPLICATE KEY UPDATE estado_json = :e2, atualizado_em = NOW()",
            ['cid' => $conteudoId, 'uid' => Auth::id(), 'e' => $estadoJson, 'e2' => $estadoJson]
        );
        $projeto = Database::queryOne("SELECT id FROM video_projetos WHERE conteudo_id = :cid", ['cid' => $conteudoId]);
        $projetoId = (int) ($projeto['id'] ?? 0);

        // Enfileira (uma exportação por vez por projeto).
        Database::execute(
            "INSERT INTO fila_videos (projeto_id, conteudo_id, status, progresso, etapa, criado_em, atualizado_em)
             VALUES (:pid, :cid, 'pendente', 0, 'Na fila', NOW(), NOW())",
            ['pid' => $projetoId, 'cid' => $conteudoId]
        );
        echo json_encode(['sucesso' => true, 'enfileirado' => true, 'projeto_id' => $projetoId]);
        exit;
    }

    /** Status da exportação mais recente do projeto (polling do front). */
    public function statusExportacao(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');
        $conteudoId = (int) ($_GET['conteudo_id'] ?? 0);
        try {
            $item = Database::queryOne(
                "SELECT status, progresso, etapa, mensagem, video_url FROM fila_videos
                 WHERE conteudo_id = :cid ORDER BY id DESC LIMIT 1",
                ['cid' => $conteudoId]
            );
        } catch (\Throwable $e) {
            $item = null;
        }
        if (!$item) {
            echo json_encode(['sucesso' => true, 'status' => 'nenhum']);
            exit;
        }
        echo json_encode([
            'sucesso' => true,
            'status' => $item['status'],
            'progresso' => (int) $item['progresso'],
            'etapa' => $item['etapa'],
            'mensagem' => $item['mensagem'],
            'video_url' => $item['video_url'] ? APP_URL . $item['video_url'] : '',
        ]);
        exit;
    }

    /**
     * Processa a fila de vídeos em BACKGROUND (responde na hora e continua),
     * seguindo o mesmo padrão do worker de imagens. Renderiza com FFmpeg.
     */
    public function processarFilaBackground(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');

        $lockFile = sys_get_temp_dir() . '/oconsultor_videos.lock';
        $lock = @fopen($lockFile, 'c');
        $temLock = $lock && @flock($lock, LOCK_EX | LOCK_NB);

        echo json_encode(['sucesso' => true, 'iniciado' => true]);
        if (function_exists('fastcgi_finish_request')) { @session_write_close(); @fastcgi_finish_request(); }
        else { @ob_end_flush(); @flush(); }

        if ($lock && !$temLock) { fclose($lock); return; }
        @set_time_limit(0);
        @ignore_user_abort(true);

        try {
            $this->garantirTabelas();
            $inicio = time();
            while ((time() - $inicio) < 900) { // até ~15 min por execução
                $item = Database::queryOne(
                    "SELECT * FROM fila_videos WHERE status = 'pendente'
                        OR (status='processando' AND atualizado_em < (NOW() - INTERVAL 300 SECOND))
                     ORDER BY id ASC LIMIT 1"
                );
                if (!$item) break;
                $this->renderizarVideo($item);
            }
        } catch (\Throwable $e) {
            Logger::error('Erro no processamento background de vídeo: ' . $e->getMessage());
        } finally {
            if ($temLock) { @flock($lock, LOCK_UN); }
            if ($lock) { @fclose($lock); }
        }
        exit;
    }

    /** Atualiza progresso/etapa de um item da fila. */
    private function progresso(int $filaId, int $pct, string $etapa): void
    {
        try {
            Database::execute(
                "UPDATE fila_videos SET progresso = :p, etapa = :e, status='processando', atualizado_em=NOW() WHERE id = :id",
                ['p' => max(0, min(100, $pct)), 'e' => $etapa, 'id' => $filaId]
            );
        } catch (\Throwable $e) { /* ignora */ }
    }

    /**
     * Renderiza o vídeo com FFmpeg a partir do estado do projeto.
     * Abordagem simples e robusta: cada imagem vira um trecho com duração e
     * movimento (zoompan), concatenados; áudio de narração/música mixado.
     */
    private function renderizarVideo(array $item): void
    {
        $filaId = (int) $item['id'];
        $conteudoId = (int) $item['conteudo_id'];
        Database::execute("UPDATE fila_videos SET status='processando', tentativas=tentativas+1, atualizado_em=NOW() WHERE id=:id", ['id' => $filaId]);

        try {
            $ffmpeg = $this->ffmpegBin();
            if ($ffmpeg === '') {
                throw new \RuntimeException('FFmpeg não encontrado no servidor. Configure "ffmpeg_bin" em Admin > Configurações.');
            }

            $proj = Database::queryOne("SELECT estado_json FROM video_projetos WHERE id = :id", ['id' => (int) $item['projeto_id']]);
            $estado = json_decode((string) ($proj['estado_json'] ?? ''), true);
            if (!is_array($estado) || empty($estado['imagens'])) {
                throw new \RuntimeException('Projeto sem imagens.');
            }

            $this->progresso($filaId, 5, 'Preparando imagens');
            $dir = $this->dirProjeto($conteudoId);
            $tmp = $dir . '/tmp_' . $filaId;
            if (!is_dir($tmp)) @mkdir($tmp, 0755, true);

            $w = (int) ($estado['formato']['w'] ?? 1080);
            $h = (int) ($estado['formato']['h'] ?? 1920);
            $fps = (int) ($estado['formato']['fps'] ?? 30);

            // 1) Gera um segmento de vídeo por imagem (com duração + movimento).
            $segmentos = [];
            $totalImgs = count($estado['imagens']);
            foreach ($estado['imagens'] as $i => $clip) {
                $imgAbs = $this->urlParaAbs((string) ($clip['url'] ?? ''));
                if ($imgAbs === '' || !is_file($imgAbs)) continue;
                $dur = max(1, (float) ($clip['duracao'] ?? 3));
                $mov = (string) ($clip['movimento'] ?? 'estatico');
                $seg = $tmp . '/seg_' . $i . '.mp4';
                $vf = $this->filtroMovimento($mov, $w, $h, $fps, $dur);
                $cmd = escapeshellarg($ffmpeg) . ' -y -loop 1 -i ' . escapeshellarg($imgAbs)
                    . ' -t ' . $dur . ' -r ' . $fps
                    . ' -vf ' . escapeshellarg($vf)
                    . ' -c:v libx264 -pix_fmt yuv420p -an ' . escapeshellarg($seg) . ' 2>&1';
                exec($cmd, $out, $rc);
                if ($rc === 0 && is_file($seg)) $segmentos[] = $seg;
                $this->progresso($filaId, 5 + (int) (35 * (($i + 1) / max(1, $totalImgs))), 'Renderizando imagem ' . ($i + 1) . '/' . $totalImgs);
            }
            if (empty($segmentos)) {
                throw new \RuntimeException('Nenhum segmento de vídeo gerado.');
            }

            // 2) Concatena os segmentos.
            $this->progresso($filaId, 45, 'Juntando cenas');
            $listaTxt = $tmp . '/lista.txt';
            $conteudoLista = '';
            foreach ($segmentos as $s) { $conteudoLista .= "file '" . str_replace("'", "'\\''", $s) . "'\n"; }
            file_put_contents($listaTxt, $conteudoLista);
            $videoSemAudio = $tmp . '/video_mudo.mp4';
            $cmdConcat = escapeshellarg($ffmpeg) . ' -y -f concat -safe 0 -i ' . escapeshellarg($listaTxt)
                . ' -c:v libx264 -pix_fmt yuv420p -r ' . $fps . ' ' . escapeshellarg($videoSemAudio) . ' 2>&1';
            exec($cmdConcat, $o2, $rc2);
            if ($rc2 !== 0 || !is_file($videoSemAudio)) {
                throw new \RuntimeException('Falha ao concatenar as cenas.');
            }

            // 3) Áudio (narração + música com mixagem opcional).
            $this->progresso($filaId, 65, 'Processando áudio');
            $narrAbs = $this->urlParaAbs((string) ($estado['narracao']['url'] ?? ''));
            $musAbs = $this->urlParaAbs((string) ($estado['musica']['url'] ?? ''));
            $volNarr = (float) ($estado['narracao']['volume'] ?? 1.0);
            $volMus = (float) ($estado['musica']['volume'] ?? 0.5);

            $saidaFinal = $dir . '/reels_' . $filaId . '.mp4';
            $temNarr = $narrAbs !== '' && is_file($narrAbs);
            $temMus = $musAbs !== '' && is_file($musAbs);

            if (!$temNarr && !$temMus) {
                // Sem áudio: usa o vídeo mudo direto.
                copy($videoSemAudio, $saidaFinal);
            } else {
                $inputs = ' -i ' . escapeshellarg($videoSemAudio);
                $filtros = [];
                $mapAudio = '';
                if ($temNarr && $temMus) {
                    $inputs .= ' -i ' . escapeshellarg($narrAbs) . ' -i ' . escapeshellarg($musAbs);
                    $reduz = !empty($estado['musica']['reduzir_na_narracao']);
                    if ($reduz) {
                        // Sidechaincompress: abaixa a música quando há narração.
                        $filtros[] = '[1:a]volume=' . $volNarr . '[na]';
                        $filtros[] = '[2:a]volume=' . $volMus . '[mu]';
                        $filtros[] = '[mu][na]sidechaincompress=threshold=0.03:ratio=8:attack=5:release=300[muc]';
                        $filtros[] = '[na][muc]amix=inputs=2:duration=first:dropout_transition=2[aout]';
                    } else {
                        $filtros[] = '[1:a]volume=' . $volNarr . '[na]';
                        $filtros[] = '[2:a]volume=' . $volMus . '[mu]';
                        $filtros[] = '[na][mu]amix=inputs=2:duration=first:dropout_transition=2[aout]';
                    }
                    $mapAudio = '[aout]';
                } elseif ($temNarr) {
                    $inputs .= ' -i ' . escapeshellarg($narrAbs);
                    $filtros[] = '[1:a]volume=' . $volNarr . '[aout]';
                    $mapAudio = '[aout]';
                } else {
                    $inputs .= ' -i ' . escapeshellarg($musAbs);
                    $filtros[] = '[1:a]volume=' . $volMus . '[aout]';
                    $mapAudio = '[aout]';
                }
                $filterComplex = implode(';', $filtros);
                $cmdFinal = escapeshellarg($ffmpeg) . ' -y' . $inputs
                    . ' -filter_complex ' . escapeshellarg($filterComplex)
                    . ' -map 0:v -map ' . escapeshellarg($mapAudio)
                    . ' -c:v libx264 -pix_fmt yuv420p -c:a aac -b:a 192k -shortest '
                    . escapeshellarg($saidaFinal) . ' 2>&1';
                exec($cmdFinal, $o3, $rc3);
                if ($rc3 !== 0 || !is_file($saidaFinal)) {
                    // Fallback: entrega ao menos o vídeo mudo.
                    copy($videoSemAudio, $saidaFinal);
                }
            }

            $this->progresso($filaId, 92, 'Finalizando');
            $relFinal = '/uploads/videos/' . $conteudoId . '/reels_' . $filaId . '.mp4';
            $urlFinal = APP_URL . $relFinal;

            // Limpa temporários.
            foreach (glob($tmp . '/*') ?: [] as $f) { @unlink($f); }
            @rmdir($tmp);

            Database::execute("UPDATE fila_videos SET status='concluido', progresso=100, etapa='Concluído', video_url=:u, atualizado_em=NOW() WHERE id=:id",
                ['u' => $relFinal, 'id' => $filaId]);
            Database::execute("UPDATE video_projetos SET video_url=:u, atualizado_em=NOW() WHERE id=:pid",
                ['u' => $relFinal, 'pid' => (int) $item['projeto_id']]);
            Logger::acao('Vídeo exportado', ['conteudo_id' => $conteudoId, 'url' => $relFinal]);
        } catch (\Throwable $e) {
            $novoStatus = ((int) ($item['tentativas'] ?? 0) >= 2) ? 'erro' : 'pendente';
            Database::execute("UPDATE fila_videos SET status=:s, mensagem=:m, atualizado_em=NOW() WHERE id=:id",
                ['s' => $novoStatus, 'm' => substr($e->getMessage(), 0, 490), 'id' => $filaId]);
            Logger::error('Falha ao renderizar vídeo: ' . $e->getMessage());
        }
    }

    /** Filtro FFmpeg de movimento (Ken Burns simples) por tipo. */
    private function filtroMovimento(string $mov, int $w, int $h, int $fps, float $dur): string
    {
        $frames = max(1, (int) round($dur * $fps));
        // Base: escala/crop para preencher o formato vertical.
        $base = "scale={$w}:{$h}:force_original_aspect_ratio=increase,crop={$w}:{$h}";
        // zoompan trabalha melhor sobre uma escala maior; usamos d=frames.
        switch ($mov) {
            case 'zoom_in':
                return $base . ",zoompan=z='min(zoom+0.0015,1.2)':d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'zoom_out':
                return $base . ",zoompan=z='if(lte(zoom,1.0),1.2,max(1.0,zoom-0.0015))':d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'esquerda_direita':
                return $base . ",zoompan=z=1.15:x='(iw-iw/zoom)*(on/{$frames})':y=0:d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'direita_esquerda':
                return $base . ",zoompan=z=1.15:x='(iw-iw/zoom)*(1-on/{$frames})':y=0:d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'cima_baixo':
                return $base . ",zoompan=z=1.15:x=0:y='(ih-ih/zoom)*(on/{$frames})':d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'baixo_cima':
                return $base . ",zoompan=z=1.15:x=0:y='(ih-ih/zoom)*(1-on/{$frames})':d={$frames}:s={$w}x{$h}:fps={$fps}";
            case 'estatico':
            default:
                return $base . ",format=yuv420p";
        }
    }

    /** Converte uma URL pública (APP_URL/...) em caminho absoluto no disco. */
    private function urlParaAbs(string $url): string
    {
        if ($url === '') return '';
        // Remove querystring.
        $url = preg_replace('/\?.*$/', '', $url);
        $base = rtrim(APP_URL, '/');
        if (strpos($url, $base) === 0) {
            $rel = substr($url, strlen($base));
            return PUBLIC_PATH . $rel;
        }
        // Caminho relativo direto (/uploads/...).
        if (strpos($url, '/uploads/') === 0) return PUBLIC_PATH . $url;
        return '';
    }

    /** Localiza o binário do FFmpeg (config ou caminhos comuns). */
    private function ffmpegBin(): string
    {
        $cfg = trim((string) Configuracao::get('ffmpeg_bin', ''));
        if ($cfg !== '' && @is_executable($cfg)) return $cfg;
        foreach (['/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg', '/opt/ffmpeg/bin/ffmpeg', 'ffmpeg'] as $c) {
            if ($c === 'ffmpeg') return 'ffmpeg'; // deixa o PATH resolver
            if (@is_executable($c)) return $c;
        }
        return 'ffmpeg';
    }
}
