<?php

declare(strict_types=1);

namespace App\Models;

final class Product extends Model
{
    protected string $table = 'products';

    public function active(): array
    {
        $rows = $this->db->query(
            'SELECT p.*, c.name AS category_name, sc.name AS sub_category_name,
                    (SELECT MIN(pv.price) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS min_variant_price,
                    (SELECT COUNT(*) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS variant_count
             FROM products p
             JOIN categories c ON c.id = p.category_id
             LEFT JOIN sub_categories sc ON sc.id = p.sub_category_id
             WHERE p.status = 1
            ORDER BY p.display_order ASC, p.title ASC'
        )->fetchAll();
        return (new ProductOffer())->decorate($rows);
    }

    public function featured(int $limit = 8): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT MIN(pv.price) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS min_variant_price,
                    (SELECT COUNT(*) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS variant_count
             FROM products p
             WHERE p.status = 1 AND p.is_featured = 1
             ORDER BY p.display_order ASC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return (new ProductOffer())->decorate($stmt->fetchAll());
    }

    public function byCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT MIN(pv.price) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS min_variant_price,
                    (SELECT COUNT(*) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS variant_count
             FROM products p
             WHERE p.category_id = ? AND p.status = 1
             ORDER BY p.display_order ASC'
        );
        $stmt->execute([$categoryId]);
        return (new ProductOffer())->decorate($stmt->fetchAll());
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function withAddonGroups(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ag.* FROM addon_groups ag
             JOIN product_addon_groups pag ON pag.group_id = ag.id
             WHERE pag.product_id = ? AND ag.status = 1
             ORDER BY ag.display_order ASC'
        );
        $stmt->execute([$productId]);
        $groups = $stmt->fetchAll();

        $addonStmt = $this->db->prepare(
            'SELECT * FROM addons WHERE group_id = ? AND status = 1 ORDER BY display_order ASC'
        );
        foreach ($groups as &$g) {
            $addonStmt->execute([(int) $g['id']]);
            $g['addons'] = $addonStmt->fetchAll();
        }
        return $groups;
    }

    public function boxChoices(int $boxProductId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.* FROM products p
             JOIN box_deal_products b ON b.choice_product_id = p.id
             WHERE b.box_product_id = ? AND p.status = 1
             ORDER BY p.title ASC'
        );
        $stmt->execute([$boxProductId]);
        return $stmt->fetchAll();
    }

