<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Validator;
use App\Models\Addon;
use App\Models\Cart;
use App\Models\DeliveryZone;
use App\Models\Product;

final class CartController extends Controller
{
    public function summary(): void
    {
        $this->json(Cart::summary());
    }

    public function add(): void
    {
        Helpers::requireCsrf();
        $input = array_merge($_POST, Helpers::requestJson());

        $productId = (int) ($input['product_id'] ?? 0);
        $qty = (int) ($input['quantity'] ?? 1);
        if ($qty < 1 || $qty > 20) {
            $this->json(['error' => 'Please choose a quantity between 1 and 20.'], 422);
        }
        $productModel = new Product();
        $product = $productModel->find($productId);
        if (!$product || !(int) ($product['status'] ?? 0)) {
            $this->json(['error' => 'Product not found'], 404);
        }

        try {
            $variants = $productModel->variants($productId, true);
        } catch (\Throwable) {
            $variants = [];
        }
        $variantId = (int) ($input['variant_id'] ?? 0);
        $variant = null;
        if ($variants) {
            if ($variantId <= 0) {
                $this->json(['error' => 'Please select a size / variant.'], 422);
            }
            $variant = $productModel->findVariant($variantId, $productId);
            if (!$variant) {
                $this->json(['error' => 'Selected variant is not available.'], 422);
            }
        }

        $rawAddons = $input['addon_ids'] ?? [];
        if (!is_array($rawAddons)) {
            $rawAddons = $rawAddons === '' || $rawAddons === null ? [] : [$rawAddons];
        }
        $addonIds = array_values(array_unique(array_filter(array_map('intval', $rawAddons))));
        $addons = (new Addon())->findMany($addonIds);
        $addonTotal = 0.0;
        $addonLabels = [];
        foreach ($addons as $a) {
            $addonTotal += (float) $a['price'];
            $addonLabels[] = [
                'id'    => (int) $a['id'],
                'name'  => $a['name'],
                'price' => (float) $a['price'],
            ];
        }

        $boxPicks = [];
        if ((int) $product['is_box_deal']) {
            $picks = $input['box_picks'] ?? [];
            $max = (int) ($product['box_max_items'] ?? 4);
            if (!is_array($picks) || count($picks) !== $max) {
                $this->json(['error' => "Please select exactly {$max} cookies."], 422);
            }
            foreach ($picks as $pid) {
                $p = $productModel->find((int) $pid);
                if ($p) {
                    $boxPicks[] = ['id' => (int) $p['id'], 'name' => $p['title']];
                }
            }
        }

        $base = $variant ? (float) $variant['price'] : (float) $product['base_price'];
        $offerModel = new \App\Models\ProductOffer();
        $offer = $offerModel->activeFor((int) $product['id']);
        $saleBase = $offerModel->salePrice($base, $offer);
        $unit = $saleBase + $addonTotal;
        $displayName = $product['title'];
        if ($variant) {
            $displayName .= ' (' . $variant['label'] . ')';
        }

        $item = [
            'product_id'   => (int) $product['id'],
            'variant_id'   => $variant ? (int) $variant['id'] : null,
            'variant_label'=> $variant['label'] ?? null,
            'name'         => $displayName,
            'image'        => $product['image'],
            'quantity'     => $qty,
            'original_unit_price' => round($base + $addonTotal, 2),
            'unit_price'   => round($unit, 2),
            'line_total'   => round($unit * $qty, 2),
            'offer_label'  => $offer ? $offerModel->badge($offer) : null,
            'addons'       => $addonLabels,
            'box_picks'    => $boxPicks,
            'is_box_deal'  => (int) $product['is_box_deal'],
        ];
        $item['key'] = md5(json_encode([
            $item['product_id'],
            $item['variant_id'],
            $addonIds,
            array_column($boxPicks, 'id'),
        ]) ?: uniqid('', true));

        Cart::add($item);
        $this->json(['ok' => true, 'cart' => Cart::summary()]);
    }

