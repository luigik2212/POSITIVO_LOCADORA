<?php

declare(strict_types=1);

namespace App\Models;

class MileageHistory extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function create(int $vehicleId, int $kmAnterior, int $kmNovo, string $origem): void
    {
        $stmt = $this->db->prepare('INSERT INTO vehicle_mileage_history (vehicle_id, km_anterior, km_novo, origem_atualizacao) VALUES (:vehicle_id, :km_anterior, :km_novo, :origem_atualizacao)');
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'km_anterior' => $kmAnterior,
            'km_novo' => $kmNovo,
            'origem_atualizacao' => $origem,
        ]);
    }

    public function all(): array
    {
        $sql = 'SELECT h.*, v.nome AS veiculo_nome, v.placa
                FROM vehicle_mileage_history h
                JOIN vehicles v ON v.id = h.vehicle_id
                ORDER BY h.data_atualizacao DESC, h.id DESC';

        return $this->db->query($sql)->fetchAll();
    }

    private function ensureTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS vehicle_mileage_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            km_anterior INT NOT NULL,
            km_novo INT NOT NULL,
            origem_atualizacao ENUM('manutencao','devolucao','edicao_manual') NOT NULL,
            data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
