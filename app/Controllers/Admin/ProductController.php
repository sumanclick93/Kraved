<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\AddonGroup;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;

final class ProductController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('Admin/products/index', [
            'title'    => 'Products',
            'products' => (new Product())->adminAll(),
            'success'  => Session::flash('success'),
        ], 'Admin/layouts/main');
    }

    public function create(): void
    {
        AuthMiddleware::requireAdmin();
        $this->formView(null);
    }

    public function edit(string $id): void
    {
        AuthMiddleware::requireAdmin();
        $product = (new Product())->find((int) $id);
        if (!$product) {
            $this->redirect('/admin/products');
        }
        $this->formView($product);
    }

    private function formView(?array $product): void
    {
        $productModel = new Product();
        $productId = $product ? (int) $product['id'] : 0;
        $this->view('Admin/products/form', [
            'title'           => $product ? 'Edit Product' : 'Add Product',
            'product'         => $product,
            'categories'      => (new Category())->all('display_order ASC'),
            'addonGroups'     => (new AddonGroup())->adminAll(),
            'allProducts'     => $productModel->adminAll(),
            'selectedGroups'  => $productId ? $productModel->addonGroupIds($productId) : [],
            'selectedChoices' => $productId ? $productModel->boxChoiceIds($productId) : [],
            'images'          => $productId ? $productModel->images($productId) : [],
            'variants'        => $productId ? $productModel->variants($productId) : [],
            'subs'            => $product && $product['category_id']
                ? (new SubCategory())->byCategory((int) $product['category_id'])
                : [],
        ], 'Admin/layouts/main');
    }

    public function store(): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $data = $this->payloadFromPost();
        $data['image'] = null;
        $productModel = new Product();
        $id = $productModel->create($data);
        $this->syncRelations($id);
        $this->handleImageUploads($id);
        $this->applyPrimaryImage($id);
        $productModel->syncCoverFromImages($id);
        Session::flash('success', 'Product created.');
        $this->redirect('/admin/products');
    }

    public function update(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $id = (int) $id;
        $productModel = new Product();
        $existing = $productModel->find($id);
        if (!$existing) {
            $this->redirect('/admin/products');
        }
        $data = $this->payloadFromPost();
        $data['image'] = $existing['image'];
        $productModel->update($id, $data);
        $this->syncRelations($id);
        $this->removeSelectedImages($id);
        $this->handleImageUploads($id);
        $this->applyPrimaryImage($id);
        $productModel->syncCoverFromImages($id);
        Session::flash('success', 'Product updated.');
        $this->redirect('/admin/products');
    }

    public function destroy(string $id): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        (new Product())->delete((int) $id);
        Session::flash('success', 'Product deleted.');
        $this->redirect('/admin/products');
    }

    private function payloadFromPost(): array
    {
        $title = trim($_POST['title'] ?? '');
        return [
            'category_id'       => (int) ($_POST['category_id'] ?? 0),
            'sub_category_id'   => (int) ($_POST['sub_category_id'] ?? 0) ?: null,
            'title'             => $title,
            'slug'              => Helpers::slugify($title),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'full_description'  => trim($_POST['full_description'] ?? ''),
            'base_price'        => (float) ($_POST['base_price'] ?? 0),
            'stock_qty'         => (int) ($_POST['stock_qty'] ?? 0),
            'weight_label'      => trim($_POST['weight_label'] ?? ''),
            'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
            'is_box_deal'       => isset($_POST['is_box_deal']) ? 1 : 0,
            'box_max_items'     => isset($_POST['is_box_deal']) ? (int) ($_POST['box_max_items'] ?? 4) : null,
            'status'            => isset($_POST['status']) ? 1 : 0,
            'display_order'     => (int) ($_POST['display_order'] ?? 0),
        ];
    }

    private function syncRelations(int $id): void
    {
        $productModel = new Product();
        $groups = array_map('intval', $_POST['addon_groups'] ?? []);
        $productModel->syncAddonGroups($id, $groups);

        if (isset($_POST['is_box_deal'])) {
            $choices = array_map('intval', $_POST['box_choices'] ?? []);
            $productModel->syncBoxChoices($id, $choices);
        }

        $productModel->syncVariants($id, $this->variantsFromPost());
    }

    /** @return list<array<string, mixed>> */
    private function variantsFromPost(): array
    {
        $labels = $_POST['variant_label'] ?? [];
        $prices = $_POST['variant_price'] ?? [];
        $stocks = $_POST['variant_stock'] ?? [];
        $skus = $_POST['variant_sku'] ?? [];
        $ids = $_POST['variant_id'] ?? [];
        $statuses = $_POST['variant_status'] ?? [];

        if (!is_array($labels)) {
            return [];
        }

        $rows = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $rows[] = [
                'id'            => (int) ($ids[$i] ?? 0) ?: null,
                'label'         => $label,
                'price'         => (float) ($prices[$i] ?? 0),
                'stock_qty'     => (int) ($stocks[$i] ?? 0),
                'sku'           => trim((string) ($skus[$i] ?? '')),
                'status'        => (int) ($statuses[$i] ?? 1) ? 1 : 0,
                'display_order' => $i,
            ];
        }
        return $rows;
    }

    private function handleImageUploads(int $productId): void
    {
        $files = $this->normalizeFiles('images');
        if (!$files) {
            // Legacy single field support
            $files = $this->normalizeFiles('image');
        }
        if (!$files) {
            return;
        }

        $productModel = new Product();
        $existing = $productModel->images($productId);
        $order = count($existing);
        $hasPrimary = false;
        foreach ($existing as $img) {
            if ((int) $img['is_primary'] === 1) {
                $hasPrimary = true;
                break;
            }
        }

        $dir = dirname(__DIR__, 3) . '/public/uploads/products/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
                continue;
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                continue;
            }
            $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                continue;
            }
            $filename = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                continue;
            }
            $path = 'products/' . $filename;
            $makePrimary = !$hasPrimary && $order === 0;
            $productModel->addImage($productId, $path, $makePrimary, $order);
            if ($makePrimary) {
                $hasPrimary = true;
            }
            $order++;
            // Avoid identical timestamps colliding in filenames within the same request
            usleep(1000);
        }
    }

    private function removeSelectedImages(int $productId): void
    {
        $removeIds = array_map('intval', $_POST['remove_images'] ?? []);
        if (!$removeIds) {
            return;
        }
        $productModel = new Product();
        $uploadRoot = dirname(__DIR__, 3) . '/public/uploads/';
        foreach ($removeIds as $imageId) {
            $path = $productModel->deleteImage($imageId, $productId);
            if ($path && is_file($uploadRoot . $path)) {
                @unlink($uploadRoot . $path);
            }
        }
    }

    private function applyPrimaryImage(int $productId): void
    {
        $primaryId = (int) ($_POST['primary_image'] ?? 0);
        if ($primaryId <= 0) {
            return;
        }
        (new Product())->setPrimaryImage($productId, $primaryId);
    }

    /**
     * Normalize single or multi file input into a list of file arrays.
     * @return list<array<string, mixed>>
     */
    private function normalizeFiles(string $field): array
    {
        if (empty($_FILES[$field])) {
            return [];
        }
        $f = $_FILES[$field];
        if (is_array($f['name'])) {
            $out = [];
            foreach ($f['name'] as $i => $name) {
                $out[] = [
                    'name'     => $name,
                    'type'     => $f['type'][$i] ?? '',
                    'tmp_name' => $f['tmp_name'][$i] ?? '',
                    'error'    => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $f['size'][$i] ?? 0,
                ];
            }
            return $out;
        }
        return [$f];
    }
}
