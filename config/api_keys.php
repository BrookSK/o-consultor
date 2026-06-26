<?php
/**
 * Chaves de API — Carregadas do banco de dados
 * O Consultor — Sistema Operacional Empresarial
 *
 * TODAS as configurações (APIs, Academy, etc.) são gerenciadas
 * exclusivamente pela tela de configurações do sistema (/admin/configuracoes).
 * Este arquivo apenas carrega os valores da tabela `configuracoes` no banco.
 *
 * Nenhuma chave, URL ou secret fica em código-fonte.
 * A única config em arquivo é a conexão com o banco (config/database.php).
 */

// Carregamento dinâmico das configurações do banco
// O model Configuracao faz cache em sessão para evitar queries repetidas.
// As constantes são definidas após a conexão com o banco estar disponível.

// Nota: este arquivo é incluído DEPOIS de database.php e ANTES dos controllers.
// O carregamento efetivo ocorre no public/index.php após autoload.
