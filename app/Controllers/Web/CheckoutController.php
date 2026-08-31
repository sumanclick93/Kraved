<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\StripeGateway;

final class CheckoutController extends Controller
{
    public function index(): void
    {
        if (Cart::count() === 0) {
            Session::flash('error', 'Your basket is empty.');
            $this->redirect('/');
        }

        $sessionUser = AuthMiddleware::user();
        $user = $sessionUser;
        if ($sessionUser) {
            $full = (new User())->find((int) $sessionUser['id']);
            if ($full) {
                $user = $full;
            }
        }

        $minError = null;
        if (!Cart::meetsMinOrder()) {
            $f = Cart::fulfillment();
            $minError = 'Minimum order for ' . ($f['postcode'] ?: 'your area') . ' is ' . Helpers::money((float) ($f['min_order'] ?? 0)) . '. Add a little more before placing the order.';
        }

        $this->view('Storefront/checkout/index', [
            'title'       => 'Checkout',
            'cart'        => Cart::summary(),
            'user'        => $user,
            'fulfillment' => Cart::fulfillment(),
            'cartCount'   => Cart::count(),
            'error'       => Session::flash('error') ?: $minError,
            'canPlace'    => Cart::meetsMinOrder(),
            'upsell'      => Category::checkoutUpsell(),
            'stripeEnabled' => StripeGateway::enabled(),
            'stripeMode'    => StripeGateway::mode(),
        ], 'Storefront/layouts/main');
    }

    public function upsell(): void
    {
        $this->json(Category::checkoutUpsell());
    }

