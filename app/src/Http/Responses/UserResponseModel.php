<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;

/**
 * Модель ответа, содержащая данные пользователя.
 */
final class UserResponseModel extends BaseResponseModel
{
    /**
     * Конструктор модели ответа с пользователем.
     *
     * @param  User  $user  Модель пользователя Eloquent.
     * @param  string  $message  Сообщение о результате.
     * @param  int  $statusCode  HTTP статус-код.
     */
    public function __construct(User $user, string $message = 'Данные пользователя получены успешно.', int $statusCode = 200)
    {
        $userData = [
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        parent::__construct(true, $message, $userData, [], $statusCode);
    }
}
