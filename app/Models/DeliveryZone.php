<?php

declare(strict_types=1);

namespace App\Models;

final class DeliveryZone extends Model
{
    protected string $table = 'delivery_zones';

    public function findByPrefix(string $prefix): ?array
    {
        $prefix = strtoupper(trim($prefix));
        $stmt = $this->db->prepare(
            'SELECT * FROM delivery_zones WHERE postcode_prefix = ? AND status = 1 LIMIT 1'
        );
        $stmt->execute([$prefix]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Try shorter prefixes (e.g. EC1A -> EC1 -> EC)
        for ($len = strlen($prefix) - 1; $len >= 2; $len--) {
            $try = substr($prefix, 0, $len);
            $stmt->execute([$try]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }
        return null;
    }

    public function active(): array
    {
        return $this->db->query(
            'SELECT * FROM delivery_zones WHERE status = 1 ORDER BY postcode_prefix ASC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO delivery_zones (postcode_prefix, delivery_fee, min_order_amount, estimated_mins, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            strtoupper($data['postcode_prefix']),
            $data['delivery_fee'],
            $data['min_order_amount'],
            $data['estimated_mins'] ?? 45,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE delivery_zones SET postcode_prefix=?, delivery_fee=?, min_order_amount=?, estimated_mins=?, status=? WHERE id=?'
        );
        return $stmt->execute([
            strtoupper($data['postcode_prefix']),
            $data['delivery_fee'],
            $data['min_order_amount'],
            $data['estimated_mins'] ?? 45,
            $data['status'] ?? 1,
            $id,
        ]);
    }
}
