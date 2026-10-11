<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class Encrypter
{
    private const CIPHER = 'AES-256-CBC';

    private string $key;

    public function __construct(?string $key = null)
    {
        $rawKey = $key ?? getenv('APP_KEY') ?: '';

        if (str_starts_with($rawKey, 'base64:')) {
            $rawKey = base64_decode(substr($rawKey, 7), true) ?: '';
        }

        if (strlen($rawKey) !== 32) {
            throw new RuntimeException('APP_KEY must be exactly 32 bytes (256 bits).');
        }

        $this->key = $rawKey;
    }

    /**
     * Зашифровать строку в формат ENC:base64(...)
     */
    public function encrypt(string $value): string
    {
        // 1. Генерируем случайный IV (16 байт)
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));

        // 2. Шифруем данные
        $ciphertext = openssl_encrypt($value, self::CIPHER, $this->key, 0, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        // 3. Вычисляем контрольную подпись (HMAC) для защиты от подделки
        $mac = hash_hmac('sha256', $iv.$ciphertext, $this->key);

        // 4. Упаковываем всё в JSON и кодируем в Base64
        $payload = json_encode([
            'iv' => base64_encode($iv),
            'value' => $ciphertext,
            'mac' => $mac,
        ], JSON_THROW_ON_ERROR);

        return 'ENC:'.base64_encode($payload);
    }

    /**
     * Расшифровать строку из формата ENC:base64(...)
     */
    public function decrypt(string $payload): string
    {
        if (str_starts_with($payload, 'ENC:')) {
            $payload = substr($payload, 4);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Payload is not valid base64.');
        }

        $data = json_decode($decoded, true);
        if (! is_array($data) || ! isset($data['iv'], $data['value'], $data['mac'])) {
            throw new RuntimeException('Invalid payload structure.');
        }

        $iv = base64_decode((string) $data['iv'], true);
        $ciphertext = (string) $data['value'];
        $mac = (string) $data['mac'];

        // Проверяем подпись HMAC (hash_equals защищает от timing-атак)
        $expectedMac = hash_hmac('sha256', $iv.$ciphertext, $this->key);
        if (! hash_equals($expectedMac, $mac)) {
            throw new RuntimeException('MAC verification failed. Encrypted data was corrupted or tampered with.');
        }

        // Расшифровываем
        $decrypted = openssl_decrypt($ciphertext, self::CIPHER, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        return $decrypted;
    }

    /**
     * Проверка: является ли значение зашифрованным
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, 'ENC:');
    }
}
