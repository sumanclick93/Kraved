<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Core\Validator;
use App\Models\DeliveryZone;

final class DeliveryZoneController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('Admin/delivery_zones/index', [
            'title'  => 'Delivery Zones',
            'zones'  => (new DeliveryZone())->all('postcode_prefix ASC'),
            'success'=> Session::flash('success'),
            'error'  => Session::flash('error'),
        ], 'Admin/layouts/main');
    }

    public function store(): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $payload = $this->validatedPayload(false);
        if ($payload === null) {
            $this->redirect('/admin/delivery-zones');
        }
        (new DeliveryZone())->create($payload);
        Session::flash('success', 'Zone added.');
        $this->redirect('/admin/delivery-zones');
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $payload = $this->validatedPayload(true);
        if ($payload === null) {
            $this->redirect('/admin/delivery-zones');
        }
        (new DeliveryZone())->update((int) $id, $payload);
        Session::flash('success', 'Zone updated.');
        $this->redirect('/admin/delivery-zones');
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        (new DeliveryZone())->delete((int) $id);
        Session::flash('success', 'Zone deleted.');
        $this->redirect('/admin/delivery-zones');
    }

    /** @return array<string, mixed>|null */
    private function validatedPayload(bool $updating): ?array
    {
        $prefix = Validator::normalizePrefix((string) ($_POST['postcode_prefix'] ?? ''));
        $fee = $_POST['delivery_fee'] ?? 0;
        $min = $_POST['min_order_amount'] ?? 0;
        $eta = $_POST['estimated_mins'] ?? 45;

        $v = new Validator();
        $v->postcodePrefix('postcode_prefix', $prefix)
            ->money('delivery_fee', $fee, 'Delivery fee')
            ->money('min_order_amount', $min, 'Minimum order')
            ->intRange('estimated_mins', $eta, 5, 180, 'ETA');
        if (!$v->ok()) {
            Session::flash('error', $v->firstError());
            return null;
        }

        return [
            'postcode_prefix'  => $prefix,
            'delivery_fee'     => round((float) $fee, 2),
            'min_order_amount' => round((float) $min, 2),
            'estimated_mins'   => (int) $eta,
            'status'           => $updating ? (isset($_POST['status']) ? 1 : 0) : 1,
        ];
    }
}
