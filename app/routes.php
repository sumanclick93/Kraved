<?php

declare(strict_types=1);

use App\Controllers\Admin\AddonController as AdminAddonController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CmsController as AdminCmsController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DeliveryZoneController;
use App\Controllers\Admin\OfferController as AdminOfferController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Web\AccountController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\CartController;
use App\Controllers\Web\CheckoutController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\OrderController;
use App\Controllers\Web\ProductController;
use App\Controllers\Web\WishlistController;
use App\Core\Router;

$router = new Router();

// Storefront
$router->get('/', [HomeController::class, 'index']);
$router->get('/category/{slug}', [ProductController::class, 'category']);
$router->get('/subcategory/{slug}', [ProductController::class, 'subCategory']);
$router->get('/api/product/{id}/customise', [ProductController::class, 'customise']);
$router->get('/api/category/{id}/products', [ProductController::class, 'categoryProducts']);
$router->get('/api/checkout/upsell', [CheckoutController::class, 'upsell']);

$router->get('/api/cart', [CartController::class, 'summary']);
$router->post('/api/cart/add', [CartController::class, 'add']);
$router->post('/api/cart/update', [CartController::class, 'update']);
$router->post('/api/cart/remove', [CartController::class, 'remove']);
$router->post('/api/cart/promo', [CartController::class, 'applyPromo']);
$router->post('/api/cart/promo/remove', [CartController::class, 'removePromo']);
$router->post('/api/fulfillment', [CartController::class, 'setFulfillment']);

$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'place']);
$router->get('/checkout/thanks', [CheckoutController::class, 'thanks']);
$router->get('/checkout/stripe/return', [CheckoutController::class, 'stripeReturn']);
$router->get('/checkout/stripe/cancel', [CheckoutController::class, 'stripeCancel']);
$router->post('/webhooks/stripe', [CheckoutController::class, 'stripeWebhook']);

$router->get('/orders', [OrderController::class, 'history']);
$router->get('/order/{number}/invoice', [OrderController::class, 'invoice']);
$router->get('/order/{number}', [OrderController::class, 'status']);
$router->get('/api/order/{number}/status', [OrderController::class, 'statusJson']);

$router->get('/account', [AccountController::class, 'profile']);
$router->post('/account', [AccountController::class, 'update']);
$router->get('/wishlist', [WishlistController::class, 'index']);
$router->post('/api/wishlist/toggle', [WishlistController::class, 'toggle']);

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Admin auth
$router->get('/admin/login', [AdminAuthController::class, 'loginForm']);
$router->post('/admin/login', [AdminAuthController::class, 'login']);
$router->get('/admin/logout', [AdminAuthController::class, 'logout']);

// Admin
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->post('/admin/categories', [AdminCategoryController::class, 'store']);
$router->post('/admin/categories/{id}/update', [AdminCategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy']);
$router->post('/admin/subcategories', [AdminCategoryController::class, 'storeSub']);
$router->post('/admin/subcategories/{id}/update', [AdminCategoryController::class, 'updateSub']);
$router->post('/admin/subcategories/{id}/delete', [AdminCategoryController::class, 'destroySub']);
$router->post('/admin/categories/reorder', [AdminCategoryController::class, 'reorder']);
$router->get('/admin/api/categories/{categoryId}/subs', [AdminCategoryController::class, 'subsJson']);

$router->get('/admin/products', [AdminProductController::class, 'index']);
$router->get('/admin/products/create', [AdminProductController::class, 'create']);
$router->post('/admin/products', [AdminProductController::class, 'store']);
$router->get('/admin/products/{id}/edit', [AdminProductController::class, 'edit']);
$router->post('/admin/products/{id}/update', [AdminProductController::class, 'update']);
$router->post('/admin/products/{id}/delete', [AdminProductController::class, 'destroy']);

$router->get('/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/admin/orders/{id}', [AdminOrderController::class, 'show']);
$router->get('/admin/orders/{id}/receipt', [AdminOrderController::class, 'receipt']);
$router->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
$router->get('/admin/api/orders/poll', [AdminOrderController::class, 'poll']);

$router->get('/admin/addons', [AdminAddonController::class, 'index']);
$router->post('/admin/addons/groups', [AdminAddonController::class, 'storeGroup']);
$router->post('/admin/addons', [AdminAddonController::class, 'storeAddon']);
$router->post('/admin/addons/groups/{id}/update', [AdminAddonController::class, 'updateGroup']);
$router->post('/admin/addons/{id}/update', [AdminAddonController::class, 'updateAddon']);
$router->post('/admin/addons/groups/{id}/delete', [AdminAddonController::class, 'destroyGroup']);
$router->post('/admin/addons/{id}/delete', [AdminAddonController::class, 'destroyAddon']);

$router->get('/admin/offers', [AdminOfferController::class, 'index']);
$router->post('/admin/offers/products', [AdminOfferController::class, 'storeProductOffer']);
$router->post('/admin/offers/products/{id}/update', [AdminOfferController::class, 'updateProductOffer']);
$router->post('/admin/offers/products/{id}/delete', [AdminOfferController::class, 'destroyProductOffer']);
$router->post('/admin/offers/promos', [AdminOfferController::class, 'storePromo']);
$router->post('/admin/offers/promos/{id}/update', [AdminOfferController::class, 'updatePromo']);
$router->post('/admin/offers/promos/{id}/delete', [AdminOfferController::class, 'destroyPromo']);

$router->get('/admin/delivery-zones', [DeliveryZoneController::class, 'index']);
$router->post('/admin/delivery-zones', [DeliveryZoneController::class, 'store']);
$router->post('/admin/delivery-zones/{id}/update', [DeliveryZoneController::class, 'update']);
$router->post('/admin/delivery-zones/{id}/delete', [DeliveryZoneController::class, 'destroy']);

$router->get('/admin/cms', [AdminCmsController::class, 'index']);
$router->get('/admin/cms/{key}/edit', [AdminCmsController::class, 'edit']);
$router->post('/admin/cms/{key}/update', [AdminCmsController::class, 'update']);
$router->post('/admin/cms/{key}/toggle', [AdminCmsController::class, 'toggle']);

$router->get('/admin/settings', [AdminSettingsController::class, 'index']);
$router->post('/admin/settings', [AdminSettingsController::class, 'update']);

return $router;
