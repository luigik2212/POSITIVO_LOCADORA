<?php

declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    public function findByLoginOrEmail(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE (login = :login OR email = :login) AND status = "ativo" LIMIT 1');
        $stmt->execute(['login' => $login]);
        return $stmt->fetch() ?: null;
    }
}
