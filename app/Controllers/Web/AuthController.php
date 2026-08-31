<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->captureIntended();
        $this->view('Storefront/auth/login', [
            'title'     => 'Login',
            'error'     => Session::flash('error'),
            'oldEmail'  => Session::flash('old_email'),
            'cartCount' => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function login(): void
    {
        Helpers::requireCsrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $v = new Validator();
        $v->email('email', $email)->required('password', $password, 'Password');
        if (!$v->ok()) {
            Session::flash('error', $v->firstError());
            Session::flash('old_email', $email);
            $this->redirect('/login');
        }

        $user = (new User())->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Invalid email or password.');
            Session::flash('old_email', $email);
            $this->redirect('/login');
        }

        Session::regenerate();
        AuthMiddleware::loginCustomer($user);
        (new Order())->claimGuestOrders((int) $user['id'], $user['email']);
        $this->redirectAfterAuth();
    }

    public function registerForm(): void
    {
        $this->captureIntended();
        $this->view('Storefront/auth/register', [
            'title'     => 'Create Account',
            'error'     => Session::flash('error'),
            'old'       => Session::flash('old') ?: [],
            'cartCount' => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function register(): void
    {
        Helpers::requireCsrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $v = new Validator();
        $v->name('name', $name)
            ->email('email', $email)
            ->phone('phone', $phone)
            ->password('password', $password);
        if (!$v->ok()) {
            Session::flash('error', $v->firstError());
            Session::flash('old', ['name' => $name, 'email' => $email, 'phone' => $phone]);
            $this->redirect('/register');
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            Session::flash('error', 'Email already registered.');
            Session::flash('old', ['name' => $name, 'email' => $email, 'phone' => $phone]);
            $this->redirect('/register');
        }

        $id = $userModel->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
        ]);

        Session::regenerate();
        AuthMiddleware::loginCustomer([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => 'customer',
        ]);
        (new Order())->claimGuestOrders($id, $email);
        $this->redirectAfterAuth();
    }

    private function captureIntended(): void
    {
        $next = strtolower(trim((string) ($_GET['next'] ?? '')));
        $map = [
            'orders'   => '/orders',
            'account'  => '/account',
            'profile'  => '/account',
            'wishlist' => '/wishlist',
        ];
        if (isset($map[$next])) {
            Session::set('intended', $map[$next]);
        }
    }

    private function redirectAfterAuth(): never
    {
        $intended = Session::get('intended');
        Session::remove('intended');
        if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
            $this->redirect($intended);
        }
        $this->redirect('/account');
    }

    public function logout(): void
    {
        AuthMiddleware::logoutCustomer();
        $this->redirect('/');
    }
}
