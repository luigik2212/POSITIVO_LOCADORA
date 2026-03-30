<?php

declare(strict_types=1);

namespace App\Models;

class Vehicle extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureExtraColumns();
    }

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
        $sql .= ' ORDER BY id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO vehicles (nome, marca, modelo, ano, placa, renavam, cor, quilometragem_atual, proxima_revisao_km, categoria, valor_diaria, valor_semanal, valor_mensal, status, observacoes) VALUES (:nome,:marca,:modelo,:ano,:placa,:renavam,:cor,:quilometragem_atual,:proxima_revisao_km,:categoria,:valor_diaria,:valor_semanal,:valor_mensal,:status,:observacoes)');
        $stmt->execute($data);
    }

    public function update(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE vehicles SET nome=:nome, marca=:marca, modelo=:modelo, ano=:ano, placa=:placa, renavam=:renavam, cor=:cor, quilometragem_atual=:quilometragem_atual, proxima_revisao_km=:proxima_revisao_km, categoria=:categoria, valor_diaria=:valor_diaria, valor_semanal=:valor_semanal, valor_mensal=:valor_mensal, status=:status, observacoes=:observacoes WHERE id=:id');
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vehicles WHERE id = :id');
        $stmt->execute(['id' => $id]);
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

    public function paginate(?string $search, ?string $status, int $page, int $perPage): array
    {
        $where = ' WHERE 1=1';
        $params = [];

        if ($search) {
            $where .= ' AND (nome LIKE :search OR placa LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        if ($status) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM vehicles' . $where);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = 'SELECT * FROM vehicles' . $where . ' ORDER BY id ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    private function ensureExtraColumns(): void
    {
        $stmt = $this->db->prepare('SHOW COLUMNS FROM vehicles LIKE :column_name');
        $stmt->execute(['column_name' => 'proxima_revisao_km']);
        if (!$stmt->fetch()) {
            $this->db->exec('ALTER TABLE vehicles ADD COLUMN proxima_revisao_km INT DEFAULT NULL AFTER quilometragem_atual');
        }
    }
}
