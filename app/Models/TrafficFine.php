<?php

declare(strict_types=1);

namespace App\Models;

class TrafficFine extends BaseModel
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PAGA = 'paga';
    public const STATUS_VENCIDA = 'vencida';
    public const STATUS_CANCELADA = 'cancelada';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
        $this->ensureColumns();
    }

    public function all(array $filters = []): array
    {
        $sql = "SELECT rf.*, r.id AS rental_codigo, c.nome_completo AS cliente_nome, v.nome AS veiculo_nome, v.placa,
                CASE
                    WHEN rf.status = 'pendente' AND rf.data_vencimento < CURDATE() THEN 'vencida'
                    ELSE rf.status
                END AS status_exibicao,
                fe.id AS financial_entry_id
            FROM rental_fines rf
            JOIN rentals r ON r.id = rf.rental_id
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN financial_entries fe ON fe.fine_id = rf.id
            WHERE 1=1";
        $params = [];

        if (!empty($filters['rental_id'])) {
            $sql .= ' AND rf.rental_id = :rental_id';
            $params['rental_id'] = (int)$filters['rental_id'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= ' AND r.client_id = :client_id';
            $params['client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND r.vehicle_id = :vehicle_id';
            $params['vehicle_id'] = (int)$filters['vehicle_id'];
        }
        if (!empty($filters['placa'])) {
            $sql .= ' AND v.placa LIKE :placa';
            $params['placa'] = '%' . trim((string)$filters['placa']) . '%';
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(rf.data_hora_multa) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(rf.data_hora_multa) <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['vencimento_from'])) {
            $sql .= ' AND rf.data_vencimento >= :vencimento_from';
            $params['vencimento_from'] = $filters['vencimento_from'];
        }
        if (!empty($filters['vencimento_to'])) {
            $sql .= ' AND rf.data_vencimento <= :vencimento_to';
            $params['vencimento_to'] = $filters['vencimento_to'];
        }
        if (($filters['financial_status'] ?? '') === 'gerada') {
            $sql .= ' AND fe.id IS NOT NULL';
        }
        if (($filters['financial_status'] ?? '') === 'nao_gerada') {
            $sql .= ' AND fe.id IS NULL';
        }
        if (($filters['due_state'] ?? '') === 'vencidas') {
            $sql .= ' AND rf.data_vencimento < CURDATE()';
        }
        if (($filters['due_state'] ?? '') === 'a_vencer') {
            $sql .= ' AND rf.data_vencimento >= CURDATE()';
        }
        if (!empty($filters['status'])) {
            if ((string)$filters['status'] === self::STATUS_VENCIDA) {
                $sql .= " AND ((rf.status = 'pendente' AND rf.data_vencimento < CURDATE()) OR rf.status = 'vencida')";
            } else {
                $sql .= ' AND rf.status = :status';
                $params['status'] = (string)$filters['status'];
            }
        }

        $sql .= ' ORDER BY rf.data_hora_multa DESC, rf.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function byRentalIds(array $rentalIds): array
    {
        if (empty($rentalIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rentalIds), '?'));
        $stmt = $this->db->prepare("SELECT rf.*,
                CASE
                    WHEN rf.status = 'pendente' AND rf.data_vencimento < CURDATE() THEN 'vencida'
                    ELSE rf.status
                END AS status_exibicao,
                fe.id AS financial_entry_id
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

    public function summaryByRentalIds(array $rentalIds): array
    {
        if (empty($rentalIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rentalIds), '?'));
        $stmt = $this->db->prepare("SELECT rental_id, COUNT(*) AS qtd, SUM(valor) AS valor_total
            FROM rental_fines
            WHERE rental_id IN ($placeholders) AND status <> 'cancelada'
            GROUP BY rental_id");
        $stmt->execute(array_values($rentalIds));

        $summary = [];
        foreach ($stmt->fetchAll() as $row) {
            $summary[(int)$row['rental_id']] = [
                'qtd' => (int)$row['qtd'],
                'valor_total' => (float)$row['valor_total'],
            ];
        }

        return $summary;
    }

    public function summaryByRentalId(int $rentalId): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(valor), 0) AS valor_total
            FROM rental_fines
            WHERE rental_id = :rental_id AND status <> 'cancelada'");
        $stmt->execute(['rental_id' => $rentalId]);
        $row = $stmt->fetch() ?: [];

        return [
            'qtd' => (int)($row['qtd'] ?? 0),
            'valor_total' => (float)($row['valor_total'] ?? 0),
        ];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO rental_fines (rental_id, auto_infracao, local_infracao, valor, data_hora_multa, data_vencimento, observacoes, status, gerar_despesa_financeiro) VALUES (:rental_id,:auto_infracao,:local_infracao,:valor,:data_hora_multa,:data_vencimento,:observacoes,:status,:gerar_despesa_financeiro)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE rental_fines SET rental_id=:rental_id, auto_infracao=:auto_infracao, local_infracao=:local_infracao, valor=:valor, data_hora_multa=:data_hora_multa, data_vencimento=:data_vencimento, observacoes=:observacoes, status=:status, gerar_despesa_financeiro=:gerar_despesa_financeiro WHERE id=:id');
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM rental_fines WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT rf.*, r.client_id, r.vehicle_id, c.nome_completo AS cliente_nome, v.nome AS veiculo_nome, v.placa,
            CASE
                WHEN rf.status = 'pendente' AND rf.data_vencimento < CURDATE() THEN 'vencida'
                ELSE rf.status
            END AS status_exibicao,
            fe.id AS financial_entry_id
            FROM rental_fines rf
            JOIN rentals r ON r.id = rf.rental_id
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN financial_entries fe ON fe.fine_id = rf.id
            WHERE rf.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateAttachment(int $id, array $attachment): void
    {
        $stmt = $this->db->prepare('UPDATE rental_fines SET comprovante_path=:comprovante_path, comprovante_nome_original=:comprovante_nome_original, comprovante_mime_type=:comprovante_mime_type, comprovante_tamanho_bytes=:comprovante_tamanho_bytes WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            ...$attachment,
        ]);
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
            observacoes TEXT,
            status ENUM('pendente','paga','vencida','cancelada') NOT NULL DEFAULT 'pendente',
            gerar_despesa_financeiro TINYINT(1) NOT NULL DEFAULT 0,
            comprovante_path VARCHAR(255) DEFAULT NULL,
            comprovante_nome_original VARCHAR(255) DEFAULT NULL,
            comprovante_mime_type VARCHAR(120) DEFAULT NULL,
            comprovante_tamanho_bytes INT DEFAULT NULL,
            data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function ensureColumns(): void
    {
        $columns = [
            'observacoes' => 'ALTER TABLE rental_fines ADD COLUMN observacoes TEXT',
            'status' => "ALTER TABLE rental_fines ADD COLUMN status ENUM('pendente','paga','vencida','cancelada') NOT NULL DEFAULT 'pendente'",
            'comprovante_path' => 'ALTER TABLE rental_fines ADD COLUMN comprovante_path VARCHAR(255) DEFAULT NULL',
            'comprovante_nome_original' => 'ALTER TABLE rental_fines ADD COLUMN comprovante_nome_original VARCHAR(255) DEFAULT NULL',
            'comprovante_mime_type' => 'ALTER TABLE rental_fines ADD COLUMN comprovante_mime_type VARCHAR(120) DEFAULT NULL',
            'comprovante_tamanho_bytes' => 'ALTER TABLE rental_fines ADD COLUMN comprovante_tamanho_bytes INT DEFAULT NULL',
        ];

        foreach ($columns as $column => $alter) {
            $stmt = $this->db->prepare('SHOW COLUMNS FROM rental_fines LIKE :column_name');
            $stmt->execute(['column_name' => $column]);
            if (!$stmt->fetch()) {
                $this->db->exec($alter);
            }
        }
    }
}
