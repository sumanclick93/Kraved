<?php

declare(strict_types=1);

namespace App\Models;

final class SubCategory extends Model
{
    protected string $table = 'sub_categories';

    public function byCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM sub_categories WHERE category_id = ? ORDER BY display_order ASC, name ASC'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sub_categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sub_categories (category_id, name, slug, display_order, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE sub_categories SET category_id=?, name=?, slug=?, display_order=?, status=? WHERE id=?'
        );
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
            $id,
        ]);
    }
}