    public function place(): void
    {
        Helpers::requireCsrf();

        if (Cart::count() === 0) {
            Session::flash('error', 'Your basket is empty.');
            $this->redirect('/');
        }

        $fulfillment = Cart::fulfillment();
        $type = ($fulfillment['type'] ?? 'collection') === 'delivery' ? 'delivery' : 'collection';
        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['customer_email'] ?? '')));
        $phone = trim((string) ($_POST['customer_phone'] ?? ''));
        $address = trim((string) ($_POST['delivery_address'] ?? ''));
        $postcode = Validator::normalizePostcode((string) ($_POST['postcode'] ?? ($fulfillment['postcode'] ?? '')));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $timeSlot = trim((string) ($_POST['delivery_time_slot'] ?? ($fulfillment['time_slot'] ?? 'ASAP')));
        if (strlen($timeSlot) > 40) {
            $timeSlot = 'ASAP';
        }
        if (mb_strlen($notes) > 500) {
            Session::flash('error', 'Order notes are too long.');
            $this->redirect('/checkout');
        }

        $v = new Validator();
        $v->name('customer_name', $name)
            ->email('customer_email', $email)
            ->phone('customer_phone', $phone);
        if ($type === 'delivery') {
            $v->address('delivery_address', $address)
                ->ukPostcode('postcode', $postcode, true);
        }
        if (!$v->ok()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/checkout');
        }

        $payment = 'cod';
        $postedPay = (string) ($_POST['payment_method'] ?? 'cod');
        if (in_array($postedPay, ['card', 'stripe'], true)) {
            $payment = 'card';
        }
        if ($payment === 'card' && !StripeGateway::enabled()) {
            Session::flash('error', 'Card payments are not available yet. Please pay on collection / delivery.');
            $this->redirect('/checkout');
        }
        $postedPromo = strtoupper(trim((string) ($_POST['promo_code'] ?? '')));
        if ($postedPromo !== '') {
            if (!preg_match('/^[A-Z0-9_-]{2,24}$/', $postedPromo)) {
                Session::flash('error', 'That promo code is not valid.');
                $this->redirect('/checkout');
            }
            $applied = (new PromoCode())->applyToSession($postedPromo, Cart::subtotal(), Cart::items());
            if (!$applied['ok']) {
                Session::flash('error', $applied['error'] ?? 'That promo code is not valid.');
                $this->redirect('/checkout');
            }
        }

        $deliveryFee = 0.0;
        if ($type === 'delivery') {
            $zone = (new DeliveryZone())->findByPrefix(Helpers::postcodePrefix($postcode));
            if (!$zone) {
                Session::flash('error', 'We cannot deliver to that postcode.');
                $this->redirect('/checkout');
            }
            if (Cart::subtotal() < (float) $zone['min_order_amount']) {
                Session::flash('error', 'Minimum order for ' . strtoupper((string) $zone['postcode_prefix']) . ' is ' . Helpers::money((float) $zone['min_order_amount']));
                $this->redirect('/checkout');
            }
            Cart::setFulfillment(array_merge($fulfillment, [
                'type' => 'delivery',
                'fee' => (float) $zone['delivery_fee'],
                'min_order' => (float) $zone['min_order_amount'],
                'postcode' => $postcode,
                'zone' => $zone,
            ]));
            $deliveryFee = Cart::deliveryFee();
        }

        $promoResult = Cart::promoQuote();
        $discount = (float) ($promoResult['discount'] ?? 0);
        $promoCode = $promoResult['code'] ?? null;
        $promoId = $promoResult['id'] ?? null;

        $customerId = null;
        $sessionUser = AuthMiddleware::user();
        if ($sessionUser) {
            $customerId = (int) $sessionUser['id'];
        } elseif (!empty($_POST['create_account'])) {
            $pw = (string) ($_POST['password'] ?? '');
            $pwCheck = new Validator();
            $pwCheck->password('password', $pw);
            if (!$pwCheck->ok()) {
                Session::flash('error', $pwCheck->firstError());
                $this->redirect('/checkout');
            }
            $userModel = new User();
            if (!$userModel->findByEmail($email)) {
                $customerId = $userModel->create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'password_hash' => password_hash($pw, PASSWORD_DEFAULT),
                    'role' => 'customer',
                    'address' => $address ?: null,
                    'postcode' => $postcode ?: null,
                ]);
                AuthMiddleware::loginCustomer([
                    'id' => $customerId,
                    'name' => $name,
                    'email' => $email,
                    'role' => 'customer',
                ]);
            }
        }

        $orderModel = new Order();
        try {
            $orderId = $orderModel->create([
                'order_number'       => Helpers::orderNumber(),
                'customer_id'        => $customerId,
                'fulfillment_type'   => $type,
                'customer_name'      => $name,
                'customer_email'     => $email,
                'customer_phone'     => $phone,
                'delivery_address'   => $type === 'delivery' ? $address : null,
                'postcode'           => $postcode ?: null,
                'subtotal'           => Cart::subtotal(),
                'delivery_fee'       => $deliveryFee,
                'discount_amount'    => $discount,
                'promo_code'         => $promoCode,
                'promo_id'           => $promoId,
                'total_amount'       => Cart::total(),
                'payment_status'     => $payment === 'cod' ? 'cod' : 'pending',
                'payment_method'     => $payment,
                'order_status'       => 'received',
                'delivery_time_slot' => $timeSlot,
                'notes'              => $notes ?: null,
            ]);

            $order = $orderModel->find($orderId);
            if (!$order) {
                throw new \RuntimeException('Order could not be created.');
            }
            foreach (Cart::items() as $item) {
                $addonsPayload = [
                    'addons' => $item['addons'] ?? [],
                    'box_picks' => $item['box_picks'] ?? [],
                    'variant' => !empty($item['variant_id']) ? [
                        'id' => $item['variant_id'],
                        'label' => $item['variant_label'] ?? null,
                    ] : null,
                ];
                $orderModel->addItem($orderId, [
                    'product_id'       => $item['product_id'],
                    'product_name'     => $item['name'],
                    'price'            => $item['unit_price'],
                    'quantity'         => $item['quantity'],
                    'addons_json'      => json_encode($addonsPayload),
                    'total_item_price' => $item['line_total'],
                ]);
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'We could not place your order. Please try again.');
            $this->redirect('/checkout');
        }

        if ($payment === 'card') {
            try {
                $session = StripeGateway::createCheckoutSession($order);
                try {
                    $orderModel->setStripeSession((int) $order['id'], $session['id'], $session['payment_intent']);
                } catch (\Throwable) {
                    // Order still matches via Stripe client_reference_id on return/webhook.
                }
                Session::set('pending_stripe_order', $order['order_number']);
                Helpers::redirect($session['url']);
            } catch (\Throwable $e) {
                try {
                    $orderModel->markPaymentFailed((int) $order['id']);
                } catch (\Throwable) {
                    // Keep the original Stripe error for the customer.
                }
                Session::flash('error', $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Card payment could not start. Check Stripe keys in Admin → Settings.');
                $this->redirect('/checkout');
            }
        }

        $this->finalisePlacedOrder($order, $promoId ? (int) $promoId : null);
    }

    public function stripeReturn(): void
    {
        $sessionId = trim((string) ($_GET['session_id'] ?? ''));
        try {
            $session = StripeGateway::retrieveSession($sessionId);
        } catch (\Throwable) {
            Session::flash('error', 'We could not confirm that payment. If you were charged, please contact the shop.');
            $this->redirect('/checkout');
        }

        $order = $this->orderFromStripeSession($session, $sessionId);
        if (!$order) {
            Session::flash('error', 'We could not match that payment to an order.');
            $this->redirect('/checkout');
        }

        $paid = ($session['payment_status'] ?? '') === 'paid' || ($session['status'] ?? '') === 'complete';
        if (!$paid) {
            (new Order())->discardUnpaid((int) $order['id']);
            Session::flash('error', 'Payment was not completed. You have not been charged.');
            $this->redirect('/checkout');
        }

        $intent = $session['payment_intent'] ?? null;
        $this->completeStripeOrder($order, is_string($intent) ? $intent : null);
    }

    public function stripeCancel(): void
    {
        $number = trim((string) ($_GET['order'] ?? (string) Session::get('pending_stripe_order', '')));
        $model = new Order();
        $order = $number !== '' ? $model->findByNumber($number) : null;
        if ($order && ($order['payment_status'] ?? '') === 'pending') {
            $model->markPaymentFailed((int) $order['id']);
        }
        Session::remove('pending_stripe_order');
        Session::flash('error', 'Card payment was cancelled. Your basket is still here — you can try again.');
        $this->redirect('/checkout');
    }

    public function stripeWebhook(): void
    {
        try {
            $event = StripeGateway::parseWebhook(
                Helpers::requestRaw(),
                (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '')
            );
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }

        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? [];
        if (!is_array($object)) {
            $this->json(['ok' => true]);
        }

        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $sessionId = (string) ($object['id'] ?? '');
            $order = $this->orderFromStripeSession($object, $sessionId);
            if ($order && (($object['payment_status'] ?? '') === 'paid' || ($object['status'] ?? '') === 'complete')) {
                $intent = $object['payment_intent'] ?? null;
                $model = new Order();
                $alreadyPaid = ($order['payment_status'] ?? '') === 'paid';
                $model->markPaid((int) $order['id'], is_string($intent) ? $intent : null);
                if (!$alreadyPaid && !empty($order['promo_id'])) {
                    (new PromoCode())->incrementUse((int) $order['promo_id']);
                }
            }
        } elseif (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $sessionId = (string) ($object['id'] ?? '');
            $order = $this->orderFromStripeSession($object, $sessionId);
            if ($order) {
                (new Order())->discardUnpaid((int) $order['id']);
            }
        }

        $this->json(['ok' => true]);
    }

    public function thanks(): void
    {
        $number = (string) Session::get('last_order_number', '');
        $this->view('Storefront/checkout/thanks', [
            'title'        => 'Order placed',
            'orderNumber'  => $number,
            'cartCount'    => Cart::count(),
            'fulfillment'  => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    private function finalisePlacedOrder(array $order, ?int $promoId): never
    {
        Cart::clear();
        if ($promoId) {
            (new PromoCode())->incrementUse($promoId);
        }
        PromoCode::clearApplied();
        Session::set('last_order_number', $order['order_number']);
        if (AuthMiddleware::check()) {
            $this->redirect('/order/' . $order['order_number']);
        }
        $this->redirect('/checkout/thanks');
    }

    /** @param array<string, mixed> $session */
    private function orderFromStripeSession(array $session, string $sessionId): ?array
    {
        $model = new Order();
        $order = $sessionId !== '' ? $model->findByStripeSession($sessionId) : null;
        if ($order) {
            return $order;
        }
        $number = (string) ($session['client_reference_id'] ?? '');
        if ($number === '') {
            $meta = $session['metadata'] ?? [];
            $number = is_array($meta) ? (string) ($meta['order_number'] ?? '') : '';
        }
        return $number !== '' ? $model->findByNumber($number) : null;
    }

    private function completeStripeOrder(array $order, ?string $paymentIntent): never
    {
        $model = new Order();
        $alreadyPaid = ($order['payment_status'] ?? '') === 'paid';
        $model->markPaid((int) $order['id'], $paymentIntent);
        if (!$alreadyPaid && !empty($order['promo_id'])) {
            (new PromoCode())->incrementUse((int) $order['promo_id']);
        }
        Cart::clear();
        PromoCode::clearApplied();
        Session::remove('pending_stripe_order');
        Session::set('last_order_number', $order['order_number']);
        if (AuthMiddleware::check()) {
            $this->redirect('/order/' . $order['order_number']);
        }
        $this->redirect('/checkout/thanks');
    }
}
