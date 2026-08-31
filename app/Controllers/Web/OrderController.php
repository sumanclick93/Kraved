<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Cart;
use App\Models\Order;

final class OrderController extends Controller
{
    public function history(): void
    {
        AuthMiddleware::requireCustomer('/orders');
        $user = AuthMiddleware::user();
        $orders = (new Order())->forCustomer((int) $user['id'], (string) ($user['email'] ?? ''));

        $this->view('Storefront/order/history', [
            'title'        => 'My orders',
            'orders'       => $orders,
            'user'         => $user,
            'accountPage'  => 'orders',
            'error'        => Session::flash('error'),
            'success'      => Session::flash('success'),
            'cartCount'    => Cart::count(),
            'fulfillment'  => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function status(string $number): void
    {
        $order = $this->ownedOrder($number);
        $this->view('Storefront/order/status', [
            'title'       => 'Order ' . $order['order_number'],
            'order'       => $order,
            'items'       => (new Order())->items((int) $order['id']),
            'cartCount'   => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
            'user'        => AuthMiddleware::user(),
            'accountPage' => 'orders',
            'steps'       => $this->stepsFor($order),
        ], 'Storefront/layouts/main');
    }

    public function invoice(string $number): void
    {
        $order = $this->ownedOrder($number);
        $this->view('Storefront/order/invoice', [
            'title' => 'Invoice ' . $order['order_number'],
            'order' => $order,
            'items' => (new Order())->items((int) $order['id']),
        ]);
    }

    public function statusJson(string $number): void
    {
        $user = AuthMiddleware::requireCustomerJson();
        $order = (new Order())->findByNumber($number);
        if (!$order || !(new Order())->belongsTo($order, $user) || !Order::isPlaced($order)) {
            $this->json(['error' => 'Not found'], 404);
        }
        $this->json([
            'order_status' => $order['order_status'],
            'label'        => Helpers::statusLabel($order['order_status']),
            'steps'        => $this->stepsFor($order),
            'updated_at'   => $order['status_updated_at'],
        ]);
    }

    private function ownedOrder(string $number): array
    {
        AuthMiddleware::requireCustomer('/order/' . $number);
        $user = AuthMiddleware::user() ?? [];
        $order = (new Order())->findByNumber($number);
        if (!$order || !(new Order())->belongsTo($order, $user) || !Order::isPlaced($order)) {
            http_response_code(404);
            $this->view('Storefront/order/not-found', [
                'title'       => 'Order not found',
                'cartCount'   => Cart::count(),
                'fulfillment' => Cart::fulfillment(),
            ], 'Storefront/layouts/main');
            exit;
        }
        return $order;
    }

    private function stepsFor(array $order): array
    {
        return Helpers::orderStatusSteps($order);
    }
}
