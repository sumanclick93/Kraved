<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Helpers;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSection;

final class HomeController extends Controller
{
    public function index(): void
    {
        $categories = (new Category())->withSubCategories();
        $productModel = new Product();
        $menu = [];
        foreach ($categories as $cat) {
            $menu[] = [
                'category' => $cat,
                'products' => $productModel->byCategory((int) $cat['id']),
            ];
        }

        $popularCfg = SiteSection::content('popular');
        $limit = max(1, min(24, (int) ($popularCfg['limit'] ?? 8)));
        $featured = $productModel->featured($limit);
        $popular = [];
        foreach ($featured as $p) {
            $popular[] = [
                'id' => (int) $p['id'],
                'title' => $p['title'],
                'short_description' => $p['short_description'] ?? '',
                'base_price' => $p['base_price'],
                'image_url' => !empty($p['image'])
                    ? Helpers::upload($p['image'])
                    : Helpers::asset('images/hero-dessert.png'),
                'badge' => ['Featured', 'best'],
            ];
        }

        $gallery = [
            Helpers::asset('images/product-lava-cake.png'),
            Helpers::asset('images/product-biscoff-cheesecake.png'),
            Helpers::asset('images/product-cookies-cream.png'),
            Helpers::asset('images/product-choc-waffle.png'),
            Helpers::asset('images/hero-dessert.png'),
        ];

        $hero = SiteSection::content('hero');
        $pageTitle = trim(($hero['headline_line1'] ?? '') . ' ' . ($hero['headline_line2'] ?? ''));
        if ($pageTitle === '') {
            $pageTitle = (string) Helpers::setting('store_name', 'Kraved');
        }

        $this->view('Storefront/home/index', [
            'title'       => $pageTitle,
            'categories'  => $categories,
            'menu'        => $menu,
            'featured'    => $featured,
            'popular'     => $popular,
            'gallery'     => $gallery,
            'cms'         => SiteSection::map(),
            'fulfillment' => Cart::fulfillment(),
            'cartCount'   => Cart::count(),
        ], 'Storefront/layouts/main');
    }
}
