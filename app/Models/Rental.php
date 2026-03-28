<?php

declare(strict_types=1);

namespace App\Models;

class Rental extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome, v.placa,
                    COALESCE(fin.total_lancamentos, 0) AS financeiro_total_lancamentos,
                    COALESCE(fin.total_pago, 0) AS financeiro_total_pago,
                    COALESCE(fin.total_pendente, 0) AS financeiro_total_pendente,
                    COALESCE(fin.qtd_lancamentos, 0) AS financeiro_qtd_lancamentos
                FROM rentals r
                JOIN clients c ON c.id = r.client_id
                JOIN vehicles v ON v.id = r.vehicle_id
                LEFT JOIN (
                    SELECT rental_id,
                           SUM(valor) AS total_lancamentos,
                           SUM(CASE WHEN pagamento_status='pago' THEN valor ELSE 0 END) AS total_pago,
                           SUM(CASE WHEN pagamento_status='nao_pago' THEN valor ELSE 0 END) AS total_pendente,
                           COUNT(*) AS qtd_lancamentos
                    FROM financial_entries
                    WHERE rental_id IS NOT NULL
                    GROUP BY rental_id
                ) fin ON fin.rental_id = r.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= ' AND r.client_id = :client_id';
            $params['client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND r.vehicle_id = :vehicle_id';
            $params['vehicle_id'] = (int)$filters['vehicle_id'];
        }
        if (!empty($filters['billing_type'])) {
            $sql .= ' AND r.tipo_cobranca = :billing_type';
            $params['billing_type'] = $filters['billing_type'];
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND r.data_inicio >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND r.data_inicio <= :to';
            $params['to'] = $filters['to'];
        }

        $sql .= ' ORDER BY r.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO rentals (client_id, vehicle_id, tipo_cobranca, valor_cobranca, tempo_contrato, dia_semana_vencimento, data_inicio, data_prevista_termino, quilometragem_saida, caucao, valor_total_previsto, status, observacoes) VALUES (:client_id,:vehicle_id,:tipo_cobranca,:valor_cobranca,:tempo_contrato,:dia_semana_vencimento,:data_inicio,:data_prevista_termino,:quilometragem_saida,:caucao,:valor_total_previsto,:status,:observacoes)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function finalize(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE rentals SET data_real_termino=:data_real_termino, quilometragem_retorno=:quilometragem_retorno, valor_total_final=:valor_total_final, status="finalizada", observacoes=CONCAT(IFNULL(observacoes,""), :obs) WHERE id=:id');
        $stmt->execute($data);
    }

    public function cancel(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE rentals SET status = "cancelada" WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome, v.placa,
            COALESCE(fin.total_lancamentos, 0) AS financeiro_total_lancamentos,
            COALESCE(fin.total_pago, 0) AS financeiro_total_pago,
            COALESCE(fin.total_pendente, 0) AS financeiro_total_pendente,
            COALESCE(fin.qtd_lancamentos, 0) AS financeiro_qtd_lancamentos
            FROM rentals r
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN (
                SELECT rental_id,
                       SUM(valor) AS total_lancamentos,
                       SUM(CASE WHEN pagamento_status='pago' THEN valor ELSE 0 END) AS total_pago,
                       SUM(CASE WHEN pagamento_status='nao_pago' THEN valor ELSE 0 END) AS total_pendente,
                       COUNT(*) AS qtd_lancamentos
                FROM financial_entries
                WHERE rental_id IS NOT NULL
                GROUP BY rental_id
            ) fin ON fin.rental_id = r.id
            WHERE r.id=:id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function hasActiveByVehicle(int $vehicleId, ?int $excludeRentalId = null): bool
    {
        $sql = "SELECT id FROM rentals WHERE vehicle_id = :vehicle_id AND status = 'ativa'";
        $params = ['vehicle_id' => $vehicleId];
        if ($excludeRentalId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeRentalId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function countByVehicle(int $vehicleId, ?string $from = null, ?string $to = null): array
    {
        $sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status='ativa' THEN 1 ELSE 0 END) AS ativas,
            SUM(CASE WHEN status='finalizada' THEN 1 ELSE 0 END) AS finalizadas,
            SUM(CASE WHEN status='cancelada' THEN 1 ELSE 0 END) AS canceladas
            FROM rentals
            WHERE vehicle_id = :vehicle_id";
        $params = ['vehicle_id' => $vehicleId];

        if ($from) {
            $sql .= ' AND data_inicio >= :from';
            $params['from'] = $from;
        }
        if ($to) {
            $sql .= ' AND data_inicio <= :to';
            $params['to'] = $to;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: [
            'total' => 0,
            'ativas' => 0,
            'finalizadas' => 0,
            'canceladas' => 0,
        ];
    }
    public function activeCount(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM rentals WHERE status='ativa'")->fetchColumn();
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome
                                     FROM rentals r
                                     JOIN clients c ON c.id = r.client_id
                                     JOIN vehicles v ON v.id = r.vehicle_id
                                     ORDER BY r.id DESC LIMIT :lim');
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function upcoming(int $limit = 5): array
    {
        $stmt = $this->db->prepare("SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome
            FROM rentals r
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            WHERE r.status='ativa' AND r.data_prevista_termino >= CURDATE()
            ORDER BY r.data_prevista_termino ASC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
