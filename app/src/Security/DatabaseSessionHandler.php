<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\Session;
use App\Models\User;
use SessionHandlerInterface;
use Throwable;

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    /**
     * Открытие сессии (в БД соединение уже открыто).
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Закрытие сессии.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Чтение полезной нагрузки сессии по её ID.
     */
    public function read(string $id): string|false
    {
        try {
            $session = Session::find($id);

            if ($session === null) {
                return '';
            }

            return (string) $session->payload;
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Запись или обновление данных сессии в базе данных.
     */
    public function write(string $id, string $data): bool
    {
        try {
            $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

            // Защита от несуществующих user_id (foreign key check)
            if ($userId !== null && ! User::where('user_id', $userId)->exists()) {
                $userId = null;
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

            // Атомарный UPSERT через модель Eloquent
            $session = Session::find($id);
            if ($session === null) {
                $session = new Session;
                $session->id = $id;
            }
            $session->user_id = $userId;
            $session->ip_address = $ipAddress;
            $session->user_agent = $userAgent;
            $session->payload = $data;
            $session->last_activity = time();

            return (bool) $session->save();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Удаление сессии (например, при session_destroy()).
     */
    public function destroy(string $id): bool
    {
        try {
            Session::destroy($id);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Сборщик мусора: удаление всех сессий, активность которых старше $max_lifetime секунд.
     */
    public function gc(int $max_lifetime): int|false
    {
        try {
            $cutoff = time() - $max_lifetime;

            return (int) Session::where('last_activity', '<', $cutoff)->delete();
        } catch (Throwable) {
            return false;
        }
    }
}
