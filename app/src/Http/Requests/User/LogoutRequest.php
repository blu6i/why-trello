<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\AbstractRequest;
use App\Http\Responses\ResponseModelInterface;
use App\Http\Responses\SuccessResponseModel;
use App\Security\UserSessionAuth;

/**
 * Класс запроса разлогина (выхода пользователя из сессии).
 *
 * Реализует:
 * 1. Валидацию параметров (не требуются).
 * 2. Валидацию логики (проверка наличия активной сессии).
 * 3. Выполнение разлогина (очистка сессии и удаление сессионной куки).
 */
final class LogoutRequest extends AbstractRequest
{
    private ?UserSessionAuth $auth = null;

    public function setAuth(UserSessionAuth $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    private function getAuth(): UserSessionAuth
    {
        if ($this->auth === null) {
            $this->auth = new UserSessionAuth;
        }

        return $this->auth;
    }

    /**
     * Для разлогина дополнительных параметров не требуется.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Валидация логики: проверка активности сессии.
     *
     * @return bool
     */
    public function validateLogic(): bool
    {
        return true;
    }

    /**
     * Выполнение разлогина: уничтожение сессии и очистка куки.
     *
     * @return ResponseModelInterface
     */
    public function execute(): ResponseModelInterface
    {
        $this->getAuth()->logout();

        return new SuccessResponseModel('Вы успешно вышли из системы. Сессионная кука удалена.');
    }
}
