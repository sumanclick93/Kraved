<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Helpers;
use App\Core\Session;

final class PromoCode extends Model
{
    protected string $table = 'promo_codes';
    public const SESSION_KEY = 'promo';

    public function adminAll(): array
    {
        return $this->db->query(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM promo_code_products x WHERE x.promo_id = p.id) AS product_limit
             FROM promo_codes p
             ORDER BY p.id DESC'
        )->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM promo_codes WHERE UPPER(code) = UPPER(?) LIMIT 1');
        $stmt->execute([trim($code)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO promo_codes
             (code, title, discount_type, discount_value, min_order, max_uses, starts_at, ends_at, status)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['title'],
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order'] ?? 0,
            $data['max_uses'] !== null && $data['max_uses'] !== '' ? (int) $data['max_uses'] : null,
            $data['starts_at'] ?: null,
            $data['ends_at'] ?: null,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE promo_codes SET
             code = ?, title = ?, discount_type = ?, discount_value = ?, min_order = ?,
             max_uses = ?, starts_at = ?, ends_at = ?, status = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            strtoupper(trim($data['code'])),
            $data['title'],
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order'] ?? 0,
            $data['max_uses'] !== null && $data['max_uses'] !== '' ? (int) $data['max_uses'] : null,
            $data['starts_at'] ?: null,
            $data['ends_at'] ?: null,
            $data['status'] ?? 1,
            $id,
        ]);
    }

    public function productIds(int $promoId): array
    {
        $stmt = $this->db->prepare('SELECT product_id FROM promo_code_products WHERE promo_id = ?');
        $stmt->execute([$promoId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function syncProducts(int $promoId, array $productIds): void
    {
        $this->db->prepare('DELETE FROM promo_code_products WHERE promo_id = ?')->execute([$promoId]);
        $ins = $this->db->prepare('INSERT INTO promo_code_products (promo_id, product_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $productIds)) as $pid) {
            if ($pid > 0) {
                $ins->execute([$promoId, $pid]);
            }
        }
    }

    public function incrementUse(int $id): void
    {
        $this->db->prepare('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?')->execute([$id]);
    }

    public static function applied(): ?array
    {
        $promo = Session::get(self::SESSION_KEY);
        return is_array($promo) ? $promo : null;
    }

    public static function clearApplied(): void
    {
        Session::remove(self::SESSION_KEY);
    }

    /**
     * @return array{ok:bool, error?:string, promo?:array, discount?:float, eligible_subtotal?:float}
     */
    public function evaluate(string $code, float $subtotal, array $items): array
    {
        $promo = $this->findByCode($code);
        if (!$promo || !(int) $promo['status']) {
            return ['ok' => false, 'error' => 'That promo code is not valid.'];
        }
        if (!empty($promo['starts_at']) && strtotime((string) $promo['starts_at']) > time()) {
            return ['ok' => false, 'error' => 'That promo code is not active yet.'];
        }
        if (!empty($promo['ends_at']) && strtotime((string) $promo['ends_at']) < time()) {
            return ['ok' => false, 'error' => 'That promo code has expired.'];
        }
        $max = $promo['max_uses'];
        if ($max !== null && (int) $promo['used_count'] >= (int) $max) {
            return ['ok' => false, 'error' => 'That promo code has been fully used.'];
        }

        $limited = $this->productIds((int) $promo['id']);
        $eligible = 0.0;
        if ($limited === []) {
            $eligible = $subtotal;
        } else {
            foreach ($items as $item) {
                if (in_array((int) ($item['product_id'] ?? 0), $limited, true)) {
                    $eligible += (float) ($item['line_total'] ?? 0);
                }
            }
            if ($eligible <= 0) {
                return ['ok' => false, 'error' => 'This code does not apply to items in your basket.'];
            }
        }

        if ($subtotal < (float) $promo['min_order']) {
            return [
                'ok' => false,
                'error' => 'Spend ' . Helpers::money((float) $promo['min_order']) . ' to use this code.',
            ];
        }

        $discount = Helpers::discountedPrice($eligible, $promo['discount_type'], (float) $promo['discount_value']);
        $amount = round($eligible - $discount, 2);
        $amount = min($amount, $subtotal);

        return [
            'ok' => true,
            'promo' => $promo,
            'discount' => max(0, $amount),
            'eligible_subtotal' => $eligible,
        ];
    }

    public function applyToSession(string $code, float $subtotal, array $items): array
    {
        $result = $this->evaluate($code, $subtotal, $items);
        if (!$result['ok']) {
            self::clearApplied();
            return $result;
        }
        Session::set(self::SESSION_KEY, [
            'id'             => (int) $result['promo']['id'],
            'code'           => $result['promo']['code'],
            'title'          => $result['promo']['title'],
            'discount_type'  => $result['promo']['discount_type'],
            'discount_value' => (float) $result['promo']['discount_value'],
            'discount'       => $result['discount'],
        ]);
        return $result;
    }
}
