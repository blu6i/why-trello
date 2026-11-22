<?php

declare(strict_types=1);

namespace App\Http\Validation;

/**
 * Интерфейс для параметров-валидаторов входящего HTTP-запроса.
 */
interface ValidatorInterface
{
    /**
     * Проверка валидности переданного значения параметра.
     *
     * @param  mixed  $value  Значение проверяемого параметра.
     * @param  string  $field  Имя проверяемого поля.
     * @return bool Возвращает true, если значение успешно прошло валидацию, иначе false.
     */
    public function validate(mixed $value, string $field): bool;

    /**
     * Получение человекочитаемого текста ошибки валидации на кириллице.
     *
     * @param  string  $field  Имя проверяемого поля.
     * @return string Текст сообщения об ошибке.
     */
    public function getErrorMessage(string $field): string;
}
