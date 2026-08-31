<?php

declare(strict_types=1);

namespace App\Models;

final class Order extends Model
{
    protected string $table = 'orders';

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders
            (order_number, customer_id, fulfillment_type, customer_name, customer_email, customer_phone,
             delivery_address, postcode, subtotal, delivery_fee, discount_amount, promo_code, promo_id,
             total_amount, payment_status, payment_method,
             order_status, delivery_time_slot, scheduled_at, notes, status_updated_at, placed_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),IF(?=1,NOW(),NULL))'
        );
        $paymentMethod = (string) ($data['payment_method'] ?? 'cod');
        $paymentStatus = (string) ($data['payment_status'] ?? 'pending');
        $isPlaced = !self::isUnplacedPayment($paymentMethod, $paymentStatus);
        $stmt->execute([
            $data['order_number'],
            $data['customer_id'] ?? null,
            $data['fulfillment_type'],
            $data['customer_name'],
            $data['customer_email'],
            $data['customer_phone'],
            $data['delivery_address'] ?? null,
            $data['postcode'] ?? null,
            $data['subtotal'],
            $data['delivery_fee'],
            $data['discount_amount'] ?? 0,
            $data['promo_code'] ?? null,
            $data['promo_id'] ?? null,
            $data['total_amount'],
            $paymentStatus,
            $paymentMethod,
            $data['order_status'] ?? 'received',
            $data['delivery_time_slot'] ?? 'ASAP',
            $data['scheduled_at'] ?? null,
            $data['notes'] ?? null,
            $isPlaced ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addItem(int $orderId, array $item): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, price, quantity, addons_json, total_item_price)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $orderId,
            $item['product_id'] ?? null,
            $item['product_name'],
            $item['price'],
            $item['quantity'],
            $item['addons_json'] ?? null,
            $item['total_item_price'],
        ]);
    }

    public function findByNumber(string $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
        $stmt->execute([$number]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function forCustomer(int $customerId, ?string $email = null): array
    {
        $sql = 'SELECT o.*,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
                    (SELECT GROUP_CONCAT(oi.product_name ORDER BY oi.id SEPARATOR \'||\')
                       FROM order_items oi WHERE oi.order_id = o.id) AS item_names
                FROM orders o
                WHERE ' . self::placedSql('o') . '
                  AND (o.customer_id = ?';
        $params = [$customerId];

        if ($email !== null && $email !== '') {
            $sql .= ' OR (o.customer_id IS NULL AND LOWER(o.customer_email) = LOWER(?))';
            $params[] = $email;
        }
        $sql .= ')';

        $sql .= ' ORDER BY o.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function claimGuestOrders(int $customerId, string $email): void
    {
        if ($email === '') {
            return;
        }
        $stmt = $this->db->prepare(
            'UPDATE orders SET customer_id = ?
             WHERE customer_id IS NULL AND LOWER(customer_email) = LOWER(?)'
        );
        $stmt->execute([$customerId, $email]);
    }

    public function belongsTo(array $order, array $user): bool
    {
        $userId = (int) ($user['id'] ?? 0);
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($userId > 0 && (int) ($order['customer_id'] ?? 0) === $userId) {
            return true;
        }
        return $email !== '' && strtolower(trim((string) ($order['customer_email'] ?? ''))) === $email;
    }

    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET order_status = ?, status_updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$status, $id]);
    }

    public function findByStripeSession(string $sessionId): ?array
    {
        if ($sessionId === '') {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE stripe_session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setStripeSession(int $id, string $sessionId, ?string $paymentIntent = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET stripe_session_id = ?, stripe_payment_intent = COALESCE(?, stripe_payment_intent) WHERE id = ?'
        );
        $stmt->execute([$sessionId, $paymentIntent, $id]);
    }

    public function markPaid(int $id, ?string $paymentIntent = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET payment_status = 'paid',
                 order_status = CASE
                    WHEN order_status IN ('cancelled') OR placed_at IS NULL THEN 'received'
                    ELSE order_status
                 END,
                 placed_at = COALESCE(placed_at, NOW()),
                 status_updated_at = CASE WHEN placed_at IS NULL THEN NOW() ELSE status_updated_at END,
                 stripe_payment_intent = COALESCE(?, stripe_payment_intent)
             WHERE id = ? AND payment_status != 'paid'"
        );
        $stmt->execute([$paymentIntent, $id]);
    }

    public function markPaymentFailed(int $id): void
    {
        $this->discardUnpaid($id);
    }

    public function discardUnpaid(int $id): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM orders
             WHERE id = ?
               AND payment_method IN ('card','stripe')
               AND payment_status = 'pending'"
        );
        $stmt->execute([$id]);
    }

    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE ' . self::placedSql('') . ' ORDER BY COALESCE(placed_at, created_at) DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function pendingCount(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM orders
             WHERE order_status IN ('received','baking','ready','out_for_delivery')
               AND " . self::placedSql('')
        )->fetchColumn();
    }

    public function todayStats(): array
    {
        $placed = self::placedSql('');
        $sales = (float) $this->db->query(
            "SELECT COALESCE(SUM(total_amount),0) FROM orders
             WHERE DATE(COALESCE(placed_at, created_at)) = CURDATE()
               AND order_status != 'cancelled'
               AND payment_status IN ('paid','cod')
               AND {$placed}"
        )->fetchColumn();

        $count = (int) $this->db->query(
            "SELECT COUNT(*) FROM orders
             WHERE DATE(COALESCE(placed_at, created_at)) = CURDATE()
               AND {$placed}"
        )->fetchColumn();

        return [
            'sales_today'   => $sales,
            'orders_today'  => $count,
            'pending'       => $this->pendingCount(),
        ];
    }

    public function latestId(): int
    {
        return (int) $this->db->query(
            'SELECT COALESCE(MAX(id),0) FROM orders WHERE ' . self::placedSql('')
        )->fetchColumn();
    }

    public function latestPlacedAt(): string
    {
        $value = $this->db->query(
            'SELECT MAX(placed_at) FROM orders WHERE ' . self::placedSql('')
        )->fetchColumn();
        return is_string($value) && $value !== '' ? $value : '';
    }

    public function sinceId(int $afterId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE id > ? AND ' . self::placedSql('') . ' ORDER BY id ASC'
        );
        $stmt->execute([$afterId]);
        return $stmt->fetchAll();
    }

    public function placedSince(string $placedAfter, int $afterId = 0): array
    {
        if ($placedAfter === '') {
            return $this->sinceId($afterId);
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM orders
             WHERE ' . self::placedSql('') . '
               AND placed_at IS NOT NULL
               AND (placed_at > ? OR (placed_at = ? AND id > ?))
             ORDER BY placed_at ASC, id ASC'
        );
        $stmt->execute([$placedAfter, $placedAfter, $afterId]);
        return $stmt->fetchAll();
    }

    public static function isPlaced(array $order): bool
    {
        return !self::isUnplacedPayment(
            (string) ($order['payment_method'] ?? 'cod'),
            (string) ($order['payment_status'] ?? '')
        );
    }

    private static function isUnplacedPayment(string $method, string $status): bool
    {
        return in_array($method, ['card', 'stripe'], true)
            && in_array($status, ['pending', 'failed'], true);
    }

    private static function placedSql(string $alias = ''): string
    {
        $col = $alias !== '' ? $alias . '.' : '';
        return "NOT ({$col}payment_method IN ('card','stripe') AND {$col}payment_status IN ('pending','failed'))";
    }
}
