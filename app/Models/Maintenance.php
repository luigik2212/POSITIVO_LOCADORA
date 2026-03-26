<?php

declare(strict_types=1);

namespace App\Models;

class Maintenance extends BaseModel
{
    public function all(?int $vehicleId = null): array
    {
        $sql = 'SELECT m.*, v.nome as veiculo_nome, v.placa FROM maintenances m JOIN vehicles v ON v.id = m.vehicle_id';
        $params = [];
        if ($vehicleId) {
            $sql .= ' WHERE m.vehicle_id = :vehicle_id';
            $params['vehicle_id'] = $vehicleId;
        }
        $sql .= ' ORDER BY m.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO maintenances (vehicle_id, tipo_manutencao, descricao, data_manutencao, quilometragem_manutencao, valor_gasto, oficina_fornecedor, observacoes, status) VALUES (:vehicle_id,:tipo_manutencao,:descricao,:data_manutencao,:quilometragem_manutencao,:valor_gasto,:oficina_fornecedor,:observacoes,:status)');
        $stmt->execute($data);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE maintenances SET status=:status WHERE id=:id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM maintenances WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function totalByVehicle(): array
    {
        return $this->db->query('SELECT v.nome, v.placa, SUM(m.valor_gasto) as total_gasto
                                 FROM maintenances m JOIN vehicles v ON v.id = m.vehicle_id
                                 GROUP BY m.vehicle_id ORDER BY total_gasto DESC')->fetchAll();
    }

    public function pending(int $limit = 5): array
    {
        $stmt = $this->db->prepare("SELECT m.*, v.nome as veiculo_nome, v.placa
            FROM maintenances m
            JOIN vehicles v ON v.id = m.vehicle_id
            WHERE m.status='pendente'
            ORDER BY m.data_manutencao DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
