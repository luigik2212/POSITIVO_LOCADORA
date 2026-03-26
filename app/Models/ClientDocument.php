<?php

declare(strict_types=1);

namespace App\Models;

class ClientDocument extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO client_documents (client_id, nome_original, caminho_arquivo, mime_type, tamanho_bytes) VALUES (:client_id, :nome_original, :caminho_arquivo, :mime_type, :tamanho_bytes)');
        $stmt->execute($data);
    }

    public function allByClient(int $clientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM client_documents WHERE client_id = :client_id ORDER BY data_upload DESC, id DESC');
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function groupedByClientIds(array $clientIds): array
    {
        if ($clientIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM client_documents WHERE client_id IN ({$placeholders}) ORDER BY data_upload DESC, id DESC");
        $stmt->execute(array_values($clientIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $doc) {
            $grouped[(int)$doc['client_id']][] = $doc;
        }

        return $grouped;
    }

    public function findForClient(int $id, int $clientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM client_documents WHERE id = :id AND client_id = :client_id LIMIT 1');
        $stmt->execute(['id' => $id, 'client_id' => $clientId]);
        return $stmt->fetch() ?: null;
    }

    private function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS client_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            nome_original VARCHAR(255) NOT NULL,
            caminho_arquivo VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) DEFAULT NULL,
            tamanho_bytes INT DEFAULT NULL,
            data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
