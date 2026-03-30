<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\NotificationCenter;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        if (isAuthenticated()) {
            $notifications = (new NotificationCenter())->allActive();
            $data['globalNotifications'] = $notifications;
            $data['globalNotificationsCount'] = count($notifications);
        }

        extract($data);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}
