<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;

final class ProductController extends Controller
{
    public function menuPage(): void
    {
        $categories = (new Category())->withSubCategories();
        $productModel = new Product();
        $menu = [];
        foreach ($categories as $cat) {
            $products = $productModel->byCategory((int) $cat['id']);
            if ($products) {
                $menu[] = [
                    'category' => $cat,
                    'products' => $products,
                ];
            }
        }

        $gallery = [
            \App\Core\Helpers::asset('images/product-lava-cake.png'),
            \App\Core\Helpers::asset('images/product-biscoff-cheesecake.png'),
            \App\Core\Helpers::asset('images/product-cookies-cream.png'),
            \App\Core\Helpers::asset('images/product-choc-waffle.png'),
            \App\Core\Helpers::asset('images/hero-dessert.png'),
        ];

        $this->view('Storefront/products/menu', [
            'title'       => 'Menu - ' . \App\Core\Helpers::setting('store_name', 'Kraved'),
            'categories'  => $categories,
            'menu'        => $menu,
            'gallery'     => $gallery,
            'cms'         => \App\Models\SiteSection::map(),
            'cartCount'   => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function menu(): void
    {
        $this->menuPage();
    }

    public function category(string $slug): void
    {
        $category = (new Category())->findBySlug($slug);
        if (!$category) {
            http_response_code(404);
            echo 'Category not found';
            return;
        }

        $this->view('Storefront/products/category', [
            'title'      => $category['name'],
            'category'   => $category,
            'products'   => (new Product())->byCategory((int) $category['id']),
            'categories' => (new Category())->withSubCategories(),
            'cartCount'  => Cart::count(),
            'fulfillment'=> Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function subCategory(string $slug): void
    {
        $sub = (new SubCategory())->findBySlug($slug);
        if (!$sub) {
            http_response_code(404);
            echo 'Sub-category not found';
            return;
        }

        $stmt = $this->db()->prepare(
            'SELECT p.*,
                    (SELECT MIN(pv.price) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS min_variant_price,
                    (SELECT COUNT(*) FROM product_variants pv
                     WHERE pv.product_id = p.id AND pv.status = 1) AS variant_count
             FROM products p
             WHERE p.sub_category_id = ? AND p.status = 1
             ORDER BY p.display_order ASC'
        );
        $stmt->execute([(int) $sub['id']]);
        $products = (new \App\Models\ProductOffer())->decorate($stmt->fetchAll());

        $this->view('Storefront/products/category', [
            'title'      => $sub['name'],
            'category'   => $sub,
            'products'   => $products,
            'categories' => (new Category())->withSubCategories(),
            'cartCount'  => Cart::count(),
            'fulfillment'=> Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    /** AJAX: product customisation payload */
    public function customise(string $id): void
    {
        $product = (new Product())->find((int) $id);
        if (!$product || !(int) $product['status']) {
            $this->json(['error' => 'Product not found'], 404);
        }

        $productModel = new Product();
        $productId = (int) $id;
        try {
            $images = $productModel->images($productId);
        } catch (\Throwable) {
            $images = [];
        }
        $imagePaths = array_column($images, 'image_path');
        if (!$imagePaths && !empty($product['image'])) {
            $imagePaths = [$product['image']];
        }

        $offerModel = new \App\Models\ProductOffer();
        $offer = $offerModel->activeFor($productId);
        try {
            $variants = $productModel->variants($productId, true);
        } catch (\Throwable) {
            $variants = [];
        }
        foreach ($variants as &$v) {
            $v['original_price'] = (float) $v['price'];
            $v['sale_price'] = $offerModel->salePrice((float) $v['price'], $offer);
        }
        unset($v);

        try {
            $groups = $productModel->withAddonGroups($productId);
        } catch (\Throwable) {
            $groups = [];
        }

        $boxChoices = [];
        if ((int) $product['is_box_deal']) {
            try {
                $boxChoices = $productModel->boxChoices($productId);
            } catch (\Throwable) {
                $boxChoices = [];
            }
        }

        $payload = [
            'product' => $product,
            'offer'   => $offer,
            'sale_price' => $offerModel->salePrice((float) $product['base_price'], $offer),
            'groups'  => $groups,
            'box_choices' => $boxChoices,
            'variants' => $variants,
            'images'   => $imagePaths,
        ];
        $this->json($payload);
    }

    public function categoryProducts(string $id): void
    {
        $categoryId = (int) $id;
        $category = (new Category())->find($categoryId);
        if (!$category || !(int) ($category['status'] ?? 0)) {
            $this->json(['error' => 'Category not found'], 404);
        }

        $this->json([
            'category' => [
                'id'    => (int) $category['id'],
                'name'  => $category['name'],
                'slug'  => $category['slug'],
                'image' => !empty($category['image']) ? \App\Core\Helpers::upload((string) $category['image']) : null,
            ],
            'products' => array_map(
                [$this, 'cardPayload'],
                (new Product())->byCategory($categoryId)
            ),
        ]);
    }

    private function cardPayload(array $p): array
    {
        $variantCount = (int) ($p['variant_count'] ?? 0);
        $display = isset($p['sale_price'])
            ? (float) $p['sale_price']
            : ($variantCount > 0 && $p['min_variant_price'] !== null
                ? (float) $p['min_variant_price']
                : (float) $p['base_price']);
        $original = (float) ($p['original_price'] ?? $display);
        $img = !empty($p['image'])
            ? \App\Core\Helpers::upload((string) $p['image'])
            : \App\Core\Helpers::asset('images/product-lava-cake.png');

        return [
            'id'                => (int) $p['id'],
            'title'             => $p['title'],
            'short_description' => $p['short_description'] ?? '',
            'image'             => $img,
            'price'             => $display,
            'original_price'    => $original,
            'on_offer'          => !empty($p['on_offer']),
            'offer_badge'       => $p['offer_badge'] ?? '',
            'from'              => $variantCount > 1,
            'weight_label'      => $p['weight_label'] ?? '',
        ];
    }
}
