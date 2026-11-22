<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор минимальной длины строки.
 */
final class MinLengthValidator implements ValidatorInterface
{
    private int $minLength;

    /**
     * Конструктор валидатора.
     *
     * @param  int  $minLength  Минимально допустимое количество символов.
     */
    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;
    }

    /**
     * Проверяет, что длина строки не меньше указанного лимита.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если длина строки достаточна.
     */
    public function validate(mixed $value, string $field): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return mb_strlen(trim($value)) >= $this->minLength;
    }

    /**
     * Возвращает текст ошибки при недостаточной длине поля.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] должно содержать не менее {$this->minLength} символов.";
    }
}
