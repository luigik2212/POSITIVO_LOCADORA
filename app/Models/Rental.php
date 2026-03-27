<?php

declare(strict_types=1);

namespace App\Models;

class Rental extends BaseModel
{
    public function all(array $filters = []): array
    {
        $sql = "SELECT r.*, c.nome_completo as cliente_nome, c.telefone as cliente_telefone, v.nome as veiculo_nome, v.placa,
                    CASE
                        WHEN r.data_prevista_termino < CURDATE() THEN 'vencido'
                        WHEN r.data_prevista_termino = CURDATE() THEN 'vence_hoje'
                        WHEN DATEDIFF(r.data_prevista_termino, CURDATE()) = 7 THEN 'vence_em_7_dias'
                        ELSE 'ok'
                    END AS alerta_status,
                    COALESCE(wn.delivery_status, 'nao_enviado') AS whatsapp_delivery_status,
                    wn.sent_at AS whatsapp_sent_at,
                    wn.phone AS whatsapp_phone,
                    COALESCE(fin.total_lancamentos, 0) AS financeiro_total_lancamentos,
                    COALESCE(fin.total_pago, 0) AS financeiro_total_pago,
                    COALESCE(fin.total_pendente, 0) AS financeiro_total_pendente,
                    COALESCE(fin.qtd_lancamentos, 0) AS financeiro_qtd_lancamentos
                FROM rentals r
                JOIN clients c ON c.id = r.client_id
                JOIN vehicles v ON v.id = r.vehicle_id
                LEFT JOIN (
                    SELECT n1.*
                    FROM whatsapp_notifications n1
                    INNER JOIN (
                        SELECT rental_id, MAX(id) AS max_id
                        FROM whatsapp_notifications
                        WHERE alert_type = 'due_in_7_days'
                        GROUP BY rental_id
                    ) n2 ON n2.max_id = n1.id
                ) wn ON wn.rental_id = r.id
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
        $stmt = $this->db->prepare("SELECT r.*, c.nome_completo as cliente_nome, c.telefone as cliente_telefone, v.nome as veiculo_nome, v.placa,
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

    public function listDueInDaysWithoutNotification(int $days): array
    {
        $stmt = $this->db->prepare("SELECT r.*, c.nome_completo as cliente_nome, c.telefone as cliente_telefone, v.nome as veiculo_nome, v.placa
            FROM rentals r
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN whatsapp_notifications wn
                ON wn.rental_id = r.id
                AND wn.alert_type = 'due_in_7_days'
                AND wn.delivery_status IN ('queued','sent','delivered','read')
            WHERE r.status = 'ativa'
              AND r.data_prevista_termino = DATE_ADD(CURDATE(), INTERVAL :days DAY)
              AND wn.id IS NULL");
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function dueAlertsForDashboard(int $limit = 10): array
    {
        $stmt = $this->db->prepare("SELECT r.id, c.nome_completo as cliente_nome, v.nome as veiculo_nome, v.placa,
            r.data_prevista_termino,
            CASE
                WHEN r.data_prevista_termino < CURDATE() THEN 'vencido'
                WHEN r.data_prevista_termino = CURDATE() THEN 'vence_hoje'
                WHEN DATEDIFF(r.data_prevista_termino, CURDATE()) = 7 THEN 'vence_em_7_dias'
                ELSE 'ok'
            END AS alerta_status,
            COALESCE(wn.delivery_status, 'nao_enviado') AS whatsapp_delivery_status
            FROM rentals r
            JOIN clients c ON c.id = r.client_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN (
                SELECT n1.*
                FROM whatsapp_notifications n1
                INNER JOIN (
                    SELECT rental_id, MAX(id) AS max_id
                    FROM whatsapp_notifications
                    WHERE alert_type = 'due_in_7_days'
                    GROUP BY rental_id
                ) n2 ON n2.max_id = n1.id
            ) wn ON wn.rental_id = r.id
            WHERE r.status='ativa'
              AND (r.data_prevista_termino <= CURDATE() OR DATEDIFF(r.data_prevista_termino, CURDATE()) = 7)
            ORDER BY r.data_prevista_termino ASC
            LIMIT :lim");
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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
