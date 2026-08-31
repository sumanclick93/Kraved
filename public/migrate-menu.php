<?php

declare(strict_types=1);

/**
 * Replaces the catalog with the printed Kraved Desserts menu.
 * Open: /public/migrate-menu.php
 * Click Apply, then DELETE this file.
 */

$configFile = dirname(__DIR__) . '/config/database.local.php';
$fallback = dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';
$log = [];
$counts = ['categories' => 0, 'products' => 0, 'variants' => 0];

function kraved_slug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

try {
    $cfg = require (is_file($configFile) ? $configFile : $fallback);
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $cfg['host'],
        $cfg['port'] ?? '3306',
        $cfg['dbname']
    );
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $shouldRun = $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($shouldRun) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        if ($pdo->query("SHOW TABLES LIKE 'order_items'")->fetchColumn()) {
            $pdo->exec('UPDATE order_items SET product_id = NULL');
        }
        foreach ([
            'product_addon_groups',
            'box_deal_products',
            'product_images',
            'product_variants',
            'products',
            'sub_categories',
            'addons',
            'addon_groups',
            'categories',
        ] as $table) {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
            if (!$exists) {
                continue;
            }
            $pdo->exec("DELETE FROM `{$table}`");
            $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            $log[] = "cleared {$table}";
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $insCat = $pdo->prepare(
            'INSERT INTO categories (name, slug, display_order, status) VALUES (?,?,?,1)'
        );
        $insSub = $pdo->prepare(
            'INSERT INTO sub_categories (category_id, name, slug, display_order, status) VALUES (?,?,?,?,1)'
        );
        $insProd = $pdo->prepare(
            'INSERT INTO products
             (category_id, sub_category_id, title, slug, short_description, full_description,
              base_price, stock_qty, weight_label, is_featured, is_box_deal, box_max_items, status, display_order)
             VALUES (?,?,?,?,?,?,?,100,?,?,0,NULL,1,?)'
        );
        $hasVariants = (bool) $pdo->query("SHOW TABLES LIKE 'product_variants'")->fetchColumn();
        $insVar = $hasVariants
            ? $pdo->prepare(
                'INSERT INTO product_variants (product_id, label, price, stock_qty, status, display_order)
                 VALUES (?,?,?,100,1,?)'
            )
            : null;
        $insGroup = $pdo->prepare(
            'INSERT INTO addon_groups (title, min_selection, max_selection, is_required, display_order, status)
             VALUES (?,?,?,?,?,1)'
        );
        $insAddon = $pdo->prepare(
            'INSERT INTO addons (group_id, name, price, display_order, status) VALUES (?,?,0.00,?,1)'
        );
        $linkGroup = $pdo->prepare(
            'INSERT INTO product_addon_groups (product_id, group_id) VALUES (?,?)'
        );

        $addProduct = static function (
            PDOStatement $insProd,
            PDO $pdo,
            int $catId,
            ?int $subId,
            string $title,
            string $slug,
            string $short,
            string $full,
            float $price,
            ?string $weight,
            int $featured,
            int $order
        ): int {
            $insProd->execute([
                $catId, $subId, $title, $slug, $short, $full, $price, $weight, $featured, $order,
            ]);
            return (int) $pdo->lastInsertId();
        };

        $categories = [
            'Milkshakes',
            'Cookie Dough',
            'Waffles',
            'Classic Shakes',
            'Mini Pancakes',
            'Cups',
            'Puddings',
            'Matilda Cake',
            'Cheesecakes',
        ];
        $catIds = [];
        foreach ($categories as $i => $name) {
            $insCat->execute([$name, kraved_slug($name), $i + 1]);
            $catIds[$name] = (int) $pdo->lastInsertId();
        }

        $insSub->execute([$catIds['Milkshakes'], 'Kraved Shakes', 'kraved-shakes', 1]);
        $kravedSub = (int) $pdo->lastInsertId();
        $insSub->execute([$catIds['Milkshakes'], 'Create Your Own', 'create-your-own-shake', 2]);
        $shakeCustomSub = (int) $pdo->lastInsertId();
        $insSub->execute([$catIds['Cookie Dough'], 'Create Your Own', 'create-your-own-cookie-dough', 1]);
        $doughCustomSub = (int) $pdo->lastInsertId();
        $insSub->execute([$catIds['Waffles'], 'Create Your Own', 'create-your-own-waffle', 1]);
        $waffleCustomSub = (int) $pdo->lastInsertId();

        $shakes = [
            ['Millionaire', 6.99, 'Fresh strawberries, Ferrero Rocher, chocolate sauce & strawberry sauce blended with vanilla ice cream & topped with cream.', 1],
            ['Ferrero Frenzy', 6.99, 'Ferrero Rocher, Kinder Bueno, chocolate sauce & hazelnut sauce with vanilla ice cream, topped with cream and nuts.', 1],
            ['Something About Lotus', 6.49, 'Lotus biscuit, Lotus Biscoff sauce with vanilla ice cream, topped with cream.', 0],
            ['Dodger Dream', 6.49, 'Jammie Dodger, strawberry sauce with vanilla ice cream, topped with cream.', 0],
            ['Strawberry Heaven', 6.49, 'Fresh strawberries, strawberry syrup, vanilla ice cream, topped with cream.', 1],
            ['The Cookie Monster', 6.99, 'Oreo, Maryland cookies, chocolate sauce, vanilla ice cream topped with cream.', 1],
            ['Nutty Professor', 6.99, 'Snickers, chocolate Nutella sauce, vanilla ice cream topped with cream and nuts.', 0],
            ['Pistachio Paradise', 6.99, 'Crushed pistachios, pistachio sauce, vanilla ice cream, topped with cream.', 1],
        ];
        foreach ($shakes as $i => $row) {
            [$title, $price, $desc, $feat] = $row;
            $addProduct(
                $insProd, $pdo, $catIds['Milkshakes'], $kravedSub, $title, kraved_slug($title),
                $desc, $desc . ' A Kraved signature milkshake.', (float) $price, 'Kraved Shake', (int) $feat, $i + 1
            );
        }

        $loaded = [
            ['Chocoberry', 'Strawberries & chocolate sauce.'],
            ['Mr Brown', 'Brownies & milk chocolate sauce.'],
            ['S\'mores', 'Milk chocolate sauce & marshmallows.'],
            ['Kinder Surprise', 'Kinder Bueno, chocolate sauce & white chocolate.'],
            ['Kunafa Craze', 'Pistachio sauce, chocolate sauce & kunafa mix.'],
            ['Nutty One', 'Chopped Ferrero, nuts & Nutella sauce.'],
            ['Triple Chocolate', 'Milk chocolate, white chocolate & hazelnut sauce.'],
            ['Biscoff', 'Lotus crumbs, Lotus spread & chocolate sauce.'],
            ['Cookie Crumble', 'Crushed Oreos & chocolate sauce.'],
            ['Rainbow', 'Smarties and chocolate sauce.'],
        ];

        foreach ($loaded as $i => $row) {
            [$title, $desc] = $row;
            $feat = in_array($title, ['Kunafa Craze', 'Kinder Surprise', 'Biscoff'], true) ? 1 : 0;
            $addProduct(
                $insProd, $pdo, $catIds['Cookie Dough'], null, $title, 'cookie-dough-' . kraved_slug($title),
                $desc,
                'Milk chocolate cookie dough with vanilla ice cream. ' . $desc,
                6.99, 'Cookie Dough', $feat, $i + 1
            );
        }

        foreach ($loaded as $i => $row) {
            [$title, $desc] = $row;
            $feat = $title === 'Kunafa Craze' ? 1 : 0;
            $addProduct(
                $insProd, $pdo, $catIds['Waffles'], null, $title, 'waffle-' . kraved_slug($title),
                $desc,
                'Fresh Belgian waffle with vanilla ice cream. ' . $desc,
                7.49, 'Belgian Waffle', $feat, $i + 1
            );
        }

        $classic = [
            'Vanilla Shake', 'Nutella Shake', 'Flake Shake', 'Bubblegum Shake',
            "Terry's Orange Shake", 'Kinder Bueno Dark Shake', 'Kinder Bueno White Shake',
            'Ferrero Rocher Shake', 'Aero Shake', 'Oreo Shake', 'Milky Bar Shake',
        ];
        foreach ($classic as $i => $title) {
            $id = $addProduct(
                $insProd, $pdo, $catIds['Classic Shakes'], null, $title, kraved_slug($title),
                'Regular £4.99 · Large £5.49',
                $title . ' — choose Regular or Large.',
                4.99, 'Regular / Large', 0, $i + 1
            );
            if ($insVar) {
                $insVar->execute([$id, 'Regular', 4.99, 1]);
                $insVar->execute([$id, 'Large', 5.49, 2]);
            }
        }

        $pancakes = [
            'Strawberry & Nutella',
            'Banana & Nutella',
            'Ferrero & Chocolate Sauce',
            'Lotus & Lotus Sauce',
            'Oreo & Chocolate Sauce',
            'Pistachio & White Chocolate',
            'Marshmallow & Chocolate Sauce',
            'Kinder Bueno & Chocolate Sauce',
        ];
        foreach ($pancakes as $i => $title) {
            $feat = $title === 'Strawberry & Nutella' ? 1 : 0;
            $addProduct(
                $insProd, $pdo, $catIds['Mini Pancakes'], null, $title, 'mini-pancakes-' . kraved_slug($title),
                $title . ' mini pancakes.',
                'A stack of mini pancakes with ' . strtolower($title) . '.',
                6.99, 'Mini Pancakes', $feat, $i + 1
            );
        }

        $cups = [
            ['Strawberry & Chocolate', 6.49, 0],
            ['Brownies & Chocolate', 6.49, 0],
            ['Waffle & Chocolate', 6.49, 0],
            ['Strawberry, Kunafa & Chocolate', 7.49, 1],
        ];
        foreach ($cups as $i => $row) {
            [$title, $price, $feat] = $row;
            $addProduct(
                $insProd, $pdo, $catIds['Cups'], null, $title, 'cup-' . kraved_slug($title),
                'Dessert cup with ' . strtolower($title) . '.',
                'Loaded dessert cup: ' . $title . '.',
                (float) $price, 'Cup', (int) $feat, $i + 1
            );
        }

        $puddings = [
            'Jam Coconut Sponge Pudding',
            'Sticky Toffee',
            'Molten Cake',
            'Jam Roly Poly',
            'Cornflake Tart',
        ];
        $puddingIds = [];
        foreach ($puddings as $i => $title) {
            $feat = $title === 'Molten Cake' ? 1 : 0;
            $puddingIds[] = $addProduct(
                $insProd, $pdo, $catIds['Puddings'], null, $title, kraved_slug($title),
                'Served with custard or 1 scoop of vanilla ice cream.',
                $title . ' — all puddings served with custard or 1 scoop of vanilla ice cream.',
                5.99, 'Pudding', $feat, $i + 1
            );
        }

        $addProduct(
            $insProd, $pdo, $catIds['Matilda Cake'], null, 'Matilda Cake', 'matilda-cake',
            'Rich chocolate Matilda cake.',
            'A generous slice of rich chocolate Matilda cake.',
            6.49, 'Slice', 1, 1
        );

        $addProduct(
            $insProd, $pdo, $catIds['Cheesecakes'], null, 'Cheesecake of the Day', 'cheesecake-of-the-day',
            'Availability depends on stock. Call 01282 943888 for today’s flavours.',
            'Cheesecake flavours change — please call to see what we have today.',
            5.99, 'Ask in store', 0, 1
        );

        $toppings = [
            'Fresh strawberries', 'Ferrero Rocher', 'Kinder Bueno', 'Lotus biscuit',
            'Jammie Dodger', 'Oreo', 'Maryland cookies', 'Snickers', 'Crushed pistachios',
            'Brownies', 'Marshmallows', 'Kunafa mix', 'Nuts', 'Smarties', 'Banana', 'Flake',
        ];
        $sauces = [
            'Chocolate sauce', 'Strawberry sauce', 'Hazelnut sauce', 'Lotus Biscoff sauce',
            'Nutella sauce', 'Pistachio sauce', 'White chocolate', 'Milk chocolate sauce',
        ];

        $insGroup->execute(['Pick 2 toppings', 2, 2, 1, 1]);
        $toppingGroup = (int) $pdo->lastInsertId();
        foreach ($toppings as $i => $name) {
            $insAddon->execute([$toppingGroup, $name, $i + 1]);
        }

        $insGroup->execute(['Pick 2 sauces', 2, 2, 1, 2]);
        $sauceGroup = (int) $pdo->lastInsertId();
        foreach ($sauces as $i => $name) {
            $insAddon->execute([$sauceGroup, $name, $i + 1]);
        }

        $insGroup->execute(['Served with', 1, 1, 1, 3]);
        $serveGroup = (int) $pdo->lastInsertId();
        $insAddon->execute([$serveGroup, 'Custard', 1]);
        $insAddon->execute([$serveGroup, '1 scoop vanilla ice cream', 2]);

        $customShake = $addProduct(
            $insProd, $pdo, $catIds['Milkshakes'], $shakeCustomSub,
            'Create Your Own Milkshake', 'create-your-own-milkshake',
            'Pick any two toppings and two sauces of your choice.',
            'Build your own Kraved milkshake — choose two toppings and two sauces, blended with vanilla ice cream and topped with cream.',
            6.99, 'Custom Shake', 0, 20
        );
        $customDough = $addProduct(
            $insProd, $pdo, $catIds['Cookie Dough'], $doughCustomSub,
            'Create Your Own Cookie Dough', 'create-your-own-cookie-dough',
            'Pick any two toppings and two sauces. Extra £1.00.',
            'Milk chocolate cookie dough with vanilla ice cream — pick two toppings and two sauces. Custom builds include a £1 extra.',
            7.99, 'Custom + £1', 0, 20
        );
        $customWaffle = $addProduct(
            $insProd, $pdo, $catIds['Waffles'], $waffleCustomSub,
            'Create Your Own Waffle', 'create-your-own-waffle',
            'Pick any two toppings and two sauces. Extra £1.00.',
            'Fresh Belgian waffle with vanilla ice cream — pick two toppings and two sauces. Custom builds include a £1 extra.',
            8.49, 'Custom + £1', 0, 20
        );

        foreach ([$customShake, $customDough, $customWaffle] as $pid) {
            $linkGroup->execute([$pid, $toppingGroup]);
            $linkGroup->execute([$pid, $sauceGroup]);
        }
        foreach ($puddingIds as $pid) {
            $linkGroup->execute([$pid, $serveGroup]);
        }

        $pdo->exec('DELETE FROM delivery_zones');
        $pdo->exec("ALTER TABLE delivery_zones AUTO_INCREMENT = 1");
        $zone = $pdo->prepare(
            'INSERT INTO delivery_zones (postcode_prefix, delivery_fee, min_order_amount, estimated_mins, status)
             VALUES (?,?,?,?,1)'
        );
        foreach ([
            ['BB8', 2.49, 12.00, 30],
            ['BB9', 2.99, 12.00, 35],
            ['BB10', 3.49, 15.00, 40],
            ['BB11', 3.49, 15.00, 40],
            ['BB12', 3.99, 15.00, 45],
        ] as $z) {
            $zone->execute($z);
        }
        $log[] = 'delivery zones set to Colne / East Lancashire';

        $menuLead = 'Milkshakes, cookie dough, waffles, pancakes and puddings — handcrafted in Colne.';
        $stmt = $pdo->query("SELECT content_json FROM site_sections WHERE section_key = 'menu'");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($row) {
            $content = json_decode((string) $row['content_json'], true);
            if (!is_array($content)) {
                $content = [];
            }
            $content['title'] = 'Full Menu';
            $content['lead'] = $menuLead;
            $upd = $pdo->prepare('UPDATE site_sections SET content_json = ?, updated_at = NOW() WHERE section_key = ?');
            $upd->execute([json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'menu']);
            $log[] = 'updated menu intro copy';
        }

        $counts['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $counts['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $counts['variants'] = (int) $pdo->query('SELECT COUNT(*) FROM product_variants')->fetchColumn();
        $success = 'Menu replaced with the Kraved Desserts catalog. Delete migrate-menu.php now.';
    } else {
        $counts['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $counts['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kraved Menu Update</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;background:#f6efe4;color:#2c1810}
    .card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(44,24,16,.08)}
    .ok{color:#2f9e5f}.err{color:#d45454}
    button,.btn{background:#3d2418;color:#fff;border:0;padding:10px 18px;border-radius:999px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
    pre{background:#efe4d4;padding:12px;border-radius:10px;overflow:auto;font-size:12px}
    .warn{background:#fff3cd;padding:12px 14px;border-radius:10px;margin:1rem 0}
  </style>
</head>
<body>
  <div class="card">
    <h1>Replace menu catalog</h1>
    <p>This removes the old cookie-shop items and loads the printed Kraved Desserts menu (milkshakes, cookie dough, waffles, classic shakes, pancakes, cups, puddings, Matilda cake, cheesecakes).</p>
    <div class="warn">Existing products in the database will be deleted. Past orders keep their item names.</div>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="ok"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <p>Current: <?= (int) $counts['categories'] ?> categories · <?= (int) $counts['products'] ?> products
      <?php if (!empty($counts['variants'])): ?> · <?= (int) $counts['variants'] ?> size variants<?php endif; ?></p>
    <?php if (!$success): ?>
      <form method="post"><button type="submit">Apply new menu</button></form>
    <?php else: ?>
      <a class="btn" href="/">View storefront</a>
    <?php endif; ?>
    <?php if ($log): ?><h3>Log</h3><pre><?= htmlspecialchars(implode("\n", $log)) ?></pre><?php endif; ?>
  </div>
</body>
</html>
