<?php

declare(strict_types=1);

namespace App\Core;

final class AuthMiddleware
{
    public const CUSTOMER_KEY = 'customer';
    public const ADMIN_KEY = 'admin_user';

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            Session::flash('error', 'Please log in as admin.');
            Helpers::redirect('/admin/login');
        }
    }

    public static function requireCustomer(?string $intended = null): void
    {
        if (!self::customer()) {
            Session::set('intended', $intended ?: '/account');
            Session::flash('error', 'Please log in to continue.');
            Helpers::redirect('/login');
        }
    }

    public static function requireCustomerJson(): array
    {
        $user = self::customer();
        if (!is_array($user) || empty($user['id'])) {
            Helpers::json(['error' => 'Please log in', 'login' => true], 401);
        }
        return $user;
    }

    /** Storefront identity — independent of the admin panel. */
    public static function user(): ?array
    {
        return self::customer();
    }

    public static function customer(): ?array
    {
        self::migrateLegacy();
        $user = Session::get(self::CUSTOMER_KEY);
        return is_array($user) && !empty($user['id']) ? $user : null;
    }

    public static function admin(): ?array
    {
        self::migrateLegacy();
        $user = Session::get(self::ADMIN_KEY);
        if (is_array($user) && ($user['role'] ?? '') === 'admin' && !empty($user['id'])) {
            return $user;
        }
        return null;
    }

    public static function check(): bool
    {
        return self::customer() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::admin() !== null;
    }

    public static function loginCustomer(array $user): void
    {
        Session::set(self::CUSTOMER_KEY, [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'] ?? 'customer',
        ]);
    }

    public static function loginAdmin(array $user): void
    {
        Session::set(self::ADMIN_KEY, [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => 'admin',
        ]);
    }

    public static function logoutCustomer(): void
    {
        Session::remove(self::CUSTOMER_KEY);
        Session::remove('intended');
    }

    public static function logoutAdmin(): void
    {
        Session::remove(self::ADMIN_KEY);
    }

    /**
     * Older installs stored both roles in $_SESSION['user'].
     * Split once so an existing tab keeps working.
     */
    private static function migrateLegacy(): void
    {
        $legacy = Session::get('user');
        if (!is_array($legacy) || empty($legacy['id'])) {
            return;
        }
        if (($legacy['role'] ?? '') === 'admin') {
            if (!Session::has(self::ADMIN_KEY)) {
                Session::set(self::ADMIN_KEY, $legacy);
            }
        } elseif (!Session::has(self::CUSTOMER_KEY)) {
            Session::set(self::CUSTOMER_KEY, $legacy);
        }
        Session::remove('user');
    }
}
