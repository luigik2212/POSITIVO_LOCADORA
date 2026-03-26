<?php

declare(strict_types=1);

namespace App\Models;

class Vehicle extends BaseModel
{
    public function all(?string $search = null, ?string $status = null): array
    {
        $sql = 'SELECT * FROM vehicles WHERE 1=1';
        $params = [];
        if ($search) {
            $sql .= ' AND (nome LIKE :search OR placa LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        if ($status) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO vehicles (nome, marca, modelo, ano, placa, renavam, cor, quilometragem_atual, categoria, valor_diaria, valor_semanal, valor_mensal, status, observacoes) VALUES (:nome,:marca,:modelo,:ano,:placa,:renavam,:cor,:quilometragem_atual,:categoria,:valor_diaria,:valor_semanal,:valor_mensal,:status,:observacoes)');
        $stmt->execute($data);
    }

    public function update(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE vehicles SET nome=:nome, marca=:marca, modelo=:modelo, ano=:ano, placa=:placa, renavam=:renavam, cor=:cor, quilometragem_atual=:quilometragem_atual, categoria=:categoria, valor_diaria=:valor_diaria, valor_semanal=:valor_semanal, valor_mensal=:valor_mensal, status=:status, observacoes=:observacoes WHERE id=:id');
        $stmt->execute($data);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE vehicles SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function updateMileage(int $id, int $km): void
    {
        $stmt = $this->db->prepare('UPDATE vehicles SET quilometragem_atual = :km WHERE id = :id');
        $stmt->execute(['id' => $id, 'km' => $km]);
    }

    public function available(): array
    {
        return $this->db->query("SELECT * FROM vehicles WHERE status = 'disponivel' ORDER BY nome")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function counters(): array
    {
        $sql = "SELECT
            COUNT(*) total,
            SUM(CASE WHEN status='disponivel' THEN 1 ELSE 0 END) disponiveis,
            SUM(CASE WHEN status='alugado' THEN 1 ELSE 0 END) alugados,
            SUM(CASE WHEN status='manutencao' THEN 1 ELSE 0 END) manutencao
            FROM vehicles";
        return $this->db->query($sql)->fetch();
    }
}
