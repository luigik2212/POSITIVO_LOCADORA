<?php

declare(strict_types=1);

namespace App\Models;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class FinancialEntry extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureExtraColumns();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO financial_entries (tipo, categoria, descricao, valor, data_movimentacao, rental_id, maintenance_id, vehicle_id, client_id, pagamento_status, recorrente, recorrencia_periodo, recorrencia_ativa, referencia_data, origem_automatica) VALUES (:tipo,:categoria,:descricao,:valor,:data_movimentacao,:rental_id,:maintenance_id,:vehicle_id,:client_id,:pagamento_status,:recorrente,:recorrencia_periodo,:recorrencia_ativa,:referencia_data,:origem_automatica)');
        $stmt->execute([
            ...$data,
            'pagamento_status' => $data['pagamento_status'] ?? 'nao_pago',
            'recorrente' => !empty($data['recorrente']) ? 1 : 0,
            'recorrencia_periodo' => $data['recorrencia_periodo'] ?? null,
            'recorrencia_ativa' => !empty($data['recorrente']) ? 1 : 0,
            'referencia_data' => $data['referencia_data'] ?? $data['data_movimentacao'],
            'origem_automatica' => !empty($data['origem_automatica']) ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE financial_entries SET tipo=:tipo, categoria=:categoria, descricao=:descricao, valor=:valor, data_movimentacao=:data_movimentacao, vehicle_id=:vehicle_id, client_id=:client_id, pagamento_status=:pagamento_status, recorrente=:recorrente, recorrencia_periodo=:recorrencia_periodo, recorrencia_ativa=:recorrencia_ativa, referencia_data=:referencia_data WHERE id=:id');
        $stmt->execute([
            'id' => (int)$data['id'],
            'tipo' => $data['tipo'],
            'categoria' => $data['categoria'],
            'descricao' => $data['descricao'],
            'valor' => (float)$data['valor'],
            'data_movimentacao' => $data['data_movimentacao'],
            'vehicle_id' => $data['vehicle_id'],
            'client_id' => $data['client_id'],
            'pagamento_status' => $data['pagamento_status'] ?? 'nao_pago',
            'recorrente' => !empty($data['recorrente']) ? 1 : 0,
            'recorrencia_periodo' => $data['recorrencia_periodo'] ?? null,
            'recorrencia_ativa' => !empty($data['recorrente']) ? 1 : 0,
            'referencia_data' => $data['referencia_data'] ?? $data['data_movimentacao'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM financial_entries WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePaymentStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE financial_entries SET pagamento_status = :status WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function all(?string $from = null, ?string $to = null): array
    {
        $sql = 'SELECT fe.*, v.nome as veiculo_nome, c.nome_completo as cliente_nome FROM financial_entries fe
                LEFT JOIN vehicles v ON v.id = fe.vehicle_id
                LEFT JOIN clients c ON c.id = fe.client_id
                WHERE 1=1';
        $params = [];
        if ($from) {
            $sql .= ' AND fe.data_movimentacao >= :from';
            $params['from'] = $from;
        }
        if ($to) {
            $sql .= ' AND fe.data_movimentacao <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY fe.data_movimentacao DESC, fe.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function generateRecurringEntries(): void
    {
        $today = new DateTimeImmutable('today');
        $templates = $this->db->query("SELECT * FROM financial_entries WHERE recorrente = 1 AND recorrencia_ativa = 1 AND parent_entry_id IS NULL")->fetchAll();

        foreach ($templates as $template) {
            $start = new DateTimeImmutable($template['referencia_data'] ?: $template['data_movimentacao']);
            $interval = ($template['recorrencia_periodo'] ?? 'mensal') === 'semanal' ? new DateInterval('P1W') : new DateInterval('P1M');
            $period = new DatePeriod($start->add($interval), $interval, $today->add($interval));

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                if ($dateString > $today->format('Y-m-d')) {
                    continue;
                }
                if ($this->existsByParentAndDate((int)$template['id'], $dateString)) {
                    continue;
                }

                $stmt = $this->db->prepare('INSERT INTO financial_entries (tipo, categoria, descricao, valor, data_movimentacao, rental_id, maintenance_id, vehicle_id, client_id, pagamento_status, recorrente, recorrencia_periodo, recorrencia_ativa, referencia_data, origem_automatica, parent_entry_id) VALUES (:tipo,:categoria,:descricao,:valor,:data_movimentacao,:rental_id,:maintenance_id,:vehicle_id,:client_id,:pagamento_status,0,NULL,0,:referencia_data,1,:parent_entry_id)');
                $stmt->execute([
                    'tipo' => $template['tipo'],
                    'categoria' => $template['categoria'],
                    'descricao' => $template['descricao'] . ' (recorrente)',
                    'valor' => $template['valor'],
                    'data_movimentacao' => $dateString,
                    'rental_id' => $template['rental_id'],
                    'maintenance_id' => $template['maintenance_id'],
                    'vehicle_id' => $template['vehicle_id'],
                    'client_id' => $template['client_id'],
                    'pagamento_status' => 'nao_pago',
                    'referencia_data' => $dateString,
                    'parent_entry_id' => $template['id'],
                ]);
            }
        }
    }

    public function generateWeeklyRentalCharges(): void
    {
        $today = new DateTimeImmutable('today');
        $sql = "SELECT r.*, c.nome_completo as cliente_nome, v.nome as veiculo_nome
                FROM rentals r
                JOIN clients c ON c.id = r.client_id
                JOIN vehicles v ON v.id = r.vehicle_id
                WHERE r.status = 'ativa' AND r.tipo_cobranca = 'semanal'";
        $rentals = $this->db->query($sql)->fetchAll();

        foreach ($rentals as $rental) {
            $start = new DateTimeImmutable($rental['data_inicio']);
            $period = new DatePeriod($start, new DateInterval('P1W'), $today->add(new DateInterval('P1W')));
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                if ($dateString > $today->format('Y-m-d')) {
                    continue;
                }

                $stmt = $this->db->prepare("SELECT id FROM financial_entries WHERE rental_id = :rental_id AND categoria = 'locacao_semanal' AND data_movimentacao = :data_movimentacao LIMIT 1");
                $stmt->execute([
                    'rental_id' => $rental['id'],
                    'data_movimentacao' => $dateString,
                ]);
                if ($stmt->fetch()) {
                    continue;
                }

                $insert = $this->db->prepare('INSERT INTO financial_entries (tipo, categoria, descricao, valor, data_movimentacao, rental_id, maintenance_id, vehicle_id, client_id, pagamento_status, recorrente, recorrencia_periodo, recorrencia_ativa, referencia_data, origem_automatica) VALUES (\'receita\',\'locacao_semanal\',:descricao,:valor,:data_movimentacao,:rental_id,NULL,:vehicle_id,:client_id,\'nao_pago\',0,NULL,0,:referencia_data,1)');
                $insert->execute([
                    'descricao' => 'Cobrança semanal locação #' . $rental['id'] . ' - ' . $rental['cliente_nome'],
                    'valor' => $rental['valor_cobranca'],
                    'data_movimentacao' => $dateString,
                    'rental_id' => $rental['id'],
                    'vehicle_id' => $rental['vehicle_id'],
                    'client_id' => $rental['client_id'],
                    'referencia_data' => $dateString,
                ]);
            }
        }
    }

    public function monthSummary(): array
    {
        $sql = "SELECT
            SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) receitas,
            SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) despesas
            FROM financial_entries
            WHERE DATE_FORMAT(data_movimentacao, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";

        $row = $this->db->query($sql)->fetch();
        $receitas = (float)($row['receitas'] ?? 0);
        $despesas = (float)($row['despesas'] ?? 0);

        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'lucro' => $receitas - $despesas,
        ];
    }

    private function existsByParentAndDate(int $parentId, string $date): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM financial_entries WHERE parent_entry_id = :parent_entry_id AND data_movimentacao = :data_movimentacao LIMIT 1');
        $stmt->execute([
            'parent_entry_id' => $parentId,
            'data_movimentacao' => $date,
        ]);
        return (bool)$stmt->fetch();
    }

    private function ensureExtraColumns(): void
    {
        $columns = [
            'pagamento_status' => "ALTER TABLE financial_entries ADD COLUMN pagamento_status ENUM('pago','nao_pago') NOT NULL DEFAULT 'nao_pago'",
            'recorrente' => "ALTER TABLE financial_entries ADD COLUMN recorrente TINYINT(1) NOT NULL DEFAULT 0",
            'recorrencia_periodo' => "ALTER TABLE financial_entries ADD COLUMN recorrencia_periodo ENUM('semanal','mensal') DEFAULT NULL",
            'recorrencia_ativa' => "ALTER TABLE financial_entries ADD COLUMN recorrencia_ativa TINYINT(1) NOT NULL DEFAULT 0",
            'referencia_data' => "ALTER TABLE financial_entries ADD COLUMN referencia_data DATE DEFAULT NULL",
            'origem_automatica' => "ALTER TABLE financial_entries ADD COLUMN origem_automatica TINYINT(1) NOT NULL DEFAULT 0",
            'parent_entry_id' => "ALTER TABLE financial_entries ADD COLUMN parent_entry_id INT DEFAULT NULL",
        ];

        foreach ($columns as $column => $alter) {
            $stmt = $this->db->prepare('SHOW COLUMNS FROM financial_entries LIKE :column_name');
            $stmt->execute(['column_name' => $column]);
            if (!$stmt->fetch()) {
                $this->db->exec($alter);
            }
        }
    }
}
