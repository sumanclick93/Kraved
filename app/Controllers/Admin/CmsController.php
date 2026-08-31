<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\SiteSection;

final class CmsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $model = new SiteSection();
        $sections = [];
        try {
            $sections = $model->all('display_order ASC, id ASC');
        } catch (\Throwable) {
            $sections = [];
        }

        $this->view('Admin/cms/index', [
            'title' => 'Homepage CMS',
            'sections' => $sections,
            'defaults' => SiteSection::defaults(),
            'tableMissing' => $sections === [],
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ], 'Admin/layouts/main');
    }

    public function edit(string $key): void
    {
        AuthMiddleware::requireAdmin();
        $model = new SiteSection();
        $row = null;
        try {
            $row = $model->findByKey($key);
        } catch (\Throwable) {
            $row = null;
        }

        if (!$row) {
            Session::flash('error', 'Section not found. Run the CMS database migration first.');
            $this->redirect('/admin/cms');
        }

        $content = json_decode((string) $row['content_json'], true);
        if (!is_array($content)) {
            $content = SiteSection::defaults()[$key]['content'] ?? [];
        }

        $this->view('Admin/cms/edit', [
            'title' => 'Edit: ' . $row['label'],
            'section' => $row,
            'content' => $content,
            'success' => Session::flash('success'),
        ], 'Admin/layouts/main');
    }

    public function update(string $key): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();

        $model = new SiteSection();
        $row = $model->findByKey($key);
        if (!$row) {
            Session::flash('error', 'Section not found.');
            $this->redirect('/admin/cms');
        }

        $existing = json_decode((string) $row['content_json'], true);
        if (!is_array($existing)) {
            $existing = [];
        }

        $content = $this->payloadFromPost($key, $existing);
        $visible = isset($_POST['is_visible']);
        $model->updateContent($key, $content, $visible);

        Session::flash('success', $row['label'] . ' saved.');
        $this->redirect('/admin/cms/' . rawurlencode($key) . '/edit');
    }

    public function toggle(string $key): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();
        $model = new SiteSection();
        $row = $model->findByKey($key);
        if ($row) {
            $model->setVisible($key, !((int) $row['is_visible'] === 1));
            Session::flash('success', 'Visibility updated.');
        }
        $this->redirect('/admin/cms');
    }

    private function payloadFromPost(string $key, array $existing): array
    {
        return match ($key) {
            'nav' => $this->parseLinks('links'),
            'hero' => $this->parseHero($existing),
            'features' => $this->parseFeatures(),
            'popular' => [
                'title' => trim((string) ($_POST['title'] ?? '')),
                'view_all_label' => trim((string) ($_POST['view_all_label'] ?? 'View All')),
                'view_all_href' => trim((string) ($_POST['view_all_href'] ?? '#menu')),
                'limit' => max(1, min(24, (int) ($_POST['limit'] ?? 8))),
            ],
            'how_it_works' => $this->parseHow(),
            'about' => [
                'eyebrow' => trim((string) ($_POST['eyebrow'] ?? '')),
                'title' => trim((string) ($_POST['title'] ?? '')),
                'copy' => trim((string) ($_POST['copy'] ?? '')),
                'copy_extra' => trim((string) ($_POST['copy_extra'] ?? '')),
                'story' => trim((string) ($_POST['story'] ?? '')),
                'quote' => trim((string) ($_POST['quote'] ?? '')),
                'mission' => trim((string) ($_POST['mission'] ?? '')),
                'cta_label' => trim((string) ($_POST['cta_label'] ?? '')),
                'cta_href' => trim((string) ($_POST['cta_href'] ?? '#menu')),
                'image' => $this->handleUpload('about_image', $existing['image'] ?? 'images/hero-dessert.png'),
            ],
            'trust_bar' => $this->parseChips(),
            'hygiene' => [
                'eyebrow' => trim((string) ($_POST['eyebrow'] ?? 'FOOD SAFETY & HYGIENE')),
                'title' => trim((string) ($_POST['title'] ?? '⭐ 5-Star Food Hygiene Rated')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'rating' => trim((string) ($_POST['rating'] ?? '5')),
                'rating_label' => trim((string) ($_POST['rating_label'] ?? 'VERY GOOD')),
                'badge_image' => $this->handleUpload('badge_image', $existing['badge_image'] ?? 'images/food-hygiene-rating-5.svg'),
            ],
            'menu' => [
                'title' => trim((string) ($_POST['title'] ?? '')),
                'lead' => trim((string) ($_POST['lead'] ?? '')),
            ],
            'footer' => $this->parseFooter(),
            default => $existing,
        };
    }

    private function parseHero(array $existing): array
    {
        $image = $existing['image'] ?? 'images/hero-dessert.png';
        $uploaded = $this->handleUpload('hero_image', $image);
        return [
            'headline_line1' => trim((string) ($_POST['headline_line1'] ?? '')),
            'headline_line2' => trim((string) ($_POST['headline_line2'] ?? '')),
            'subhead' => trim((string) ($_POST['subhead'] ?? '')),
            'image' => $uploaded,
            'image_alt' => trim((string) ($_POST['image_alt'] ?? '')),
            'badge_html' => trim((string) ($_POST['badge_html'] ?? '')),
            'default_location' => trim((string) ($_POST['default_location'] ?? '')),
            'outlets_text' => trim((string) ($_POST['outlets_text'] ?? '')),
            'delivery_title' => trim((string) ($_POST['delivery_title'] ?? '')),
            'delivery_subtitle' => trim((string) ($_POST['delivery_subtitle'] ?? '')),
            'takeaway_title' => trim((string) ($_POST['takeaway_title'] ?? '')),
            'takeaway_subtitle' => trim((string) ($_POST['takeaway_subtitle'] ?? '')),
        ];
    }

    private function parseLinks(string $field): array
    {
        $labels = $_POST[$field . '_label'] ?? [];
        $hrefs = $_POST[$field . '_href'] ?? [];
        $items = [];
        if (!is_array($labels)) {
            return [$field => []];
        }
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }
            $items[] = [
                'label' => $label,
                'href' => trim((string) ($hrefs[$i] ?? '#')),
            ];
        }
        return [$field => $items];
    }

    private function parseFeatures(): array
    {
        $titles = $_POST['item_title'] ?? [];
        $texts = $_POST['item_text'] ?? [];
        $icons = $_POST['item_icon'] ?? [];
        $items = [];
        if (!is_array($titles)) {
            return ['items' => []];
        }
        foreach ($titles as $i => $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $items[] = [
                'title' => $title,
                'text' => trim((string) ($texts[$i] ?? '')),
                'icon' => trim((string) ($icons[$i] ?? 'pin')) ?: 'pin',
            ];
        }
        return ['items' => $items];
    }

    private function parseHow(): array
    {
        $titles = $_POST['step_title'] ?? [];
        $icons = $_POST['step_icon'] ?? [];
        $steps = [];
        if (is_array($titles)) {
            foreach ($titles as $i => $title) {
                $title = trim((string) $title);
                if ($title === '') {
                    continue;
                }
                $steps[] = [
                    'title' => $title,
                    'icon' => trim((string) ($icons[$i] ?? 'pin')) ?: 'pin',
                ];
            }
        }
        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'steps' => $steps,
        ];
    }

    private function parseChips(): array
    {
        $raw = (string) ($_POST['chips'] ?? '');
        $chips = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
        return ['chips' => $chips];
    }

    private function parseFooter(): array
    {
        $socialLabels = $_POST['social_label'] ?? [];
        $socialUrls = $_POST['social_url'] ?? [];
        $socialArias = $_POST['social_aria'] ?? [];
        $social = [];
        if (is_array($socialLabels)) {
            foreach ($socialLabels as $i => $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $social[] = [
                    'label' => $label,
                    'url' => trim((string) ($socialUrls[$i] ?? '#')),
                    'aria' => trim((string) ($socialArias[$i] ?? $label)),
                ];
            }
        }

        return [
            'mission' => trim((string) ($_POST['mission'] ?? '')),
            'tagline' => trim((string) ($_POST['tagline'] ?? '')),
            'newsletter_title' => trim((string) ($_POST['newsletter_title'] ?? '')),
            'newsletter_text' => trim((string) ($_POST['newsletter_text'] ?? '')),
            'copyright' => trim((string) ($_POST['copyright'] ?? '')),
            'made_in' => trim((string) ($_POST['made_in'] ?? '')),
            'quick_links' => $this->parseLinks('quick_links')['quick_links'] ?? [],
            'info_links' => $this->parseLinks('info_links')['info_links'] ?? [],
            'social' => $social,
        ];
    }

    private function handleUpload(string $field, string $current): string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return $current;
        }
        $ext = strtolower(pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return $current;
        }
        $dir = dirname(__DIR__, 3) . '/public/uploads/cms/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'cms_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename)) {
            return 'cms/' . $filename;
        }
        return $current;
    }
}
