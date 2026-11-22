<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Response;

/**
 * Интерфейс модели ответа в архитектуре MVC.
 *
 * Определяет единый контракт для структурированных ответов слоя приложения.
 */
interface ResponseModelInterface
{
    /**
     * Признак успешности выполнения запроса.
     *
     * @return bool True при успехе, false при ошибке.
     */
    public function isSuccess(): bool;

    /**
     * Человекочитаемое сообщение о результате выполнения.
     *
     * @return string Сообщение на кириллице.
     */
    public function getMessage(): string;

    /**
     * Полезная нагрузка ответа (данные).
     *
     * @return mixed Структурированные данные ответа.
     */
    public function getData(): mixed;

    /**
     * Список ошибок, возникших при валидации параметров или бизнес-логики.
     *
     * @return array<string, mixed> Ассоциативный массив ошибок.
     */
    public function getErrors(): array;

    /**
     * HTTP-код статуса ответа (например, 200, 400, 422).
     *
     * @return int Код статуса.
     */
    public function getStatusCode(): int;

    /**
     * Преобразование модели ответа в стандартный ассоциативный массив.
     *
     * @return array<string, mixed> Массив представления ответа.
     */
    public function toArray(): array;

    /**
     * Преобразование модели в готовый HTTP-объект Response для отправки клиенту.
     *
     * @return Response Объект HTTP-ответа.
     */
    public function toHttpResponse(): Response;
}
