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

## 6) Integração oficial WhatsApp Cloud API (Meta)

### Variáveis de ambiente
Adicione no `.env` (backend):

```dotenv
WHATSAPP_ACCESS_TOKEN=EAAG...
WHATSAPP_PHONE_NUMBER_ID=1234567890
WHATSAPP_BUSINESS_ACCOUNT_ID=1234567890
WHATSAPP_WEBHOOK_VERIFY_TOKEN=token_forte_unico
WHATSAPP_TEMPLATE_NAME=lembrete_vencimento_locacao
WHATSAPP_TEMPLATE_LANGUAGE=pt_BR
```

> Nunca exponha token no front-end. A integração usa apenas backend PHP.

### Template esperada
A implementação envia **template message** com 4 variáveis no body, nesta ordem:
1. Nome do cliente
2. Nome do veículo
3. Placa
4. Data de vencimento (dd/mm/aaaa)

### Webhook oficial da Meta
- URL de verificação/callback: `https://seu-dominio.com/webhooks/whatsapp`
- Método GET: validação (`hub.challenge`)
- Método POST: recebimento de status (`sent`, `delivered`, `read`, `failed`)
- O `WHATSAPP_WEBHOOK_VERIFY_TOKEN` deve bater com o token configurado no app Meta.

### Rotina automática (7 dias antes)
Script pronto para cron diário:

```bash
php /caminho/do/projeto/scripts/send_due_alerts.php
```

Exemplo de cron (todo dia 08:00):

```cron
0 8 * * * /usr/bin/php /home/usuario/public_html/scripts/send_due_alerts.php >> /home/usuario/public_html/storage/logs/cron.log 2>&1
```

### Logs
- Arquivo de diagnóstico: `storage/logs/whatsapp.log`
- Registra: payload resumido, HTTP code, resposta da API, telefone inválido e falhas de configuração.

### Controle de duplicidade
- Tabela: `whatsapp_notifications`
- O disparo automático ignora locações que já tiveram alerta `due_in_7_days` com status de sucesso/fila.
- Reenvio manual continua disponível na tela de locações.
