<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\AuthMiddleware;
use PDO;

final class Wishlist extends Model
{
    protected string $table = 'wishlists';

    /** @var list<int>|null */
    private static ?array $currentIds = null;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    public function ensureTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS wishlists (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_wishlist_user_product (user_id, product_id),
            KEY idx_wishlist_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        try {
            $this->db->exec($sql);
        } catch (\Throwable) {
            // Table already exists.
        }
    }

    /** @return list<int> */
    public function productIds(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return list<array<string, mixed>> */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    (SELECT MIN(pv.price) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS min_variant_price,
                    (SELECT COUNT(*) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS variant_count
             FROM wishlists w
             JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ? AND p.status = 1
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return (new ProductOffer())->decorate($stmt->fetchAll());
    }

    public function has(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$userId, $productId]);
        return (bool) $stmt->fetchColumn();
    }

    public function toggle(int $userId, int $productId): bool
    {
        if ($this->has($userId, $productId)) {
            $this->db->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
                ->execute([$userId, $productId]);
            self::$currentIds = null;
            return false;
        }

        $this->db->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)')
            ->execute([$userId, $productId]);
        self::$currentIds = null;
        return true;
    }

    public function remove(int $userId, int $productId): void
    {
        $this->db->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
            ->execute([$userId, $productId]);
        self::$currentIds = null;
    }

    /** @return list<int> */
    public static function currentProductIds(): array
    {
        if (self::$currentIds !== null) {
            return self::$currentIds;
        }
        $user = AuthMiddleware::user();
        if (!$user) {
            return self::$currentIds = [];
        }
        return self::$currentIds = (new self())->productIds((int) $user['id']);
    }
}
