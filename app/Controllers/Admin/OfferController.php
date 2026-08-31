<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\PromoCode;

final class OfferController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $promoModel = new PromoCode();
        $promos = $promoModel->adminAll();
        foreach ($promos as &$p) {
            $p['product_ids'] = $promoModel->productIds((int) $p['id']);
        }
        unset($p);

        $this->view('Admin/offers/index', [
            'title'    => 'Offers & promo codes',
            'offers'   => (new ProductOffer())->adminAll(),
            'promos'   => $promos,
            'products' => (new Product())->adminAll(),
            'success'  => Session::flash('success'),
            'error'    => Session::flash('error'),
        ], 'Admin/layouts/main');
    }

    public function storeProductOffer(): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            Session::flash('error', 'Choose a product.');
            $this->redirect('/admin/offers');
        }
        (new ProductOffer())->create($this->offerPayload());
        Session::flash('success', 'Product offer created.');
        $this->redirect('/admin/offers');
    }

    public function updateProductOffer(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        (new ProductOffer())->update((int) $id, $this->offerPayload());
        Session::flash('success', 'Product offer updated.');
        $this->redirect('/admin/offers');
    }

    public function destroyProductOffer(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        (new ProductOffer())->delete((int) $id);
        Session::flash('success', 'Product offer deleted.');
        $this->redirect('/admin/offers');
    }

    public function storePromo(): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,24}$/', $code)) {
            Session::flash('error', 'Enter a valid promo code (letters, numbers, dash or underscore).');
            $this->redirect('/admin/offers');
        }
        if ((new PromoCode())->findByCode($code)) {
            Session::flash('error', 'That promo code already exists.');
            $this->redirect('/admin/offers');
        }
        $id = (new PromoCode())->create($this->promoPayload());
        (new PromoCode())->syncProducts($id, $this->postedProductIds());
        Session::flash('success', 'Promo code created.');
        $this->redirect('/admin/offers');
    }

    public function updatePromo(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $id = (int) $id;
        (new PromoCode())->update($id, $this->promoPayload());
        (new PromoCode())->syncProducts($id, $this->postedProductIds());
        Session::flash('success', 'Promo code updated.');
        $this->redirect('/admin/offers');
    }

    public function destroyPromo(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        (new PromoCode())->delete((int) $id);
        Session::flash('success', 'Promo code deleted.');
        $this->redirect('/admin/offers');
    }

    private function offerPayload(): array
    {
        $type = ($_POST['discount_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        return [
            'product_id'     => (int) ($_POST['product_id'] ?? 0),
            'title'          => trim((string) ($_POST['title'] ?? 'Special offer')) ?: 'Special offer',
            'discount_type'  => $type,
            'discount_value' => max(0, (float) ($_POST['discount_value'] ?? 0)),
            'starts_at'      => $this->dt($_POST['starts_at'] ?? ''),
            'ends_at'        => $this->dt($_POST['ends_at'] ?? ''),
            'status'         => isset($_POST['status']) ? 1 : 0,
        ];
    }

    private function promoPayload(): array
    {
        $type = ($_POST['discount_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $max = trim((string) ($_POST['max_uses'] ?? ''));
        return [
            'code'           => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'title'          => trim((string) ($_POST['title'] ?? '')) ?: 'Promo',
            'discount_type'  => $type,
            'discount_value' => max(0, (float) ($_POST['discount_value'] ?? 0)),
            'min_order'      => max(0, (float) ($_POST['min_order'] ?? 0)),
            'max_uses'       => $max === '' ? null : max(1, (int) $max),
            'starts_at'      => $this->dt($_POST['starts_at'] ?? ''),
            'ends_at'        => $this->dt($_POST['ends_at'] ?? ''),
            'status'         => isset($_POST['status']) ? 1 : 0,
        ];
    }

    private function postedProductIds(): array
    {
        $ids = $_POST['product_ids'] ?? [];
        return is_array($ids) ? $ids : [];
    }

    private function dt(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $t = strtotime($value);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }
}
