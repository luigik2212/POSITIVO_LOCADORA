<?php require __DIR__ . '/../partials/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Notificações</h5>
    <small class="text-muted"><?= (int)$totalNotifications ?> itens ativos</small>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        <?php if (empty($notifications)): ?>
            <div class="list-group-item text-muted">Nenhuma notificação ativa.</div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                $targetLink = $notification['link'] ?? '/';
                $openLink = url('/notifications/open?key=' . urlencode((string)($notification['key'] ?? '')) . '&redirect=' . urlencode((string)$targetLink));
                ?>
                <a href="<?= esc($openLink) ?>" class="list-group-item list-group-item-action notification-item <?= empty($notification['read']) ? 'notification-unread' : '' ?>">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold text-<?= esc($notification['severity'] ?? 'secondary') ?>"><?= esc($notification['title'] ?? 'Notificação') ?></span>
                        <small class="text-muted"><?= isset($notification['days_left']) ? ((int)$notification['days_left'] < 0 ? 'Atrasado' : ((int)$notification['days_left'] . 'd')) : '' ?></small>
                    </div>
                    <small class="d-block text-muted"><?= esc($notification['description'] ?? '') ?></small>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
    <nav class="mt-3" aria-label="Paginação notificações">
        <ul class="pagination mb-0">
            <?php
            $prev = max(1, (int)$page - 1);
            $next = min((int)$totalPages, (int)$page + 1);
            ?>
            <li class="page-item <?= (int)$page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/notifications?page=' . $prev . '&per_page=' . (int)$perPage) ?>">Anterior</a>
            </li>
            <?php for ($p = 1; $p <= (int)$totalPages; $p++): ?>
                <li class="page-item <?= $p === (int)$page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('/notifications?page=' . $p . '&per_page=' . (int)$perPage) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= (int)$page >= (int)$totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/notifications?page=' . $next . '&per_page=' . (int)$perPage) ?>">Próxima</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
