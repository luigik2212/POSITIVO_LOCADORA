<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\NotificationState;

class NotificationController extends Controller
{
    public function index(): void
    {
        $user = authUser();
        if (!$user) {
            $this->redirect('/login');
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(50, (int)($_GET['per_page'] ?? 20)));
        $result = (new \App\Models\NotificationCenter())->paginated((int)$user['id'], $page, $perPage);

        $this->view('notifications/index', [
            'notifications' => $result['items'],
            'page' => $result['current_page'],
            'perPage' => $result['per_page'],
            'totalPages' => $result['total_pages'],
            'totalNotifications' => $result['total'],
        ]);
    }

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
