<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор целочисленного значения (например, идентификаторов сущностей).
 */
final class IntegerValidator implements ValidatorInterface
{
    /**
     * Проверяет, является ли значение целым числом или числовой строкой.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если значение является целым числом.
     */
    public function validate(mixed $value, string $field): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Возвращает текст ошибки при некорректном целочисленном значении.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] должно быть целым числом.";
    }
}
