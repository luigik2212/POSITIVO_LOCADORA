<?php

declare(strict_types=1);

namespace App\Models;

class FinancialEntry extends BaseModel
{
    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO financial_entries (tipo, categoria, descricao, valor, data_movimentacao, rental_id, maintenance_id, vehicle_id, client_id) VALUES (:tipo,:categoria,:descricao,:valor,:data_movimentacao,:rental_id,:maintenance_id,:vehicle_id,:client_id)');
        $stmt->execute($data);
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
}
