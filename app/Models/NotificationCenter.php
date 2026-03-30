<?php

declare(strict_types=1);

namespace App\Models;

class NotificationCenter extends BaseModel
{
    public function allActive(int $windowDays = 15): array
    {
        $notifications = array_merge(
            $this->contractEndingSoon($windowDays),
            $this->financialDueSoon($windowDays),
            $this->finesDueSoon($windowDays)
        );

        usort($notifications, static function (array $a, array $b): int {
            $cmp = ($a['days_left'] ?? 9999) <=> ($b['days_left'] ?? 9999);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string)$a['due_date'], (string)$b['due_date']);
        });

        return $notifications;
    }

    private function contractEndingSoon(int $windowDays): array
    {
        $stmt = $this->db->prepare("SELECT r.id, r.data_prevista_termino, c.nome_completo AS cliente_nome, v.nome AS veiculo_nome, v.placa,
                DATEDIFF(r.data_prevista_termino, CURDATE()) AS days_left
            FROM rentals r
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            WHERE r.status = 'ativa'
              AND r.data_prevista_termino BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :window DAY)
            ORDER BY r.data_prevista_termino ASC");
        $stmt->bindValue(':window', $windowDays, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'type' => 'contrato',
                'severity' => ((int)$row['days_left'] <= 3) ? 'danger' : 'warning',
                'title' => 'Contrato perto do fim',
                'description' => sprintf(
                    '%s • %s (%s) • término %s',
                    $row['cliente_nome'],
                    $row['veiculo_nome'],
                    $row['placa'],
                    date('d/m/Y', strtotime((string)$row['data_prevista_termino']))
                ),
                'due_date' => $row['data_prevista_termino'],
                'days_left' => (int)$row['days_left'],
                'link' => url('/rentals?status=ativa'),
            ];
        }, $stmt->fetchAll());
    }

    private function financialDueSoon(int $windowDays): array
    {
        $stmt = $this->db->prepare("SELECT fe.id, fe.tipo, fe.descricao, fe.valor, fe.data_movimentacao,
                c.nome_completo AS cliente_nome, v.nome AS veiculo_nome,
                DATEDIFF(fe.data_movimentacao, CURDATE()) AS days_left
            FROM financial_entries fe
            LEFT JOIN clients c ON c.id = fe.client_id
            LEFT JOIN vehicles v ON v.id = fe.vehicle_id
            WHERE fe.pagamento_status = 'nao_pago'
              AND fe.data_movimentacao <= DATE_ADD(CURDATE(), INTERVAL :window DAY)
            ORDER BY fe.data_movimentacao ASC");
        $stmt->bindValue(':window', $windowDays, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $tab = ($row['tipo'] ?? 'despesa') === 'receita' ? 'receivable' : 'payable';
            $tipoLabel = ($row['tipo'] ?? 'despesa') === 'receita' ? 'Receber' : 'Pagar';
            $severity = ((int)$row['days_left'] < 0 || (int)$row['days_left'] <= 2) ? 'danger' : 'info';

            $context = trim(((string)($row['cliente_nome'] ?? '')) . ' ' . ((string)($row['veiculo_nome'] ?? '')));
            $contextText = $context !== '' ? (' • ' . $context) : '';

            return [
                'type' => 'financeiro',
                'severity' => $severity,
                'title' => 'Conta a vencer',
                'description' => sprintf(
                    '%s • %s • R$ %s • venc. %s%s',
                    $tipoLabel,
                    $row['descricao'],
                    number_format((float)$row['valor'], 2, ',', '.'),
                    date('d/m/Y', strtotime((string)$row['data_movimentacao'])),
                    $contextText
                ),
                'due_date' => $row['data_movimentacao'],
                'days_left' => (int)$row['days_left'],
                'link' => url('/financial?tab=' . $tab),
            ];
        }, $stmt->fetchAll());
    }

    private function finesDueSoon(int $windowDays): array
    {
        $stmt = $this->db->prepare("SELECT rf.id, rf.valor, rf.data_vencimento, rf.rental_id,
                c.nome_completo AS cliente_nome, v.nome AS veiculo_nome, v.placa,
                DATEDIFF(rf.data_vencimento, CURDATE()) AS days_left
            FROM rental_fines rf
            JOIN rentals r ON r.id = rf.rental_id
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            WHERE rf.status IN ('pendente', 'vencida')
              AND rf.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL :window DAY)
            ORDER BY rf.data_vencimento ASC");
        $stmt->bindValue(':window', $windowDays, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            return [
                'type' => 'multa',
                'severity' => ((int)$row['days_left'] <= 2) ? 'danger' : 'warning',
                'title' => 'Multa a vencer',
                'description' => sprintf(
                    'Locação #%d • %s • %s (%s) • R$ %s • venc. %s',
                    (int)$row['rental_id'],
                    $row['cliente_nome'],
                    $row['veiculo_nome'],
                    $row['placa'],
                    number_format((float)$row['valor'], 2, ',', '.'),
                    date('d/m/Y', strtotime((string)$row['data_vencimento']))
                ),
                'due_date' => $row['data_vencimento'],
                'days_left' => (int)$row['days_left'],
                'link' => url('/fines?rental_id=' . (int)$row['rental_id']),
            ];
        }, $stmt->fetchAll());
    }
}
