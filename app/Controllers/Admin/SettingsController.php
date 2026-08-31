<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Category;
use App\Models\Setting;

final class SettingsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $settings = [];
        try {
            $settings = Setting::map();
        } catch (\Throwable) {
            $settings = [];
        }

        $this->view('Admin/settings/index', [
            'title' => 'Store Settings',
            'settings' => $settings,
            'categories' => (new Category())->all('display_order ASC, name ASC'),
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ], 'Admin/layouts/main');
    }

    public function update(): void
    {
        AuthMiddleware::requireAdmin();
        Helpers::requireCsrf();

        $current = [];
        try {
            $current = Setting::map();
        } catch (\Throwable) {
            $current = [];
        }

        $pairs = [
            'store_name' => trim((string) ($_POST['store_name'] ?? '')),
            'store_tagline' => trim((string) ($_POST['store_tagline'] ?? '')),
            'currency_symbol' => trim((string) ($_POST['currency_symbol'] ?? '£')),
            'free_delivery_threshold' => (string) round((float) ($_POST['free_delivery_threshold'] ?? 25), 2),
            'collection_address' => trim((string) ($_POST['collection_address'] ?? '')),
            'asap_prep_mins' => (string) max(5, (int) ($_POST['asap_prep_mins'] ?? 35)),
            'order_prefix' => strtoupper(trim((string) ($_POST['order_prefix'] ?? 'KRV'))),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
            'contact_hours' => trim((string) ($_POST['contact_hours'] ?? '')),
            'checkout_upsell_enabled' => !empty($_POST['checkout_upsell_enabled']) ? '1' : '0',
            'checkout_upsell_title' => trim((string) ($_POST['checkout_upsell_title'] ?? '')),
            'checkout_upsell_subtitle' => trim((string) ($_POST['checkout_upsell_subtitle'] ?? '')),
            'checkout_upsell_cat_1' => (string) max(0, (int) ($_POST['checkout_upsell_cat_1'] ?? 0)),
            'checkout_upsell_cat_2' => (string) max(0, (int) ($_POST['checkout_upsell_cat_2'] ?? 0)),
            'checkout_upsell_label_1' => trim((string) ($_POST['checkout_upsell_label_1'] ?? '')),
            'checkout_upsell_label_2' => trim((string) ($_POST['checkout_upsell_label_2'] ?? '')),
            'stripe_enabled' => !empty($_POST['stripe_enabled']) ? '1' : '0',
            'stripe_mode' => (($_POST['stripe_mode'] ?? 'sandbox') === 'live') ? 'live' : 'sandbox',
            'stripe_sandbox_publishable_key' => $this->keptSecret(
                (string) ($_POST['stripe_sandbox_publishable_key'] ?? ''),
                (string) ($current['stripe_sandbox_publishable_key'] ?? '')
            ),
            'stripe_sandbox_secret_key' => $this->keptSecret(
                (string) ($_POST['stripe_sandbox_secret_key'] ?? ''),
                (string) ($current['stripe_sandbox_secret_key'] ?? '')
            ),
            'stripe_sandbox_webhook_secret' => $this->keptSecret(
                (string) ($_POST['stripe_sandbox_webhook_secret'] ?? ''),
                (string) ($current['stripe_sandbox_webhook_secret'] ?? '')
            ),
            'stripe_live_publishable_key' => $this->keptSecret(
                (string) ($_POST['stripe_live_publishable_key'] ?? ''),
                (string) ($current['stripe_live_publishable_key'] ?? '')
            ),
            'stripe_live_secret_key' => $this->keptSecret(
                (string) ($_POST['stripe_live_secret_key'] ?? ''),
                (string) ($current['stripe_live_secret_key'] ?? '')
            ),
            'stripe_live_webhook_secret' => $this->keptSecret(
                (string) ($_POST['stripe_live_webhook_secret'] ?? ''),
                (string) ($current['stripe_live_webhook_secret'] ?? '')
            ),
        ];

        if (!empty($_POST['reset_logo'])) {
            $pairs['site_logo'] = '';
        } elseif (!empty($_FILES['site_logo']['tmp_name']) && is_uploaded_file($_FILES['site_logo']['tmp_name'])) {
            $uploadedLogo = $this->handleLogoUpload();
            if ($uploadedLogo) {
                $pairs['site_logo'] = $uploadedLogo;
            }
        }

        try {
            (new Setting())->setMany($pairs);
            Session::flash('success', 'Store settings saved.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Could not save settings. Check the settings table exists.');
        }

        $this->redirect('/admin/settings');
    }

    private function handleLogoUpload(): ?string
    {
        if (empty($_FILES['site_logo']['tmp_name']) || !is_uploaded_file($_FILES['site_logo']['tmp_name'])) {
            return null;
        }
        $ext = strtolower(pathinfo((string) $_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'], true)) {
            return null;
        }
        $dir = dirname(__DIR__, 3) . '/public/uploads/settings/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $dir . $filename)) {
            return 'settings/' . $filename;
        }
        return null;
    }

    private function keptSecret(string $posted, string $existing): string
    {
        $posted = trim($posted);
        if ($posted === '' || str_contains($posted, '•')) {
            return $existing;
        }
        return $posted;
    }
}
