<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $candidates = [];

        if ($scriptDir !== '' && $scriptDir !== '.' && $scriptDir !== '/') {
            $candidates[] = $scriptDir;

            if (str_ends_with($scriptDir, '/public')) {
                $candidates[] = substr($scriptDir, 0, -7) ?: '';
            }
        }

        foreach ($candidates as $basePath) {
            if ($basePath !== '' && str_starts_with($uri, $basePath)) {
                $uri = substr($uri, strlen($basePath));
                break;
            }
        }

        $path = '/' . trim($uri, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
