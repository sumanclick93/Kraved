<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Helpers;

final class ProductOffer extends Model
{
    protected string $table = 'product_offers';

    public function adminAll(): array
    {
        return $this->db->query(
            'SELECT o.*, p.title AS product_title
             FROM product_offers o
             JOIN products p ON p.id = o.product_id
             ORDER BY o.id DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_offers
             (product_id, title, discount_type, discount_value, starts_at, ends_at, status)
             VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['product_id'],
            $data['title'],
            $data['discount_type'],
            $data['discount_value'],
            $data['starts_at'] ?: null,
            $data['ends_at'] ?: null,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE product_offers SET
             product_id = ?, title = ?, discount_type = ?, discount_value = ?,
             starts_at = ?, ends_at = ?, status = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['product_id'],
            $data['title'],
            $data['discount_type'],
            $data['discount_value'],
            $data['starts_at'] ?: null,
            $data['ends_at'] ?: null,
            $data['status'] ?? 1,
            $id,
        ]);
    }

    public function activeFor(int $productId): ?array
    {
        try {
            $stmt = $this->db->prepare(
            'SELECT * FROM product_offers
             WHERE product_id = ? AND status = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY id DESC'
            );
            $stmt->execute([$productId]);
        } catch (\Throwable) {
            return null;
        }
        $best = null;
        $bestSave = -1.0;
        while ($row = $stmt->fetch()) {
            $save = Helpers::discountedPrice(100, $row['discount_type'], (float) $row['discount_value']);
            $amount = 100 - $save;
            if ($amount > $bestSave) {
                $bestSave = $amount;
                $best = $row;
            }
        }
        return $best ?: null;
    }

    /** @return array<int, array> product_id => offer */
    public function activeMap(): array
    {
        $rows = $this->db->query(
            'SELECT * FROM product_offers
             WHERE status = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY id DESC'
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $pid = (int) $row['product_id'];
            if (!isset($map[$pid])) {
                $map[$pid] = $row;
            }
        }
        return $map;
    }

    public function decorate(array $products): array
    {
        if ($products === []) {
            return $products;
        }
        try {
            $map = $this->activeMap();
        } catch (\Throwable) {
            return $products;
        }
        foreach ($products as &$p) {
            $offer = $map[(int) $p['id']] ?? null;
            $p['offer'] = $offer;
            $variantCount = (int) ($p['variant_count'] ?? 0);
            $display = $variantCount > 0 && $p['min_variant_price'] !== null
                ? (float) $p['min_variant_price']
                : (float) $p['base_price'];
            $p['original_price'] = $display;
            $p['sale_price'] = $offer
                ? Helpers::discountedPrice($display, $offer['discount_type'], (float) $offer['discount_value'])
                : $display;
            $p['on_offer'] = $offer !== null && $p['sale_price'] < $p['original_price'] - 0.001;
            $p['offer_badge'] = $p['on_offer'] ? $this->badge($offer) : '';
        }
        unset($p);
        return $products;
    }

    public function salePrice(float $price, ?array $offer): float
    {
        if (!$offer) {
            return round($price, 2);
        }
        return Helpers::discountedPrice($price, $offer['discount_type'], (float) $offer['discount_value']);
    }

    public function badge(array $offer): string
    {
        if (($offer['discount_type'] ?? '') === 'percent') {
            return rtrim(rtrim(number_format((float) $offer['discount_value'], 1), '0'), '.') . '% off';
        }
        return Helpers::money((float) $offer['discount_value']) . ' off';
    }
}
