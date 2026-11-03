<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    private string $content;

    private int $statusCode;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'text/html; charset=UTF-8';

        return new self($html, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return new self($json, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $headers['Location'] = $url;

        return new self('', $status, $headers);
    }

    public static function empty(int $status = 204, array $headers = []): self
    {
        return new self('', $status, $headers);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Отправка HTTP-заголовков, статуса и содержимого клиенту.
     */
    public function send(): void
    {
        if (! headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }
}
