<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Helpers;

final class Category extends Model
{
    protected string $table = 'categories';

    public function activeOrdered(): array
    {
        return $this->db->query(
            'SELECT * FROM categories WHERE status = 1 ORDER BY display_order ASC, name ASC'
        )->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, image, display_order, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['image'] ?? null,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name=?, slug=?, image=?, display_order=?, status=? WHERE id=?'
        );
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['image'] ?? null,
            $data['display_order'] ?? 0,
            $data['status'] ?? 1,
            $id,
        ]);
    }

    public function updateOrder(array $ids): void
    {
        $stmt = $this->db->prepare('UPDATE categories SET display_order = ? WHERE id = ?');
        foreach ($ids as $order => $id) {
            $stmt->execute([(int) $order + 1, (int) $id]);
        }
    }

    public function withSubCategories(): array
    {
        $cats = $this->activeOrdered();
        $stmt = $this->db->prepare(
            'SELECT * FROM sub_categories WHERE category_id = ? AND status = 1 ORDER BY display_order ASC'
        );
        foreach ($cats as &$cat) {
            $stmt->execute([(int) $cat['id']]);
            $cat['sub_categories'] = $stmt->fetchAll();
        }
        return $cats;
    }

    /**
     * Admin-managed checkout extras (e.g. drinks + dessert).
     *
     * @return array{enabled:bool,title:string,subtitle:string,categories:list<array{id:int,name:string,slug:string,label:string,image:?string}>}
     */
    public static function checkoutUpsell(): array
    {
        try {
            return self::buildCheckoutUpsell();
        } catch (\Throwable) {
            return [
                'enabled'    => false,
                'title'      => 'Want drinks or dessert too?',
                'subtitle'   => 'Add a little extra before you place your order.',
                'categories' => [],
            ];
        }
    }

    /**
     * @return array{enabled:bool,title:string,subtitle:string,categories:list<array{id:int,name:string,slug:string,label:string,image:?string}>}
     */
    private static function buildCheckoutUpsell(): array
    {
        $enabled = ((string) Helpers::setting('checkout_upsell_enabled', '1')) === '1';
        $title = (string) Helpers::setting('checkout_upsell_title', 'Want drinks or dessert too?');
        $subtitle = (string) Helpers::setting(
            'checkout_upsell_subtitle',
            'Add a little extra before you place your order.'
        );

        $slots = [
            [
                'id'    => (int) Helpers::setting('checkout_upsell_cat_1', 0),
                'label' => trim((string) Helpers::setting('checkout_upsell_label_1', '')),
            ],
            [
                'id'    => (int) Helpers::setting('checkout_upsell_cat_2', 0),
                'label' => trim((string) Helpers::setting('checkout_upsell_label_2', '')),
            ],
        ];

        $model = new self();
        $categories = [];
        foreach ($slots as $slot) {
            $id = (int) $slot['id'];
            if ($id <= 0) {
                continue;
            }
            $cat = $model->find($id);
            if (!$cat || !(int) ($cat['status'] ?? 0)) {
                continue;
            }
            $categories[] = [
                'id'    => (int) $cat['id'],
                'name'  => (string) $cat['name'],
                'slug'  => (string) $cat['slug'],
                'label' => $slot['label'] !== '' ? $slot['label'] : (string) $cat['name'],
                'image' => !empty($cat['image']) ? Helpers::upload((string) $cat['image']) : null,
            ];
        }

        if ($enabled && $categories === []) {
            $all = $model->activeOrdered();
            $drink = null;
            $dessert = null;
            foreach ($all as $cat) {
                $n = strtolower((string) $cat['name']);
                if (!$drink && preg_match('/shake|drink|milk|beverage|juice/', $n)) {
                    $drink = $cat;
                    continue;
                }
                if (!$dessert && preg_match('/dessert|cake|pudding|cookie|waffle|pancake|cheesecake|dough/', $n)) {
                    $dessert = $cat;
                }
            }
            foreach ([['cat' => $drink, 'label' => 'Add drinks'], ['cat' => $dessert, 'label' => 'Add dessert']] as $auto) {
                if (!$auto['cat']) {
                    continue;
                }
                $cat = $auto['cat'];
                $categories[] = [
                    'id'    => (int) $cat['id'],
                    'name'  => (string) $cat['name'],
                    'slug'  => (string) $cat['slug'],
                    'label' => $auto['label'],
                    'image' => !empty($cat['image']) ? Helpers::upload((string) $cat['image']) : null,
                ];
            }
        }

        return [
            'enabled'    => $enabled && $categories !== [],
            'title'      => $title !== '' ? $title : 'Want to add a little extra?',
            'subtitle'   => $subtitle,
            'categories' => $categories,
        ];
    }
}
