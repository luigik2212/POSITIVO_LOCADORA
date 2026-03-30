<?php

declare(strict_types=1);

namespace App\Models;

class NotificationCenter extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureColumns();
    }

    public function allActive(int $userId, int $windowDays = 15): array
    {
        $states = (new NotificationState())->getByUser($userId);
        $notifications = array_merge(
            $this->contractEndingSoon($windowDays),
            $this->financialDueSoon($windowDays),
            $this->finesDueSoon($windowDays),
            $this->maintenanceByMileage(),
            $this->pendingMileageFill()
        );

        $notifications = array_values(array_filter($notifications, static function (array $notification) use ($states): bool {
            $key = (string)($notification['key'] ?? '');
            if ($key === '') {
                return true;
            }

            if (!empty($states[$key]['resolved_at']) && !empty($notification['dismiss_on_resolve'])) {
                return false;
            }

            return true;
        }));

        foreach ($notifications as &$notification) {
            $key = (string)($notification['key'] ?? '');
            $notification['read'] = $key !== '' && !empty($states[$key]['viewed_at']);
        }
        unset($notification);

        usort($notifications, static function (array $a, array $b): int {
            $priorityOrder = ['danger' => 0, 'warning' => 1, 'info' => 2, 'secondary' => 3];
            $aPriority = $priorityOrder[$a['severity'] ?? 'secondary'] ?? 9;
            $bPriority = $priorityOrder[$b['severity'] ?? 'secondary'] ?? 9;

            $cmpPriority = $aPriority <=> $bPriority;
            if ($cmpPriority !== 0) {
                return $cmpPriority;
            }

            $cmp = ($a['days_left'] ?? 9999) <=> ($b['days_left'] ?? 9999);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string)($a['due_date'] ?? ''), (string)($b['due_date'] ?? ''));
        });

        return $notifications;
    }

    public function unreadCount(int $userId, int $windowDays = 15): int
    {
        $all = $this->allActive($userId, $windowDays);
        $unread = array_filter($all, static fn(array $item): bool => empty($item['read']));
        return count($unread);
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
                'key' => 'contract-ending-' . (int)$row['id'],
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
                'key' => 'financial-due-' . (int)$row['id'],
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
                'key' => 'fine-due-' . (int)$row['id'],
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

    private function maintenanceByMileage(): array
    {
        $stmt = $this->db->query("SELECT id, nome, placa, quilometragem_atual, proxima_revisao_km
            FROM vehicles
            WHERE proxima_revisao_km IS NOT NULL
              AND quilometragem_atual >= proxima_revisao_km");
        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            return [
                'key' => 'maintenance-km-' . (int)$row['id'] . '-' . (int)$row['proxima_revisao_km'],
                'type' => 'revisao_km',
                'severity' => 'danger',
                'title' => 'Revisão por KM pendente',
                'description' => sprintf(
                    '%s (%s) • KM atual: %d • revisão prevista: %d',
                    $row['nome'],
                    $row['placa'],
                    (int)$row['quilometragem_atual'],
                    (int)$row['proxima_revisao_km']
                ),
                'due_date' => date('Y-m-d'),
                'days_left' => 0,
                'link' => url('/maintenances?vehicle_id=' . (int)$row['id']),
            ];
        }, $rows);
    }

    private function pendingMileageFill(): array
    {
        $stmt = $this->db->query("SELECT fe.id, fe.data_movimentacao, v.nome, v.placa
            FROM financial_entries fe
            JOIN vehicles v ON v.id = fe.vehicle_id
            WHERE fe.km_pendente_preenchimento = 1
              AND fe.pagamento_status = 'pago'
            ORDER BY fe.data_movimentacao DESC, fe.id DESC");

        return array_map(static function (array $row): array {
            return [
                'key' => 'pending-km-entry-' . (int)$row['id'],
                'type' => 'preencher_km',
                'severity' => 'warning',
                'title' => 'Preencher KM do veículo',
                'description' => sprintf(
                    'Cobrança #%d • %s (%s) • pagamento baixado sem KM',
                    (int)$row['id'],
                    $row['nome'],
                    $row['placa']
                ),
                'due_date' => $row['data_movimentacao'],
                'days_left' => 0,
                'link' => url('/financial?tab=receivable&pending_km_entry=' . (int)$row['id']),
                'dismiss_on_resolve' => true,
            ];
        }, $stmt->fetchAll());
    }

    private function ensureColumns(): void
    {
        $vehicleColumn = $this->db->prepare('SHOW COLUMNS FROM vehicles LIKE :column_name');
        $vehicleColumn->execute(['column_name' => 'proxima_revisao_km']);
        if (!$vehicleColumn->fetch()) {
            $this->db->exec('ALTER TABLE vehicles ADD COLUMN proxima_revisao_km INT DEFAULT NULL AFTER quilometragem_atual');
        }

        $financialColumn = $this->db->prepare('SHOW COLUMNS FROM financial_entries LIKE :column_name');
        $financialColumn->execute(['column_name' => 'km_pendente_preenchimento']);
        if (!$financialColumn->fetch()) {
            $this->db->exec('ALTER TABLE financial_entries ADD COLUMN km_pendente_preenchimento TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
}
