<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор строкового типа данных.
 */
final class StringValidator implements ValidatorInterface
{
    /**
     * Проверяет, является ли значение строкой.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если значение строкового типа.
     */
    public function validate(mixed $value, string $field): bool
    {
        return is_string($value);
    }

    /**
     * Возвращает текст ошибки при нестроковом типе данных.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] должно быть строкой.";
    }
}
