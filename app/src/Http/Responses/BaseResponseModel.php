<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Response;

/**
 * Базовый класс модели ответа приложения.
 *
 * Инкапсулирует статус операции, сообщение, данные, ошибки и HTTP-код ответа.
 */
abstract class BaseResponseModel implements ResponseModelInterface
{
    /** @var bool Флаг успешности выполнения запроса */
    protected bool $success;

    /** @var string Текстовое пояснение результата */
    protected string $message;

    /** @var mixed Полезная нагрузка (данные сущности или результат) */
    protected mixed $data;

    /** @var array<string, mixed> Ошибки валидации или логики */
    protected array $errors;

    /** @var int HTTP статус-код */
    protected int $statusCode;

    /**
     * Конструктор базовой модели ответа.
     *
     * @param  bool  $success  Флаг успешности.
     * @param  string  $message  Сообщение на кириллице.
     * @param  mixed  $data  Полезные данные ответа.
     * @param  array<string, mixed>  $errors  Ошибки выполнения.
     * @param  int  $statusCode  HTTP статус-код.
     */
    public function __construct(
        bool $success,
        string $message = '',
        mixed $data = null,
        array $errors = [],
        int $statusCode = 200
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
        $this->statusCode = $statusCode;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Преобразование модели ответа в массив для JSON-сериализации.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        }

        if (count($this->errors) > 0) {
            $payload['errors'] = $this->errors;
        }

        return $payload;
    }

    /**
     * Преобразование модели ответа в готовый HTTP Response.
     */
    public function toHttpResponse(): Response
    {
        return Response::json($this->toArray(), $this->statusCode);
    }
}
