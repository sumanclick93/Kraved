<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Helpers;
use App\Core\Session;

/**
 * Session-based shopping cart.
 */
final class Cart
{
    private const KEY = 'cart';
    private const FULFILLMENT = 'fulfillment';

    public static function items(): array
    {
        return Session::get(self::KEY, []);
    }

    public static function count(): int
    {
        $n = 0;
        foreach (self::items() as $item) {
            $n += (int) ($item['quantity'] ?? 1);
        }
        return $n;
    }

    public static function subtotal(): float
    {
        $sum = 0.0;
        foreach (self::items() as $item) {
            $sum += (float) $item['line_total'];
        }
        return round($sum, 2);
    }

    public static function add(array $item): void
    {
        $cart = self::items();
        $key = $item['key'] ?? md5(json_encode($item) ?: uniqid('', true));
        $item['key'] = $key;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += (int) $item['quantity'];
            $cart[$key]['line_total'] = round(
                (float) $cart[$key]['unit_price'] * (int) $cart[$key]['quantity'],
                2
            );
        } else {
            $cart[$key] = $item;
        }

        Session::set(self::KEY, $cart);
    }

    public static function updateQty(string $key, int $qty): void
    {
        $cart = self::items();
        if (!isset($cart[$key])) {
            return;
        }
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = $qty;
            $cart[$key]['line_total'] = round((float) $cart[$key]['unit_price'] * $qty, 2);
        }
        Session::set(self::KEY, $cart);
    }

    public static function remove(string $key): void
    {
        $cart = self::items();
        unset($cart[$key]);
        Session::set(self::KEY, $cart);
    }

    public static function clear(): void
    {
        Session::remove(self::KEY);
    }

    public static function setFulfillment(array $data): void
    {
        Session::set(self::FULFILLMENT, $data);
    }

    public static function fulfillment(): array
    {
        return Session::get(self::FULFILLMENT, [
            'type'       => 'collection',
            'postcode'   => '',
            'fee'        => 0,
            'min_order'  => 0,
            'time_slot'  => 'ASAP',
            'zone'       => null,
        ]);
    }

    public static function meetsMinOrder(): bool
    {
        $f = self::fulfillment();
        if (($f['type'] ?? 'collection') !== 'delivery') {
            return true;
        }
        $min = (float) ($f['min_order'] ?? 0);
        return $min <= 0 || self::subtotal() >= $min;
    }

    public static function deliveryFee(): float
    {
        $f = self::fulfillment();
        if (($f['type'] ?? 'collection') !== 'delivery') {
            return 0.0;
        }
        $threshold = (float) Helpers::setting('free_delivery_threshold', Helpers::config('free_delivery_threshold', 25));
        if (self::subtotal() >= $threshold) {
            return 0.0;
        }
        return (float) ($f['fee'] ?? 0);
    }

    public static function discount(): float
    {
        return (float) (self::promoQuote()['discount'] ?? 0);
    }

    public static function promoQuote(): array
    {
        $applied = PromoCode::applied();
        if (!$applied) {
            return ['discount' => 0.0, 'code' => null, 'id' => null, 'title' => null];
        }
        $result = (new PromoCode())->evaluate((string) $applied['code'], self::subtotal(), self::items());
        if (!$result['ok']) {
            PromoCode::clearApplied();
            return ['discount' => 0.0, 'code' => null, 'id' => null, 'title' => null, 'error' => $result['error'] ?? null];
        }
        Session::set(PromoCode::SESSION_KEY, [
            'id'             => (int) $result['promo']['id'],
            'code'           => $result['promo']['code'],
            'title'          => $result['promo']['title'],
            'discount_type'  => $result['promo']['discount_type'],
            'discount_value' => (float) $result['promo']['discount_value'],
            'discount'       => $result['discount'],
        ]);
        return [
            'discount' => (float) $result['discount'],
            'code'     => $result['promo']['code'],
            'id'       => (int) $result['promo']['id'],
            'title'    => $result['promo']['title'],
        ];
    }

    public static function total(): float
    {
        return round(max(0, self::subtotal() - self::discount()) + self::deliveryFee(), 2);
    }

    public static function summary(): array
    {
        $threshold = (float) Helpers::setting('free_delivery_threshold', Helpers::config('free_delivery_threshold', 25));
        $sub = self::subtotal();
        $promo = self::promoQuote();
        $f = self::fulfillment();
        $minOrder = (float) ($f['min_order'] ?? 0);
        return [
            'items'            => array_values(self::items()),
            'count'            => self::count(),
            'subtotal'         => $sub,
            'discount'         => (float) $promo['discount'],
            'promo'            => $promo['code'] ? $promo : null,
            'delivery_fee'     => self::deliveryFee(),
            'total'            => self::total(),
            'goods_total'      => round(max(0, $sub - (float) $promo['discount']), 2),
            'fulfillment'      => $f,
            'min_order'        => $minOrder,
            'meets_min_order'  => self::meetsMinOrder(),
            'free_delivery_at' => $threshold,
            'free_delivery_remaining' => max(0, round($threshold - $sub, 2)),
        ];
    }
}
