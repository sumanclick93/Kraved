<?php

declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role, address, postcode)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['password_hash'],
            $data['role'] ?? 'customer',
            $data['address'] ?? null,
            $data['postcode'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name=?, email=?, phone=?, address=?, postcode=? WHERE id=?'
        );
        return $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['postcode'] ?? null,
            $id,
        ]);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash=? WHERE id=?');
        return $stmt->execute([$hash, $id]);
    }
}
