<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    public function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutPath = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        if (!is_file($layoutPath)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        require $layoutPath;
    }

    protected function notFound(string $message = 'Data tidak ditemukan.'): never
    {
        http_response_code(404);
        $this->view('errors/404', ['message' => $message]);
        exit;
    }
}
