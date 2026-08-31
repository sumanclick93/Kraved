<?php

declare(strict_types=1);

namespace App\Models;

final class AddonGroup extends Model
{
    protected string $table = 'addon_groups';

    public function allWithAddons(): array
    {
        $groups = $this->db->query(
            'SELECT * FROM addon_groups WHERE status = 1 ORDER BY display_order ASC'
        )->fetchAll();

        $stmt = $this->db->prepare(
            'SELECT * FROM addons WHERE group_id = ? AND status = 1 ORDER BY display_order ASC'
        );

        foreach ($groups as &$g) {
            $stmt->execute([(int) $g['id']]);
            $g['addons'] = $stmt->fetchAll();
        }
        return $groups;
    }

    public function adminAll(): array
    {
        return $this->db->query('SELECT * FROM addon_groups ORDER BY display_order ASC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO addon_groups (title, min_selection, max_selection, is_required, display_order, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['min_selection'] ?? 0,
            $data['max_selection'] ?? 1,
            $data['is_required'] ?? 0,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE addon_groups SET title=?, min_selection=?, max_selection=?, is_required=?, display_order=?, status=? WHERE id=?'
        );
        return $stmt->execute([
            $data['title'],
            $data['min_selection'] ?? 0,
            $data['max_selection'] ?? 1,
            $data['is_required'] ?? 0,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
            $id,
        ]);
    }
}
