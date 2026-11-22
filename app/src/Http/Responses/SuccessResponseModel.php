<?php

declare(strict_types=1);

namespace App\Http\Responses;

/**
 * Модель успешного ответа операции.
 */
final class SuccessResponseModel extends BaseResponseModel
{
    /**
     * Конструктор успешного ответа.
     *
     * @param  string  $message  Сообщение об успешном выполнении.
     * @param  mixed  $data  Данные результата операции.
     * @param  int  $statusCode  HTTP статус-код (по умолчанию 200).
     */
    public function __construct(string $message = 'Операция выполнена успешно.', mixed $data = null, int $statusCode = 200)
    {
        parent::__construct(true, $message, $data, [], $statusCode);
    }
}
