<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Admin
{
    public function findByUsername(string $username): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();

        return $admin ?: null;
    }
}
