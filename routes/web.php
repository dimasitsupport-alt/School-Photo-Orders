<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\CustomerController;
use App\Middleware\AuthMiddleware;

$router->get('/', [CustomerController::class, 'createOrder']);
$router->post('/orders', [CustomerController::class, 'storeOrder']);
$router->get('/orders/{orderNo}/students', [CustomerController::class, 'studentsForm']);
$router->post('/orders/{orderNo}/students', [CustomerController::class, 'storeStudents']);

$router->get('/admin', static fn () => redirect('/admin/dashboard'));
$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/logout', [AdminController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard'], [AuthMiddleware::class]);
$router->get('/admin/search', [AdminController::class, 'search'], [AuthMiddleware::class]);
$router->get('/admin/order/{orderNo}', [AdminController::class, 'orderDetail'], [AuthMiddleware::class]);
