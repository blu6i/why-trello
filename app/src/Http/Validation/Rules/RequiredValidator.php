<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор обязательного присутствия поля в запросе.
 */
final class RequiredValidator implements ValidatorInterface
{
    /**
     * Проверяет, что поле заполнено и не является пустым.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если поле заполнено.
     */
    public function validate(mixed $value, string $field): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Возвращает текст ошибки при отсутствии обязательного поля.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] обязательно для заполнения.";
    }
}
