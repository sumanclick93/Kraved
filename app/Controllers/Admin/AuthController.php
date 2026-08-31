<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (AuthMiddleware::isAdmin()) {
            $this->redirect('/admin');
        }
        $this->view('Admin/auth/login', [
            'title' => 'Admin Login',
            'error' => Session::flash('error'),
            'oldEmail' => Session::flash('old_email'),
        ]);
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
            $this->redirect('/admin/login');
        }

        $user = (new User())->findByEmail($email);
        if (!$user || $user['role'] !== 'admin' || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Invalid admin credentials.');
            Session::flash('old_email', $email);
            $this->redirect('/admin/login');
        }

        Session::regenerate();
        AuthMiddleware::loginAdmin($user);
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        AuthMiddleware::logoutAdmin();
        $this->redirect('/admin/login');
    }
}
