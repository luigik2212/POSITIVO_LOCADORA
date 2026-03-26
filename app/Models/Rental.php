<?php

declare(strict_types=1);

namespace App\Models;

class Rental extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = 'SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome, v.placa
                FROM rentals r
                JOIN clients c ON c.id = r.client_id
                JOIN vehicles v ON v.id = r.vehicle_id
                WHERE 1=1';
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
        $stmt = $this->db->prepare('SELECT * FROM rentals WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
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
