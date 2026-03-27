<?php

declare(strict_types=1);

namespace App\Models;

class WhatsAppNotification extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO whatsapp_notifications (rental_id, client_id, alert_type, phone, template_name, template_language, sent_at, message_id, delivery_status, error_message, payload_summary, response_body) VALUES (:rental_id,:client_id,:alert_type,:phone,:template_name,:template_language,:sent_at,:message_id,:delivery_status,:error_message,:payload_summary,:response_body)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function hasSentForAlert(int $rentalId, string $alertType): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM whatsapp_notifications WHERE rental_id=:rental_id AND alert_type=:alert_type AND delivery_status IN ("queued","sent","delivered","read") LIMIT 1');
        $stmt->execute([
            'rental_id' => $rentalId,
            'alert_type' => $alertType,
        ]);

        return (bool)$stmt->fetch();
    }

    public function latestByRental(int $rentalId, string $alertType): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM whatsapp_notifications WHERE rental_id=:rental_id AND alert_type=:alert_type ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            'rental_id' => $rentalId,
            'alert_type' => $alertType,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function updateStatusByMessageId(string $messageId, string $status, ?string $timestamp = null, ?string $error = null): void
    {
        $sql = 'UPDATE whatsapp_notifications SET delivery_status=:delivery_status, status_updated_at=:status_updated_at, error_message=:error_message WHERE message_id=:message_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'delivery_status' => $status,
            'status_updated_at' => $timestamp ? date('Y-m-d H:i:s', (int)$timestamp) : date('Y-m-d H:i:s'),
            'error_message' => $error,
            'message_id' => $messageId,
        ]);
    }
}
