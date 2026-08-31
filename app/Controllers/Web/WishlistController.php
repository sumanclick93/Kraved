<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;

final class WishlistController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireCustomer('/wishlist');
        $user = AuthMiddleware::user() ?? [];
        $this->view('Storefront/account/wishlist', [
            'title'       => 'Wishlist',
            'products'    => (new Wishlist())->forUser((int) $user['id']),
            'accountPage' => 'wishlist',
            'cartCount'   => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function toggle(): void
    {
        Helpers::requireCsrf();
        $user = AuthMiddleware::requireCustomerJson();
        $input = array_merge($_POST, Helpers::requestJson());
        $productId = (int) ($input['product_id'] ?? 0);
        $product = (new Product())->find($productId);
        if (!$product || !(int) $product['status']) {
            $this->json(['error' => 'Product not found'], 404);
        }

        $wishlist = new Wishlist();
        $wished = $wishlist->toggle((int) $user['id'], $productId);
        $ids = $wishlist->productIds((int) $user['id']);
        $this->json([
            'wished' => $wished,
            'count'  => count($ids),
            'ids'    => $ids,
        ]);
    }
}
