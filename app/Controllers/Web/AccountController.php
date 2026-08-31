<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AuthMiddleware;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Cart;
use App\Models\User;

final class AccountController extends Controller
{
    public function profile(): void
    {
        AuthMiddleware::requireCustomer('/account');
        $session = AuthMiddleware::user() ?? [];
        $user = (new User())->find((int) $session['id']);
        if (!$user) {
            AuthMiddleware::logoutCustomer();
            $this->redirect('/login');
        }

        $this->view('Storefront/account/profile', [
            'title'       => 'Profile',
            'profile'     => $user,
            'accountPage' => 'profile',
            'error'       => Session::flash('error'),
            'success'     => Session::flash('success'),
            'cartCount'   => Cart::count(),
            'fulfillment' => Cart::fulfillment(),
        ], 'Storefront/layouts/main');
    }

    public function update(): void
    {
        Helpers::requireCsrf();
        AuthMiddleware::requireCustomer('/account');
        $session = AuthMiddleware::user() ?? [];
        $id = (int) $session['id'];
        $userModel = new User();
        $current = $userModel->find($id);
        if (!$current) {
            $this->redirect('/login');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $postcode = Validator::normalizePostcode((string) ($_POST['postcode'] ?? ''));

        $v = new Validator();
        $v->name('name', $name)->email('email', $email)->phone('phone', $phone, false);
        if ($postcode !== '') {
            $v->ukPostcode('postcode', $postcode, true);
        }
        if ($address !== '' && mb_strlen($address) > 250) {
            $v->fail('address', 'Address is too long.');
        }
        if (!$v->ok()) {
            Session::flash('error', $v->firstError());
            $this->redirect('/account');
        }

        $existing = $userModel->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $id) {
            Session::flash('error', 'That email is already in use.');
            $this->redirect('/account');
        }

        $userModel->updateProfile($id, [
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone !== '' ? $phone : null,
            'address'  => $address !== '' ? $address : null,
            'postcode' => $postcode !== '' ? $postcode : null,
        ]);

        $newPassword = (string) ($_POST['new_password'] ?? '');
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        if ($newPassword !== '') {
            $pw = new Validator();
            $pw->password('new_password', $newPassword);
            if (!$pw->ok()) {
                Session::flash('error', $pw->firstError());
                $this->redirect('/account');
            }
            if (!password_verify($currentPassword, (string) $current['password_hash'])) {
                Session::flash('error', 'Current password is incorrect.');
                $this->redirect('/account');
            }
            $userModel->updatePassword($id, password_hash($newPassword, PASSWORD_DEFAULT));
        }

        AuthMiddleware::loginCustomer([
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
            'role'  => $current['role'],
        ]);
        Session::flash('success', 'Profile saved.');
        $this->redirect('/account');
    }
}
