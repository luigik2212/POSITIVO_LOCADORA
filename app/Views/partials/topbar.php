<?php
$notifications = $globalNotifications ?? [];
$notificationsCount = (int)($globalNotificationsCount ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
    <h4 class="mb-0">Painel Administrativo</h4>
    <div class="dropdown">
        <button class="btn btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notificações">
            <span aria-hidden="true">🔔</span>
            <?php if ($notificationsCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notificationsCount > 99 ? '99+' : $notificationsCount ?></span>
            <?php endif; ?>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 notifications-menu">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <strong>Notificações</strong>
                <small class="text-muted"><?= $notificationsCount ?> não lidas</small>
            </div>
            <?php if (empty($notifications)): ?>
                <div class="px-3 py-3 text-muted small">Nenhum alerta ativo no momento.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
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
                </div>
                <div class="px-3 py-2 border-top text-end">
                    <a href="<?= url('/notifications') ?>" class="small">Ver todas</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
