<?php

declare(strict_types=1);

namespace App\Models;

class TrafficFine extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO rental_fines (rental_id, auto_infracao, local_infracao, valor, data_hora_multa, data_vencimento, gerar_despesa_financeiro) VALUES (:rental_id,:auto_infracao,:local_infracao,:valor,:data_hora_multa,:data_vencimento,:gerar_despesa_financeiro)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function byRentalIds(array $rentalIds): array
    {
        if (empty($rentalIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rentalIds), '?'));
        $stmt = $this->db->prepare("SELECT rf.*, fe.id AS financial_entry_id
            FROM rental_fines rf
            LEFT JOIN financial_entries fe ON fe.fine_id = rf.id
            WHERE rf.rental_id IN ($placeholders)
            ORDER BY rf.data_hora_multa DESC, rf.id DESC");
        $stmt->execute(array_values($rentalIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $fine) {
            $grouped[(int)$fine['rental_id']][] = $fine;
        }

        return $grouped;
    }

    private function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS rental_fines (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rental_id INT NOT NULL,
            auto_infracao VARCHAR(100) NOT NULL,
            local_infracao VARCHAR(180) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            data_hora_multa DATETIME NOT NULL,
            data_vencimento DATE NOT NULL,
            gerar_despesa_financeiro TINYINT(1) NOT NULL DEFAULT 0,
            data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
