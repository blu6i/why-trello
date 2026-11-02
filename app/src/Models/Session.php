<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\Encrypter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class Session extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'sessions';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Пользователь, которому принадлежит сессия.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Мутатор/аксессор для прозрачного шифрования IP-адреса.
     *
     * В базе хранится ENC:..., а при обращении $session->ip_address возвращается расшифрованный IP.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function ipAddress(): Attribute
    {
        return Attribute::make(
            get: static function (?string $value): ?string {
                if ($value === null || ! str_starts_with($value, 'ENC:')) {
                    return $value;
                }

                try {
                    return (new Encrypter)->decrypt($value);
                } catch (Throwable) {
                    return null;
                }
            },
            set: static function (?string $value): ?string {
                if ($value === null || str_starts_with($value, 'ENC:')) {
                    return $value;
                }

                try {
                    return (new Encrypter)->encrypt($value);
                } catch (Throwable) {
                    return $value;
                }
            }
        );
    }
}
