<?php

declare(strict_types=1);

namespace App\Models;

class NotificationState extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT notification_key, viewed_at, resolved_at FROM notification_states WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(string)$row['notification_key']] = [
                'viewed_at' => $row['viewed_at'] ?? null,
                'resolved_at' => $row['resolved_at'] ?? null,
            ];
        }

        return $map;
    }

    public function markViewed(int $userId, string $key): void
    {
        $stmt = $this->db->prepare("INSERT INTO notification_states (user_id, notification_key, viewed_at)
            VALUES (:user_id, :notification_key, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = IFNULL(viewed_at, VALUES(viewed_at))");
        $stmt->execute([
            'user_id' => $userId,
            'notification_key' => $key,
        ]);
    }

    public function markResolved(int $userId, string $key): void
    {
        $stmt = $this->db->prepare("INSERT INTO notification_states (user_id, notification_key, viewed_at, resolved_at)
            VALUES (:user_id, :notification_key, NOW(), NOW())
            ON DUPLICATE KEY UPDATE resolved_at = NOW(), viewed_at = IFNULL(viewed_at, NOW())");
        $stmt->execute([
            'user_id' => $userId,
            'notification_key' => $key,
        ]);
    }

    private function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS notification_states (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notification_key VARCHAR(190) NOT NULL,
            viewed_at DATETIME DEFAULT NULL,
            resolved_at DATETIME DEFAULT NULL,
            data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_notification_user_key (user_id, notification_key),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
