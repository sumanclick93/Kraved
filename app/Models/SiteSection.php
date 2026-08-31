<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Helpers;

final class SiteSection extends Model
{
    protected string $table = 'site_sections';

    private static ?array $cache = null;

    /** @return array<string, array{label:string,content:array,is_visible:bool,display_order:int}> */
    public static function map(bool $visibleOnly = false): array
    {
        if (self::$cache === null) {
            try {
                $rows = (new self())->all('display_order ASC, id ASC');
                $map = [];
                foreach ($rows as $row) {
                    $content = json_decode((string) $row['content_json'], true);
                    if (!is_array($content)) {
                        $content = self::defaults()[$row['section_key']]['content'] ?? [];
                    }
                    $map[$row['section_key']] = [
                        'id' => (int) $row['id'],
                        'label' => $row['label'],
                        'content' => $content,
                        'is_visible' => (int) $row['is_visible'] === 1,
                        'display_order' => (int) $row['display_order'],
                    ];
                }
                foreach (self::defaults() as $dKey => $dVal) {
                    if (!isset($map[$dKey])) {
                        $map[$dKey] = [
                            'id' => 0,
                            'label' => $dVal['label'],
                            'content' => $dVal['content'],
                            'is_visible' => true,
                            'display_order' => $dVal['display_order'] ?? 65,
                        ];
                    }
                }
                uasort($map, static fn ($a, $b) => $a['display_order'] <=> $b['display_order']);
                self::$cache = $map;
            } catch (\Throwable) {
                self::$cache = [];
            }
        }

        if ($visibleOnly) {
            return array_filter(self::$cache, static fn (array $s): bool => $s['is_visible']);
        }

        return self::$cache;
    }

    public static function content(string $key): array
    {
        $map = self::map();
        if (isset($map[$key])) {
            return $map[$key]['content'];
        }
        return self::defaults()[$key]['content'] ?? [];
    }

