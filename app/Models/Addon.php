<?php

declare(strict_types=1);

namespace App\Models;

final class Addon extends Model
{
    protected string $table = 'addons';

    public function byGroup(int $groupId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM addons WHERE group_id = ? ORDER BY display_order ASC'
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO addons (group_id, name, price, display_order, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['group_id'],
            $data['name'],
            $data['price'] ?? 0,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE addons SET group_id=?, name=?, price=?, display_order=?, status=? WHERE id=?'
        );
        return $stmt->execute([
            $data['group_id'],
            $data['name'],
            $data['price'] ?? 0,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
            $id,
        ]);
    }

    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM addons WHERE id IN ($placeholders) AND status = 1");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}
