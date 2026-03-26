<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Positivo Locadora</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="login-page" data-base-path="<?= esc(appBasePath()) ?>">
<div class="login-card card shadow-lg border-0">
  <div class="card-body p-4">
    <h3 class="text-center mb-3">Positivo Locadora</h3>
    <p class="text-muted text-center">Acesse o sistema</p>
    <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
    <form method="POST" action="<?= url('/login') ?>">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label class="form-label">Usuário ou e-mail</label>
        <input type="text" class="form-control" name="login" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" class="form-control" name="senha" required>
      </div>
      <button class="btn btn-primary w-100">Entrar</button>
    </form>
    <small class="d-block mt-3 text-muted">Usuário inicial: admin / 1234</small>
  </div>
</div>
</body>
</html>
