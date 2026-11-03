<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\MiddlewareInterface;
use Closure;

final class Route
{
    private string $method;

    private string $path;

    /** @var Closure|array{0: class-string, 1: string} */
    private $action;

    /** @var array<int, class-string<MiddlewareInterface>|MiddlewareInterface> */
    private array $middleware = [];

    /**
     * @param  Closure|array{0: class-string, 1: string}  $action
     */
    public function __construct(string $method, string $path, Closure|array $action)
    {
        $this->method = strtoupper($method);
        $this->path = $path === '/' ? '/' : rtrim($path, '/');
        $this->action = $action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return Closure|array{0: class-string, 1: string}
     */
    public function getAction(): Closure|array
    {
        return $this->action;
    }

    /**
     * @return array<int, class-string<MiddlewareInterface>|MiddlewareInterface>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Назначение одного или нескольких middleware на маршрут.
     *
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|array<int, class-string<MiddlewareInterface>|MiddlewareInterface>  $middleware
     */
    public function middleware(string|object|array $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Проверка соответствия HTTP-метода и URI текущему маршруту.
     *
     * @param  array<string, string>  $params
     */
    public function matches(string $method, string $path, array &$params = []): bool
    {
        if ($this->method !== 'ANY' && $this->method !== strtoupper($method)) {
            return false;
        }

        return $this->matchesPath($path, $params);
    }

    /**
     * Проверка совпадения пути маршрута без учета HTTP-метода (для 405 Method Not Allowed).
     *
     * @param  array<string, string>  $params
     */
    public function matchesPath(string $path, array &$params = []): bool
    {
        $normalizedPath = $path === '/' ? '/' : rtrim($path, '/');

        // Быстрое точное совпадение
        if ($this->path === $normalizedPath) {
            $params = [];

            return true;
        }

        // Если в маршруте нет параметров вида {param}, не совпало
        if (! str_contains($this->path, '{')) {
            return false;
        }

        // Преобразование {param} в именованную группу регулярного выражения: (?P<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', '(?P<$1>[^/]+)', $this->path);
        $regex = '#^'.$pattern.'$#u';

        if (preg_match($regex, $normalizedPath, $matches)) {
            $params = [];
            foreach ($matches as $key => $val) {
                if (is_string($key)) {
                    $params[$key] = urldecode($val);
                }
            }

            return true;
        }

        return false;
    }
}
