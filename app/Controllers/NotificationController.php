<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificationState;

class NotificationController extends Controller
{
    public function open(): void
    {
        $user = authUser();
        if (!$user) {
            $this->redirect('/login');
        }

        $key = trim((string)($_GET['key'] ?? ''));
        $redirect = trim((string)($_GET['redirect'] ?? '/'));

        if ($key !== '') {
            (new NotificationState())->markViewed((int)$user['id'], $key);
        }

        if ($redirect === '' || $redirect[0] !== '/') {
            $redirect = '/';
        }

        $this->redirect($redirect);
    }
}