    public function applyPromo(): void
    {
        Helpers::requireCsrf();
        $input = array_merge($_POST, Helpers::requestJson());
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,24}$/', $code)) {
            $this->json(['error' => 'Enter a valid promo code.'], 422);
        }
        $result = (new \App\Models\PromoCode())->applyToSession($code, Cart::subtotal(), Cart::items());
        if (!$result['ok']) {
            $this->json(['error' => $result['error'] ?? 'Invalid code', 'cart' => Cart::summary()], 422);
        }
        $this->json(['ok' => true, 'message' => 'Promo applied.', 'cart' => Cart::summary()]);
    }

    public function removePromo(): void
    {
        Helpers::requireCsrf();
        \App\Models\PromoCode::clearApplied();
        $this->json(['ok' => true, 'cart' => Cart::summary()]);
    }

    public function update(): void
    {
        Helpers::requireCsrf();
        $input = array_merge($_POST, Helpers::requestJson());
        $key = (string) ($input['key'] ?? '');
        $qty = (int) ($input['quantity'] ?? 1);
        if ($key === '' || strlen($key) > 64) {
            $this->json(['error' => 'Invalid basket item.'], 422);
        }
        if ($qty > 20) {
            $this->json(['error' => 'Maximum quantity is 20.'], 422);
        }
        Cart::updateQty($key, $qty);
        $this->json(['ok' => true, 'cart' => Cart::summary()]);
    }

    public function remove(): void
    {
        Helpers::requireCsrf();
        $input = array_merge($_POST, Helpers::requestJson());
        Cart::remove((string) ($input['key'] ?? ''));
        $this->json(['ok' => true, 'cart' => Cart::summary()]);
    }

    public function setFulfillment(): void
    {
        Helpers::requireCsrf();
        $input = array_merge($_POST, Helpers::requestJson());
        $type = ($input['type'] ?? 'collection') === 'delivery' ? 'delivery' : 'collection';
        $zoneId = (int) ($input['zone_id'] ?? 0);
        $postcode = Validator::normalizePrefix((string) ($input['postcode'] ?? ''));
        $timeSlot = trim((string) ($input['time_slot'] ?? 'ASAP'));
        if (strlen($timeSlot) > 40) {
            $timeSlot = 'ASAP';
        }

        $fee = 0.0;
        $minOrder = 0.0;
        $zone = null;

        if ($type === 'delivery') {
            $v = new Validator();
            if ($zoneId <= 0 && $postcode === '') {
                $this->json(['error' => 'Please choose a delivery area.'], 422);
            }
            if ($postcode !== '') {
                $v->postcodePrefix('postcode', $postcode);
            }
            if (!$v->ok()) {
                $this->json(['error' => $v->firstError()], 422);
            }

            $zoneModel = new DeliveryZone();
            if ($zoneId > 0) {
                $found = $zoneModel->find($zoneId);
                if ($found && (int) ($found['status'] ?? 0) === 1) {
                    $zone = $found;
                    $postcode = strtoupper((string) $found['postcode_prefix']);
                }
            }
            if (!$zone) {
                $prefix = Helpers::postcodePrefix($postcode);
                $zone = $zoneModel->findByPrefix($prefix);
            }
            if (!$zone) {
                $this->json([
                    'ok' => false,
                    'available' => false,
                    'message' => "Sorry, we don't deliver to that area yet.",
                ], 422);
            }
            $fee = (float) $zone['delivery_fee'];
            $minOrder = (float) $zone['min_order_amount'];
        }

        Cart::setFulfillment([
            'type'      => $type,
            'postcode'  => $postcode,
            'fee'       => $fee,
            'min_order' => $minOrder,
            'time_slot' => $timeSlot ?: 'ASAP',
            'zone'      => $zone,
        ]);

        $this->json([
            'ok' => true,
            'available' => true,
            'fulfillment' => Cart::fulfillment(),
            'cart' => Cart::summary(),
            'message' => $type === 'delivery'
                ? 'Delivery available to ' . $postcode . ' · min order ' . Helpers::money($minOrder)
                : 'Collection selected',
        ]);
    }
}
