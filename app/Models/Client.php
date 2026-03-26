<?php

declare(strict_types=1);

namespace App\Models;

class Client extends BaseModel
{
    public function all(?string $search = null): array
    {
        $sql = 'SELECT * FROM clients WHERE 1=1';
        $params = [];
        if ($search) {
            $sql .= ' AND (nome_completo LIKE :search OR cpf LIKE :search OR telefone LIKE :search)';
            $params['search'] = "%{$search}%";
        }
        $sql .= ' ORDER BY id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO clients (nome_completo, cpf, rg, cnh, validade_cnh, telefone, email, endereco_completo, observacoes) VALUES (:nome_completo,:cpf,:rg,:cnh,:validade_cnh,:telefone,:email,:endereco_completo,:observacoes)');
        $stmt->execute($data);
    }

    public function update(array $data): void
    {
        $stmt = $this->db->prepare('UPDATE clients SET nome_completo=:nome_completo, cpf=:cpf, rg=:rg, cnh=:cnh, validade_cnh=:validade_cnh, telefone=:telefone, email=:email, endereco_completo=:endereco_completo, observacoes=:observacoes WHERE id=:id');
        $stmt->execute($data);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM clients')->fetchColumn();
    }
}
