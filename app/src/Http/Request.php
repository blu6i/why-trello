<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private string $method;

    private string $path;

    private string $uri;

    /** @var array<string, mixed> */
    private array $queryParams;

    /** @var array<string, mixed> */
    private array $bodyParams;

    /** @var array<string, mixed> */
    private array $routeParams = [];

    /** @var array<string, mixed> */
    private array $cookies;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param  array<string, mixed>  $queryParams
     * @param  array<string, mixed>  $bodyParams
     * @param  array<string, mixed>  $cookies
     * @param  array<string, string>  $headers
     */
    public function __construct(
        string $method,
        string $uri,
        array $queryParams = [],
        array $bodyParams = [],
        array $cookies = [],
        array $headers = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->queryParams = $queryParams;
        $this->bodyParams = $bodyParams;
        $this->cookies = $cookies;
        $this->headers = $headers;

        $path = parse_url($uri, PHP_URL_PATH);
        $this->path = is_string($path) ? rtrim($path, '/') : '/';
        if ($this->path === '') {
            $this->path = '/';
        }
    }

    /**
     * Создание Request из суперглобальных переменных PHP.
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Поддержка метода из заголовка или скрытого поля _method для REST-форм (PUT/DELETE)
        if ($method === 'POST') {
            if (isset($_POST['_method']) && is_string($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && is_string($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $bodyParams = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Парсинг JSON-тела при Content-Type: application/json
        if (str_contains(strtolower($contentType), 'application/json')) {
            $rawInput = file_get_contents('php://input');
            if ($rawInput !== false && $rawInput !== '') {
                $jsonData = json_decode($rawInput, true);
                if (is_array($jsonData)) {
                    $bodyParams = array_merge($bodyParams, $jsonData);
                }
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = is_string($value) ? $value : (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = is_string($value) ? $value : (string) $value;
            }
        }

        return new self($method, $uri, $_GET, $bodyParams, $_COOKIE, $headers);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Получить параметр из тела запроса (или значение по умолчанию).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->bodyParams[$key] ?? $default;
    }

    /**
     * Получить параметр из query string (?key=value).
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Получить именованный параметр маршрута (например, {id}).
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;

        return $this;
    }

    /**
     * Получить все параметры (объединение query и body).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->queryParams, $this->bodyParams);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');

        return str_contains(strtolower((string) $contentType), 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
