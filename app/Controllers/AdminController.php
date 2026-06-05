<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Student;

class AdminController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_LOCK_SECONDS = 900;

    public function loginForm(): void
    {
        if (is_admin_logged_in()) {
            redirect('/admin/dashboard');
        }

        $this->view('admin/login', [
            'title' => 'Login Admin',
        ]);
    }

    public function login(): void
    {
        if (!verify_csrf($_POST['_csrf_token'] ?? null)) {
            set_flash('error', 'Token keamanan tidak valid. Silakan coba lagi.');
            redirect('/admin/login');
        }

        $lockSeconds = $this->loginLockRemainingSeconds();

        if ($lockSeconds > 0) {
            set_flash('error', 'Terlalu banyak percobaan login. Coba lagi dalam ' . (int) ceil($lockSeconds / 60) . ' menit.');
            redirect('/admin/login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || strlen($username) > 100 || strlen($password) > 4096) {
            $this->recordFailedLogin();
            remember_old(['username' => $username]);
            set_flash('error', 'Username atau password salah.');
            redirect('/admin/login');
        }

        $admin = (new Admin())->findByUsername($username);

        if ($admin === null || !password_verify($password, (string) $admin['password_hash'])) {
            $this->recordFailedLogin();
            remember_old(['username' => $username]);
            set_flash('error', 'Username atau password salah.');
            redirect('/admin/login');
        }

        $this->clearFailedLogins();
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => (int) $admin['id'],
            'username' => $admin['username'],
        ];

        set_flash('success', 'Login admin berhasil.');
        redirect('/admin/dashboard');
    }

    public function logout(): void
    {
        if (!verify_csrf($_POST['_csrf_token'] ?? null)) {
            set_flash('error', 'Token keamanan tidak valid.');
            redirect('/admin/dashboard');
        }

        unset($_SESSION['admin']);
        session_regenerate_id(true);
        set_flash('success', 'Anda sudah logout.');
        redirect('/admin/login');
    }

    public function dashboard(): void
    {
        $this->view('admin/dashboard', [
            'title' => 'Dashboard Admin',
        ], 'admin');
    }

    public function search(): void
    {
        $query = trim((string) ($_GET['order_no'] ?? ''));

        if ($query === '') {
            Response::json(['orders' => []]);
        }

        $orders = array_map(function (array $order): array {
            return [
                'order_no' => $order['order_no'],
                'school_name' => $order['school_name'],
                'school_address' => $order['school_address'],
                'photo_count' => (int) $order['photo_count'],
                'logo_url' => asset($order['school_logo']),
                'detail_url' => url('/admin/order/' . rawurlencode((string) $order['order_no'])),
            ];
        }, (new Order())->searchWithPhotoCount($query, 10));

        Response::json(['orders' => $orders]);
    }

    public function orderDetail(string $orderNo): void
    {
        $order = (new Order())->findByOrderNoWithCount($orderNo);

        if ($order === null) {
            $this->notFound('Nomor pesanan tidak ditemukan.');
        }

        $students = (new Student())->allByOrderId((int) $order['id']);

        $this->view('admin/order_detail', [
            'title' => 'Detail ' . $order['order_no'],
            'order' => $order,
            'students' => $students,
        ], 'admin');
    }

    private function loginLockRemainingSeconds(): int
    {
        $attempts = $_SESSION['_admin_login_attempts'] ?? null;

        if (!is_array($attempts) || (int) ($attempts['count'] ?? 0) < self::MAX_LOGIN_ATTEMPTS) {
            return 0;
        }

        $firstAttemptAt = (int) ($attempts['first_at'] ?? 0);
        $elapsed = time() - $firstAttemptAt;

        if ($elapsed >= self::LOGIN_LOCK_SECONDS) {
            $this->clearFailedLogins();
            return 0;
        }

        return self::LOGIN_LOCK_SECONDS - $elapsed;
    }

    private function recordFailedLogin(): void
    {
        $now = time();
        $attempts = $_SESSION['_admin_login_attempts'] ?? [
            'count' => 0,
            'first_at' => $now,
        ];

        if (!is_array($attempts) || ($now - (int) ($attempts['first_at'] ?? 0)) >= self::LOGIN_LOCK_SECONDS) {
            $attempts = [
                'count' => 0,
                'first_at' => $now,
            ];
        }

        $attempts['count'] = (int) ($attempts['count'] ?? 0) + 1;
        $attempts['last_at'] = $now;
        $_SESSION['_admin_login_attempts'] = $attempts;
    }

    private function clearFailedLogins(): void
    {
        unset($_SESSION['_admin_login_attempts']);
    }
}
