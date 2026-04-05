<?php

declare(strict_types=1);

namespace App\Models;

class Checklist extends BaseModel
{
    public function findByRentalAndType(int $rentalId, string $tipo): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM checklists WHERE rental_id = :rental_id AND tipo_checklist = :tipo_checklist ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            'rental_id' => $rentalId,
            'tipo_checklist' => $tipo,
        ]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO checklists (rental_id, tipo_checklist, lataria, pneus, vidros, combustivel, limpeza, interior_estado, acessorios, avarias, observacoes) VALUES (:rental_id,:tipo_checklist,:lataria,:pneus,:vidros,:combustivel,:limpeza,:interior_estado,:acessorios,:avarias,:observacoes)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE checklists SET lataria=:lataria, pneus=:pneus, vidros=:vidros, combustivel=:combustivel, limpeza=:limpeza, interior_estado=:interior_estado, acessorios=:acessorios, avarias=:avarias, observacoes=:observacoes WHERE id=:id');
        $stmt->execute([
            ...$data,
            'id' => $id,
        ]);
    }

    public function byRentalIds(array $rentalIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $rentalIds), static fn (int $id): bool => $id > 0));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM checklists WHERE rental_id IN ($placeholders) ORDER BY id DESC");
        $stmt->execute($ids);

        $map = [];
        foreach ($stmt->fetchAll() as $checklist) {
            $rentalId = (int)$checklist['rental_id'];
            $tipo = (string)$checklist['tipo_checklist'];
            if (!isset($map[$rentalId][$tipo])) {
                $map[$rentalId][$tipo] = $checklist;
            }
        }

        return $map;
    }

    public function addAttachment(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO checklist_attachments (checklist_id, tipo_arquivo, caminho_arquivo) VALUES (:checklist_id,:tipo_arquivo,:caminho_arquivo)');
        $stmt->execute($data);
    }
}
