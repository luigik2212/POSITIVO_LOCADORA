<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppService;

class WhatsAppWebhookController extends Controller
{
    public function verify(): void
    {
        $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

        $service = new WhatsAppService();
        if ($mode === 'subscribe' && $token !== '' && hash_equals($service->getWebhookVerifyToken(), (string)$token)) {
            header('Content-Type: text/plain');
            echo $challenge;
            return;
        }

        http_response_code(403);
        echo 'Token inválido';
    }

    public function receive(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo 'Payload inválido';
            return;
        }

        $service = new WhatsAppService();
        $notificationModel = new WhatsAppNotification();

        $entries = $data['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $statuses = $change['value']['statuses'] ?? [];
                foreach ($statuses as $statusItem) {
                    $messageId = $statusItem['id'] ?? null;
                    $status = $statusItem['status'] ?? null;
                    $timestamp = $statusItem['timestamp'] ?? null;
                    $errorMessage = null;
                    if (!empty($statusItem['errors'][0]['title'])) {
                        $errorMessage = (string)$statusItem['errors'][0]['title'];
                    }

                    if (!$messageId || !$status) {
                        continue;
                    }

                    $notificationModel->updateStatusByMessageId((string)$messageId, (string)$status, is_string($timestamp) ? $timestamp : null, $errorMessage);
                    $service->log('info', 'Webhook status recebido.', [
                        'message_id' => $messageId,
                        'status' => $status,
                        'timestamp' => $timestamp,
                        'error' => $errorMessage,
                    ]);
                }
            }
        }

        echo 'EVENT_RECEIVED';
    }
}
