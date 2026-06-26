# O CONSULTOR — Sistema Operacional Empresarial

Plataforma web da Holding Digital para consultoria empresarial, diagnósticos, planos de ação, SOPs gerados por IA e gestão de conteúdo.

## Stack Tecnológica

- **Backend:** PHP 8.2+ (MVC puro, sem framework)
- **Frontend:** HTML5 + Tailwind CSS (CDN) + JavaScript vanilla + Alpine.js
- **Banco de dados:** MySQL 8.0+
- **Servidor:** Apache com mod_rewrite

## Estrutura de Pastas

```
o-consultor/
├── app/
│   ├── Controllers/        # Lógica de negócio
│   ├── Models/             # Acesso ao banco (PDO)
│   ├── Views/              # Templates HTML/PHP
│   │   ├── layouts/        # Layout compartilhado
│   │   ├── auth/           # Login, cadastro, recuperação
│   │   ├── dashboard/      # Painel principal
│   │   ├── diagnostico/    # Diagnóstico empresarial
│   │   ├── plano/          # Planos de ação
│   │   ├── sop/            # Manual operacional
│   │   ├── conteudo/       # Central de conteúdo
│   │   ├── maquina/        # Máquina de conteúdo IA
│   │   ├── parceiros/      # Rede de parceiros
│   │   ├── admin/          # Painel administrativo
│   │   ├── perfil/         # Perfil do usuário
│   │   └── errors/         # Páginas de erro
│   ├── Helpers/            # Auth, Session, CSRF, API, Flash, Logger
│   └── Router.php          # Mapeamento URL → Controller::action
├── config/
│   ├── app.php             # Configurações da aplicação
│   ├── database.php        # Conexão MySQL
│   └── api_keys.php        # Chaves de API externas
├── database/
│   └── migrations/         # Scripts SQL de migração
├── public/
│   ├── index.php           # Front controller
│   ├── .htaccess           # Rewrite rules
│   ├── assets/css/         # Estilos customizados
│   ├── assets/js/          # JavaScript principal
│   └── uploads/            # Uploads de usuários
├── storage/
│   └── logs/               # Logs do sistema
├── .htaccess               # Rewrite raiz → public
├── index.php               # Delegação para public/index.php
└── README.md
```

## Instalação

1. **Clone o repositório**
2. **Configure o Apache** — aponte o DocumentRoot para a raiz do projeto (ou use VirtualHost)
3. **Crie o banco de dados** — execute as migrations SQL em ordem:
   ```bash
   mysql -u root -p < database/migrations/001_criar_estrutura_inicial.sql
   mysql -u root -p o_consultor < database/migrations/002_criar_tabela_configuracoes.sql
   mysql -u root -p o_consultor < database/migrations/003_adicionar_campo_academy_usuarios.sql
   ```
4. **Configure o banco** — edite `config/database.php` com suas credenciais MySQL
5. **Configure APP_URL** — edite `config/app.php` com a URL do seu projeto
6. **Acesse** — navegue para a URL do projeto e faça login
7. **Configure APIs** — acesse `/admin/configuracoes` com o perfil Admin e insira suas chaves de API

## Arquitetura de Configurações

**IMPORTANTE:** Todas as configurações (chaves de API, URLs, secrets, etc.) ficam no **banco de dados** e são gerenciadas pela tela `/admin/configuracoes`.

- **Nenhuma chave** fica em código-fonte, arquivos .env ou config PHP
- A única config em arquivo é a conexão com o banco (`config/database.php`) e a URL base (`config/app.php`)
- Valores sensíveis são criptografados com AES-256-CBC no banco (tabela `configuracoes`)
- O Model `Configuracao.php` lê/salva com cache em sessão
- As migrations SQL ficam em `database/migrations/` — execute manualmente no seu banco

## Primeiro Acesso

Após executar as migrations, o sistema terá os usuários iniciais definidos na migration 001.
Para criar novos usuários, acesse `/admin/usuarios` com o perfil Admin.

## Perfis de Acesso

- **ADMIN_HOLDING** — Acesso total à plataforma
- **CONSULTOR_INTERNO** — Gestão de clientes e diagnósticos
- **CLIENTE** — Acesso ao seu próprio painel e recursos

## Segurança

- Senhas com `password_hash()` (bcrypt)
- CSRF token em todos os formulários POST
- Sanitização de inputs (XSS)
- Prepared statements (SQL Injection)
- Controle de sessão e perfis
- Logging de ações críticas

## Comandos

Não há CLI. A aplicação roda inteiramente via Apache.

## Migrations

As migrations são arquivos `.sql` em `database/migrations/`. Execute manualmente no MySQL:

```bash
# Primeira instalação (rodar em ordem):
mysql -u root -p < database/migrations/001_criar_estrutura_inicial.sql
mysql -u root -p o_consultor < database/migrations/002_criar_tabela_configuracoes.sql
mysql -u root -p o_consultor < database/migrations/003_adicionar_campo_academy_usuarios.sql
```

**Regras:**
- Nunca alterar migrations existentes
- Sempre criar uma nova migration para alterações estruturais
- Migrations são geradas em arquivos `.sql` separados
- Nunca executar migrations automaticamente (sempre manual)

## Identidade Visual

- **Primárias:** Azul profundo (#1E3A5F), Branco (#FFFFFF), Cinza claro (#F5F7FA)
- **Destaque:** Laranja (#E07B00), Verde (#1a7a1a), Vermelho (#CC2222)
- **Tipografia:** Inter (Google Fonts)
