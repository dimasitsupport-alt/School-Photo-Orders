<?php

declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!is_admin_logged_in()) {
            set_flash('error', 'Silakan login sebagai admin terlebih dahulu.');
            redirect('/admin/login');
        }
    }
}
