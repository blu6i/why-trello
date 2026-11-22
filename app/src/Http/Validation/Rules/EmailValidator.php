<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор корректного формата адреса электронной почты.
 */
final class EmailValidator implements ValidatorInterface
{
    /**
     * Проверяет, соответствует ли переданная строка формату email.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если строка является валидным email.
     */
    public function validate(mixed $value, string $field): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Возвращает текст ошибки при некорректном формате email.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] должно содержать корректный адрес электронной почты.";
    }
}
