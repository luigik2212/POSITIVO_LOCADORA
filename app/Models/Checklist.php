<?php

declare(strict_types=1);

namespace App\Models;

class Checklist extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO checklists (rental_id, tipo_checklist, lataria, pneus, vidros, combustivel, limpeza, interior_estado, acessorios, avarias, observacoes) VALUES (:rental_id,:tipo_checklist,:lataria,:pneus,:vidros,:combustivel,:limpeza,:interior_estado,:acessorios,:avarias,:observacoes)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function addAttachment(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO checklist_attachments (checklist_id, tipo_arquivo, caminho_arquivo) VALUES (:checklist_id,:tipo_arquivo,:caminho_arquivo)');
        $stmt->execute($data);
    }
}
