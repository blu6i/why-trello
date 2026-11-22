<?php

declare(strict_types=1);

namespace App\Http\Responses;

/**
 * Модель ответа с ошибками валидации или бизнес-логики.
 */
final class ErrorResponseModel extends BaseResponseModel
{
    /**
     * Конструктор ответа с ошибкой.
     *
     * @param  string  $message  Главное сообщение об ошибке на кириллице.
     * @param  array<string, mixed>  $errors  Детализированный список ошибок.
     * @param  int  $statusCode  HTTP статус-код (по умолчанию 422 Unprocessable Entity).
     */
    public function __construct(string $message = 'Ошибка валидации данных.', array $errors = [], int $statusCode = 422)
    {
        parent::__construct(false, $message, null, $errors, $statusCode);
    }
}
