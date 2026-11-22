<?php

declare(strict_types=1);

namespace App\Http\Validation\Rules;

use App\Http\Validation\ValidatorInterface;

/**
 * Параметр-валидатор максимальной длины строки.
 */
final class MaxLengthValidator implements ValidatorInterface
{
    private int $maxLength;

    /**
     * Конструктор валидатора.
     *
     * @param  int  $maxLength  Максимально допустимое количество символов.
     */
    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * Проверяет, что длина строки не превышает указанный лимит.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool True, если длина строки не превышает лимит.
     */
    public function validate(mixed $value, string $field): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return mb_strlen(trim($value)) <= $this->maxLength;
    }

    /**
     * Возвращает текст ошибки при превышении допустимой длины.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Сообщение об ошибке.
     */
    public function getErrorMessage(string $field): string
    {
        return "Поле [{$field}] не должно превышать {$this->maxLength} символов.";
    }
}
