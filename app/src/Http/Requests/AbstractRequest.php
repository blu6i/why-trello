<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Request;
use App\Http\Responses\ErrorResponseModel;
use App\Http\Responses\ResponseModelInterface;
use App\Http\Validation\ParameterValidator;
use App\Http\Validation\ValidatorInterface;
use App\Models\User;

/**
 * Абстрактный базовый класс запроса приложения.
 *
 * Объединяет:
 * 1. Валидацию входных параметров через параметры-валидаторы (validateParameters).
 * 2. Валидацию бизнес-логики и прав доступа (validateLogic).
 * 3. Метод выполнения запроса с полезной нагрузкой (execute).
 */
abstract class AbstractRequest
{
    /** @var Request Объект входящего HTTP-запроса */
    protected Request $request;

    /** @var array<string, mixed> Ассоциативный массив извлеченных параметров запроса */
    protected array $parameters;

    /** @var array<string, mixed> Список накопленных ошибок валидации */
    protected array $errors = [];

    /** @var User|null Текущий авторизованный пользователь (если сессия активна) */
    protected ?User $user;

    /**
     * Конструктор запроса.
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @param  User|null  $user  Текущий авторизованный пользователь (опционально).
     */
    public function __construct(Request $request, ?User $user = null)
    {
        $this->request = $request;
        $this->parameters = $request->all();
        $this->user = $user;
    }

    /**
     * Определение набора правил для параметров-валидаторов.
     *
     * @return array<string, ValidatorInterface[]|ValidatorInterface> Ассоциативный массив правил по полям.
     */
    abstract protected function rules(): array;

    /**
     * 1. Валидация входных параметров запроса с использованием параметров-валидаторов.
     *
     * @return bool True, если параметры соответствуют заданным правилам, иначе false.
     */
    public function validateParameters(): bool
    {
        $validator = new ParameterValidator($this->parameters, $this->rules());

        if (! $validator->validate()) {
            $this->errors['parameters'] = $validator->getErrors();

            return false;
        }

        return true;
    }

    /**
     * 2. Валидация бизнес-логики запроса (состояние БД, права доступа, уникальность записей).
     *
     * Переопределяется в конкретных классах запросов при необходимости.
     *
     * @return bool True, если бизнес-логика позволяет продолжить выполнение, иначе false.
     */
    public function validateLogic(): bool
    {
        return true;
    }

    /**
     * Комплексная валидация: сначала параметры, затем бизнес-логика.
     *
     * @return bool True, если обе проверки завершились успешно.
     */
    public function validate(): bool
    {
        $this->errors = [];

        if (! $this->validateParameters()) {
            return false;
        }

        return $this->validateLogic();
    }

    /**
     * 3. Метод выполнения запроса (полезная нагрузка).
     *
     * Содержит непосредственную логику обработки запроса и возвращает структурированную модель ответа.
     *
     * @return ResponseModelInterface Модель ответа (результат операции).
     */
    abstract public function execute(): ResponseModelInterface;

    /**
     * Фасадный метод безопасного запуска: проводит валидацию и при успехе выполняет запрос.
     *
     * @return ResponseModelInterface Модель ответа (успех или ошибка).
     */
    public function handle(): ResponseModelInterface
    {
        if (! $this->validate()) {
            return new ErrorResponseModel(
                $this->getFirstError() ?? 'Ошибка валидации запроса.',
                $this->errors,
                422
            );
        }

        return $this->execute();
    }

    /**
     * Получение значения параметра запроса по имени.
     *
     * @param  string  $key  Имя параметра.
     * @param  mixed  $default  Значение по умолчанию.
     * @return mixed Значение параметра.
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Получение всех параметров запроса.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Получение текущего пользователя запроса.
     *
     * @return User|null Модель пользователя или null.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Получение всех ошибок валидации.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получение первого текстового сообщения об ошибке.
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        if (isset($this->errors['logic']) && is_string($this->errors['logic'])) {
            return $this->errors['logic'];
        }

        if (isset($this->errors['parameters']) && is_array($this->errors['parameters'])) {
            foreach ($this->errors['parameters'] as $fieldErrors) {
                if (is_array($fieldErrors) && ! empty($fieldErrors[0])) {
                    return $fieldErrors[0];
                }
            }
        }

        return null;
    }
}
