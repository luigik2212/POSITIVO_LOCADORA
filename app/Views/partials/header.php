<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body data-base-path="<?= esc(appBasePath()) ?>">
<div class="container-fluid">
    <div class="row">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            <?php require __DIR__ . '/topbar.php'; ?>
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger"><?= esc($msg) ?></div>
            <?php endif; ?>
