# POSITIVO LOCADORA (PHP MVC)

Sistema web administrativo para locadora de carros com PHP + PDO + MySQL + Bootstrap, preparado para rodar em hospedagem compartilhada (ex.: Hostinger) com `index.php` na raiz do `public_html`.

## 1) Estrutura de produção (Hostinger)

Use exatamente esta estrutura dentro de `public_html`:

```text
public_html/
├── app/
├── assets/
├── database/
├── public/                # compatibilidade legada (opcional)
├── uploads/
├── .env                   # recomendado
├── .env.example
├── .htaccess
├── index.php
└── README.md
```

> Observação: a aplicação agora funciona com `index.php` e `.htaccess` na raiz. A pasta `public/` ficou apenas para compatibilidade.

## 2) Configuração rápida

1. Copie variáveis:
   ```bash
   cp .env.example .env
   ```
2. Ajuste credenciais do MySQL no `.env`.
3. Importe o banco:
   ```bash
   mysql -u SEU_USUARIO -p SEU_BANCO < database/schema.sql
   ```
4. Acesse no domínio: `https://seu-dominio.com/login`

## 3) Usuário inicial

- Login: `admin`
- Senha: `1234`
- Seed em `database/schema.sql` com senha em hash seguro (`password_hash`).

## 4) Variáveis de ambiente

Exemplo (`.env`):

```dotenv
APP_ENV=production
APP_DEBUG=0
APP_URL=https://seu-dominio.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=locadora
DB_USER=seu_usuario_mysql
DB_PASS=sua_senha_mysql
```

### Debug local

Para diagnosticar erro HTTP 500 em ambiente de desenvolvimento, use:

```dotenv
APP_ENV=local
APP_DEBUG=1
```

Em produção, mantenha `APP_DEBUG=0`.

## 5) Notas de compatibilidade Hostinger

- Projeto sem Docker/Node/Composer obrigatório.
- Funciona com PHP 8.1+ / 8.2.
- Rotas amigáveis via `.htaccess` na raiz.
- Assets e uploads servidos pela raiz (`/assets`, `/uploads`).
- Se existir estrutura antiga em `/public`, o `.htaccess` mantém fallback automático.
