<?php

declare(strict_types=1);

namespace App;

use App\Security\Encrypter;
use RuntimeException;

final class Config
{
    private static array $cache = [];

    private static ?Encrypter $encrypter = null;

    /**
     * Получить текущее окружение
     */
    public static function env(): string
    {
        return (string) (getenv('APP_ENV') ?: 'dev');
    }

    /**
     * Получить значение параметра
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if ($file === null || $file === '') {
            return $default;
        }

        // Загружаем файл конфигурации
        $config = self::loadFile($file);

        // Выбираем секцию для текущего окружения
        $env = self::env();
        $envData = $config[$env] ?? null;

        if ($envData === null) {
            return $default;
        }

        // Если запрашивали весь файл целиком
        if (empty($segments)) {
            return self::decrypt($envData);
        }

        // Проходим по вложенным ключам
        $current = $envData;
        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        // Если значение зашифровано - расшифровываем
        if (is_string($current) && Encrypter::isEncrypted($current)) {
            return self::getEncrypter()->decrypt($current);
        }

        return $current ?? $default;
    }

    /**
     * Загрузка файла конфигурации из папки config/
     */
    private static function loadFile(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $filePath = dirname(__DIR__)."/config/{$name}.php";

        if (! file_exists($filePath)) {
            throw new RuntimeException("Файл с конфигом не найдено: {$filePath}");
        }

        $data = require $filePath;

        if (! is_array($data)) {
            throw new RuntimeException("Файл с конфигом возвращает не массив: {$filePath}");
        }

        self::$cache[$name] = $data;

        return $data;
    }

    /**
     * Расшифровка значений в массиве настроек окружения
     */
    private static function decrypt(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = (is_string($value) && Encrypter::isEncrypted($value))
                ? self::getEncrypter()->decrypt($value)
                : $value;
        }

        return $result;
    }

    /**
     * Инициализация объекта Encrypter
     */
    private static function getEncrypter(): Encrypter
    {
        if (self::$encrypter === null) {
            self::$encrypter = new Encrypter;
        }

        return self::$encrypter;
    }
}
