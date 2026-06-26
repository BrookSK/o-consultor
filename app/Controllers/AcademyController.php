<?php
/**
 * AcademyController — SSO para Academy
 */

class AcademyController
{
    /**
     * Gera JWT e redireciona para a Academy
     */
    public function sso(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();

        $jwt = ApiHelper::gerarJwtAcademy([
            'sub' => $usuario['id'],
            'name' => $usuario['nome'],
            'email' => $usuario['email'],
        ]);

        Logger::acao('SSO Academy acessado', ['usuario_id' => $usuario['id']]);

        // Em produção: redirecionar para URL da Academy com JWT
        // header('Location: https://academy.oconsultor.com.br/sso?token=' . $jwt);

        // Mock: exibir token para testes
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'token' => $jwt,
            'mensagem' => 'Token JWT gerado. Em produção, redirecionaria para a Academy.',
        ]);
        exit;
    }
}
