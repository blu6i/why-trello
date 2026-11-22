<?php

declare(strict_types=1);

namespace App\Http\Validation;

/**
 * Валидатор параметров входящего запроса.
 *
 * Организует последовательное применение параметров-валидаторов (правил) к данным запроса.
 */
final class ParameterValidator
{
    /** @var array<string, mixed> Данные для валидации */
    private array $data;

    /** @var array<string, ValidatorInterface[]|ValidatorInterface> Набор правил для каждого поля */
    private array $rules;

    /** @var array<string, string[]> Список обнаруженных ошибок валидации */
    private array $errors = [];

    /**
     * Конструктор валидатора параметров.
     *
     * @param  array<string, mixed>  $data  Ассоциативный массив входных параметров запроса.
     * @param  array<string, ValidatorInterface[]|ValidatorInterface>  $rules  Массив правил валидации.
     */
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * Запуск процесса валидации параметров.
     *
     * @return bool True, если все параметры успешно прошли валидацию, иначе false.
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rulesList = is_array($fieldRules) ? $fieldRules : [$fieldRules];

            foreach ($rulesList as $rule) {
                if (! $rule instanceof ValidatorInterface) {
                    continue;
                }

                if (! $rule->validate($value, $field)) {
                    $this->errors[$field][] = $rule->getErrorMessage($field);
                }
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Проверка, завершилась ли валидация с ошибками.
     *
     * @return bool True, если есть хотя бы одна ошибка.
     */
    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Проверка, прошла ли валидация успешно.
     *
     * @return bool True, если ошибок нет.
     */
    public function passes(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Получение всех сообщений об ошибках, сгруппированных по полям.
     *
     * @return array<string, string[]> Массив ошибок по каждому полю.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получение первого текстового сообщения об ошибке (удобно для flash-нотификаций).
     *
     * @return string|null Первая ошибка или null при их отсутствии.
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (! empty($fieldErrors[0])) {
                return $fieldErrors[0];
            }
        }

        return null;
    }
}