    public function adminAll(): array
    {
        return $this->db->query(
            'SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products
            (category_id, sub_category_id, title, slug, short_description, full_description,
             base_price, stock_qty, weight_label, is_featured, is_box_deal, box_max_items, image, status, display_order)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['sub_category_id'] ?: null,
            $data['title'],
            $data['slug'],
            $data['short_description'] ?? null,
            $data['full_description'] ?? null,
            $data['base_price'],
            $data['stock_qty'] ?? 0,
            $data['weight_label'] ?? null,
            $data['is_featured'] ?? 0,
            $data['is_box_deal'] ?? 0,
            $data['box_max_items'] ?? null,
            $data['image'] ?? null,
            $data['status'] ?? 1,
            $data['display_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET
             category_id=?, sub_category_id=?, title=?, slug=?, short_description=?, full_description=?,
             base_price=?, stock_qty=?, weight_label=?, is_featured=?, is_box_deal=?, box_max_items=?,
             image=?, status=?, display_order=?
             WHERE id=?'
        );
        return $stmt->execute([
            $data['category_id'],
            $data['sub_category_id'] ?: null,
            $data['title'],
            $data['slug'],
            $data['short_description'] ?? null,
            $data['full_description'] ?? null,
            $data['base_price'],
            $data['stock_qty'] ?? 0,
            $data['weight_label'] ?? null,
            $data['is_featured'] ?? 0,
            $data['is_box_deal'] ?? 0,
            $data['box_max_items'] ?? null,
            $data['image'] ?? null,
            $data['status'] ?? 1,
            $data['display_order'] ?? 0,
            $id,
        ]);
    }

    public function syncAddonGroups(int $productId, array $groupIds): void
    {
        $this->db->prepare('DELETE FROM product_addon_groups WHERE product_id = ?')->execute([$productId]);
        $stmt = $this->db->prepare('INSERT INTO product_addon_groups (product_id, group_id) VALUES (?, ?)');
        foreach ($groupIds as $gid) {
            $stmt->execute([$productId, (int) $gid]);
        }
    }

    public function syncBoxChoices(int $boxId, array $choiceIds): void
    {
        $this->db->prepare('DELETE FROM box_deal_products WHERE box_product_id = ?')->execute([$boxId]);
        $stmt = $this->db->prepare(
            'INSERT INTO box_deal_products (box_product_id, choice_product_id) VALUES (?, ?)'
        );
        foreach ($choiceIds as $cid) {
            $stmt->execute([$boxId, (int) $cid]);
        }
    }

    public function addonGroupIds(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT group_id FROM product_addon_groups WHERE product_id = ?');
        $stmt->execute([$productId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function boxChoiceIds(int $boxId): array
    {
        $stmt = $this->db->prepare('SELECT choice_product_id FROM box_deal_products WHERE box_product_id = ?');
        $stmt->execute([$boxId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function images(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC, id ASC'
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function addImage(int $productId, string $path, bool $isPrimary = false, int $order = 0): int
    {
        if ($isPrimary) {
            $this->db->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
        }
        $stmt = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, is_primary, display_order) VALUES (?,?,?,?)'
        );
        $stmt->execute([$productId, $path, $isPrimary ? 1 : 0, $order]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteImage(int $imageId, int $productId): ?string
    {
        $stmt = $this->db->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$imageId, $productId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $this->db->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imageId]);
        return (string) $row['image_path'];
    }

    public function setPrimaryImage(int $productId, int $imageId): ?string
    {
        $stmt = $this->db->prepare('SELECT * FROM product_images WHERE id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$imageId, $productId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $this->db->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$productId]);
        $this->db->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?')->execute([$imageId]);
        return (string) $row['image_path'];
    }

    public function syncCoverFromImages(int $productId): void
    {
        $images = $this->images($productId);
        $cover = null;
        foreach ($images as $img) {
            if ((int) $img['is_primary'] === 1) {
                $cover = $img['image_path'];
                break;
            }
        }
        if ($cover === null && $images) {
            $cover = $images[0]['image_path'];
        }
        $this->db->prepare('UPDATE products SET image = ? WHERE id = ?')->execute([$cover, $productId]);
    }

    public function variants(int $productId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM product_variants WHERE product_id = ?';
        if ($activeOnly) {
            $sql .= ' AND status = 1';
        }
        $sql .= ' ORDER BY display_order ASC, id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function findVariant(int $variantId, int $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND status = 1 LIMIT 1'
        );
        $stmt->execute([$variantId, $productId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param list<array{id?:int|null,label:string,price:float,stock_qty:int,sku?:string,status?:int,display_order?:int}> $rows
     */
    public function syncVariants(int $productId, array $rows): void
    {
        $keepIds = [];
        $insert = $this->db->prepare(
            'INSERT INTO product_variants (product_id, label, price, stock_qty, sku, status, display_order)
             VALUES (?,?,?,?,?,?,?)'
        );
        $update = $this->db->prepare(
            'UPDATE product_variants SET label=?, price=?, stock_qty=?, sku=?, status=?, display_order=?
             WHERE id=? AND product_id=?'
        );

        foreach ($rows as $i => $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $stock = (int) ($row['stock_qty'] ?? 0);
            $sku = trim((string) ($row['sku'] ?? '')) ?: null;
            $status = isset($row['status']) ? (int) $row['status'] : 1;
            $order = (int) ($row['display_order'] ?? $i);

            if ($id > 0) {
                $update->execute([$label, $price, $stock, $sku, $status, $order, $id, $productId]);
                $keepIds[] = $id;
            } else {
                $insert->execute([$productId, $label, $price, $stock, $sku, $status, $order]);
                $keepIds[] = (int) $this->db->lastInsertId();
            }
        }

        if ($keepIds) {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $params = array_merge([$productId], $keepIds);
            $this->db->prepare(
                "DELETE FROM product_variants WHERE product_id = ? AND id NOT IN ({$placeholders})"
            )->execute($params);
        } else {
            $this->db->prepare('DELETE FROM product_variants WHERE product_id = ?')->execute([$productId]);
        }
    }

    /** Lowest active variant price, or null when none. */
    public function minVariantPrice(int $productId): ?float
    {
        $stmt = $this->db->prepare(
            'SELECT MIN(price) FROM product_variants WHERE product_id = ? AND status = 1'
        );
        $stmt->execute([$productId]);
        $val = $stmt->fetchColumn();
        return $val === null || $val === false ? null : (float) $val;
    }
}
