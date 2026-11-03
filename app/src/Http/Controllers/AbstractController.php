<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Models\User;
use App\Security\Auth;
use RuntimeException;

abstract class AbstractController
{
    protected ?Request $request = null;

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = Request::fromGlobals();
        }

        return $this->request;
    }

    /**
     * Текущий авторизованный пользователь или null.
     */
    protected function user(): ?User
    {
        return Auth::user();
    }

    /**
     * ID текущего пользователя.
     */
    protected function userId(): ?int
    {
        return Auth::id();
    }

    /**
     * Авторизован ли текущий пользователь.
     */
    protected function check(): bool
    {
        return Auth::check();
    }

    /**
     * Рендеринг HTML-шаблона с передачей данных.
     *
     * @param  array<string, mixed>  $data
     */
    protected function render(string $viewPath, array $data = [], int $status = 200): Response
    {
        // Поиск шаблона: сначала проверяем как есть, затем относительно корня app/views
        $fullPath = $viewPath;
        if (! file_exists($fullPath)) {
            $fullPath = dirname(__DIR__, 3).'/views/'.ltrim($viewPath, '/');
            if (! str_ends_with($fullPath, '.php')) {
                $fullPath .= '.php';
            }
        }

        if (! file_exists($fullPath)) {
            throw new RuntimeException("View template [{$viewPath}] not found at [{$fullPath}].");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $fullPath;
        $content = (string) ob_get_clean();

        return Response::html($content, $status);
    }

    /**
     * Ответ в формате JSON.
     *
     * @param  array<string, string>  $headers
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }

    /**
     * HTTP-редирект.
     *
     * @param  array<string, string>  $headers
     */
    protected function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        return Response::redirect($url, $status, $headers);
    }

    /**
     * Сохранение Flash-сообщения в сессии.
     */
    protected function flash(string $key, string $message): void
    {
        $_SESSION["flash_{$key}"] = $message;
    }

    /**
     * Получение и удаление Flash-сообщения из сессии.
     */
    protected function getFlash(string $key): ?string
    {
        $message = $_SESSION["flash_{$key}"] ?? null;
        unset($_SESSION["flash_{$key}"]);

        return is_string($message) ? $message : null;
    }
}