    public static function isVisible(string $key): bool
    {
        $map = self::map();
        if (isset($map[$key])) {
            return $map[$key]['is_visible'];
        }
        return true;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM site_sections WHERE section_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateContent(string $key, array $content, ?bool $visible = null): void
    {
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = $this->findByKey($key);
        if ($row) {
            if ($visible === null) {
                $stmt = $this->db->prepare(
                    'UPDATE site_sections SET content_json = ?, updated_at = NOW() WHERE section_key = ?'
                );
                $stmt->execute([$json, $key]);
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE site_sections SET content_json = ?, is_visible = ?, updated_at = NOW() WHERE section_key = ?'
                );
                $stmt->execute([$json, $visible ? 1 : 0, $key]);
            }
        } else {
            $defaults = self::defaults()[$key] ?? ['label' => ucfirst($key), 'display_order' => 65];
            $label = $defaults['label'];
            $order = $defaults['display_order'] ?? 65;
            $stmt = $this->db->prepare(
                'INSERT INTO site_sections (section_key, label, content_json, is_visible, display_order, updated_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$key, $label, $json, $visible ? 1 : 0, $order]);
        }
        self::clearCache();
    }

    public function setVisible(string $key, bool $visible): void
    {
        $stmt = $this->db->prepare(
            'UPDATE site_sections SET is_visible = ?, updated_at = NOW() WHERE section_key = ?'
        );
        $stmt->execute([$visible ? 1 : 0, $key]);
        self::clearCache();
    }

    public static function mediaUrl(?string $path, string $fallback = 'images/hero-dessert.png'): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return Helpers::asset($fallback);
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'images/')) {
            return Helpers::asset($path);
        }
        return Helpers::upload($path);
    }

    public static function resolveHref(string $href): string
    {
        $href = trim($href);
        if ($href === '' || $href === '#') {
            return '#';
        }
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://') || str_starts_with($href, 'mailto:')) {
            return $href;
        }
        if (str_starts_with($href, '#')) {
            return Helpers::baseUrl() . $href;
        }
        if ($href === '/' || $href === '') {
            return Helpers::baseUrl();
        }
        if (str_starts_with($href, '/#')) {
            return Helpers::baseUrl() . substr($href, 1);
        }
        return Helpers::baseUrl(ltrim($href, '/'));
    }

    /** @return array<string, array{label:string,content:array}> */
    public static function defaults(): array
    {
        return [
            'nav' => [
                'label' => 'Header Navigation',
                'content' => [
                    'links' => [
                        ['label' => 'Home', 'href' => '/'],
                        ['label' => 'Menu', 'href' => '/#menu'],
                        ['label' => 'Best Sellers', 'href' => '/#popular'],
                        ['label' => 'Offers', 'href' => '/#offers'],
                        ['label' => 'About Us', 'href' => '/#about'],
                        ['label' => 'Contact', 'href' => '/#contact'],
                    ],
                ],
            ],
            'hero' => [
                'label' => 'Hero Section',
                'content' => [
                    'headline_line1' => 'Desserts That',
                    'headline_line2' => 'Crave You Back.',
                    'subhead' => 'Taste the sweetness in every bite. Handcrafted in the heart of Colne.',
                    'image' => 'images/hero-dessert.png',
                    'image_alt' => 'Chocolate brownie stack with ice cream and dripping sauce',
                    'badge_html' => "Based in<br>Colne",
                    'default_location' => 'Colne, UK',
                    'outlets_text' => 'Collection & delivery from our Colne kitchen',
                    'delivery_title' => 'Order Delivery',
                    'delivery_subtitle' => 'From our Colne kitchen',
                    'takeaway_title' => 'Collection',
                    'takeaway_subtitle' => 'Collect in store',
                ],
            ],
            'features' => [
                'label' => 'Feature Cards',
                'content' => [
                    'items' => [
                        ['title' => 'Order Online', 'text' => 'Browse the menu and order at kraveddesserts.com.', 'icon' => 'phone'],
                        ['title' => 'Delivery', 'text' => 'Fresh desserts delivered from our Colne kitchen.', 'icon' => 'truck'],
                        ['title' => 'Collection', 'text' => 'Collect from Unit 2, Old Biscuit Factory, Dockray Street.', 'icon' => 'bag'],
                        ['title' => 'Open Late', 'text' => 'Mon–Fri 5pm to 11pm · Sat & Sun 2pm to 11pm.', 'icon' => 'store'],
                    ],
                ],
            ],
            'popular' => [
                'label' => 'Popular Right Now',
                'content' => [
                    'title' => 'Popular Right Now',
                    'view_all_label' => 'View All',
                    'view_all_href' => '#menu',
                    'limit' => 8,
                ],
            ],
            'how_it_works' => [
                'label' => 'How It Works',
                'content' => [
                    'title' => 'Order Online, Delivery or Collection',
                    'steps' => [
                        ['title' => 'Order Online', 'icon' => 'phone'],
                        ['title' => 'Freshly Prepared With Love', 'icon' => 'chef'],
                        ['title' => 'Delivery or Collection', 'icon' => 'bag'],
                        ['title' => 'Taste the Sweetness in Every Bite', 'icon' => 'store'],
                    ],
                ],
            ],
            'about' => [
                'label' => 'About Banner',
                'content' => [
                    'eyebrow' => 'About Us',
                    'title' => 'Feed the Kraving',
                    'copy' => 'Kraved started the way most good things do, with a craving that just wouldn\'t quit.',
                    'copy_extra' => 'Based in the heart of Colne, built on the belief that a little indulgence, done properly, can turn an ordinary day into a good one.',
                    'story' => 'Every single treat we create is prepared fresh using premium quality ingredients, crafting authentic desserts that bring joy to your everyday moments.',
                    'quote' => 'Feed the Kraving',
                    'mission' => 'Our mission is simple, to make every moment sweet and memorable.',
                    'cta_label' => 'Order Online',
                    'cta_href' => '#menu',
                    'image' => 'images/hero-dessert.png',
                ],
            ],
            'trust_bar' => [
                'label' => 'Trust / Offers Bar',
                'content' => [
                    'chips' => [
                        'Rich Chocolate Bliss',
                        'Made with Real Goodness',
                        'Sweets That Make Life Sweeter',
                        'Taste the Sweetness in Every Bite',
                        'Handcrafted in Colne',
                    ],
                ],
            ],
            'hygiene' => [
                'label' => 'Food Hygiene Rating',
                'display_order' => 65,
                'content' => [
                    'eyebrow' => 'FOOD SAFETY & HYGIENE',
                    'title' => '⭐ 5-Star Food Hygiene Rated',
                    'description' => 'We’re proud to have achieved a 5-Star Food Hygiene Rating, the highest rating available. It reflects our commitment to maintaining excellent standards of food safety, cleanliness and hygiene, so you can enjoy your Kraved favourites with confidence.',
                    'rating' => '5',
                    'rating_label' => 'VERY GOOD',
                    'badge_image' => 'images/food-hygiene-rating-5.svg',
                ],
            ],
            'menu' => [
                'label' => 'Full Menu Intro',
                'content' => [
                    'title' => 'Full Menu',
                    'lead' => 'Milkshakes, cookie dough, waffles, pancakes and puddings — handcrafted in Colne.',
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'content' => [
                    'mission' => 'Our mission is simple, to make every moment sweet and memorable.',
                    'tagline' => 'Taste the sweetness in every bite.',
                    'newsletter_title' => 'Stay Updated',
                    'newsletter_text' => 'Subscribe for exclusive offers & updates',
                    'copyright' => 'Kraved Desserts. All rights reserved.',
                    'made_in' => 'Made with ♥ in Colne',
                    'quick_links' => [
                        ['label' => 'Menu', 'href' => '/#menu'],
                        ['label' => 'Best Sellers', 'href' => '/#popular'],
                        ['label' => 'Offers', 'href' => '/#offers'],
                        ['label' => 'About Us', 'href' => '/#about'],
                        ['label' => 'Contact', 'href' => '/#contact'],
                    ],
                    'info_links' => [
                        ['label' => 'Delivery Info', 'href' => '#fulfillment'],
                        ['label' => 'FAQs', 'href' => '/#how-it-works'],
                        ['label' => 'Terms & Conditions', 'href' => '#'],
                        ['label' => 'Privacy Policy', 'href' => '#'],
                    ],
                    'social' => [
                        ['label' => 'IG', 'url' => '#', 'aria' => 'Instagram'],
                        ['label' => 'FB', 'url' => '#', 'aria' => 'Facebook'],
                        ['label' => 'TT', 'url' => '#', 'aria' => 'TikTok'],
                        ['label' => 'X', 'url' => '#', 'aria' => 'X'],
                    ],
                ],
            ],
        ];
    }
}
