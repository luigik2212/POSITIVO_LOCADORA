# POSITIVO LOCADORA (PHP MVC)

Sistema web administrativo para locadora de carros com PHP + PDO + MySQL + Bootstrap.

## 1) Arquitetura

Estrutura em MVC para facilitar manutenção e expansão futura:

- `public/` ponto de entrada (`index.php`) e assets estáticos
- `app/Core` roteador, conexão PDO, controller base
- `app/Controllers` regras de fluxo por módulo
- `app/Models` acesso a dados com prepared statements
- `app/Views` telas Bootstrap responsivas
- `database/schema.sql` criação de banco + seed inicial

## 2) Instalação local

1. Copie variáveis:
   ```bash
   cp .env.example .env
   ```
2. Ajuste credenciais MySQL no `.env`.
3. Crie banco e tabelas:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
4. Rode servidor PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
5. Acesse: `http://localhost:8000/login`

## 3) Usuário inicial

- Login: `admin`
- Senha: `1234`
- Senha armazenada com hash seguro (`password_hash`).

## 4) Módulos implementados

- Autenticação com sessão, logout e proteção de rotas
- Dashboard com indicadores e listas operacionais
- Veículos (CRUD com modal, busca, inativação, quilometragem)
- Clientes (CRUD, busca, histórico de locações)
- Locações (criação, devolução, cancelamento, filtros)
- Regra de cobrança diária/semanal/mensal com cálculo automático
- Checklist de entrega/devolução com upload de foto e vídeo
- Manutenções (registro, conclusão, vínculo com status do veículo)
- Finanças (receitas/despesas, filtros e consolidação)

## 5) Decisões técnicas importantes

- **PDO + prepared statements** em todos os módulos (proteção contra SQL injection).
- **Router simples** com flag de autenticação por rota.
- **CSRF token** para ações POST.
- **Uploads controlados por MIME** (imagem/vídeo) em checklist.
- **Separação de responsabilidades** entre camada de view/controller/model.

## 6) Próximos passos sugeridos

- Controle de permissões por perfil (RBAC).
- API REST para integrações.
- Logs de auditoria.
- Testes automatizados (PHPUnit) e migrations.
