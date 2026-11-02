<?php

declare(strict_types=1);

namespace App\Schedule\Tasks;

use App\Config;
use App\Log;
use App\Schedule\TaskInterface;
use App\Security\DatabaseSessionHandler;

final class SessionGcTask implements TaskInterface
{
    public function name(): string
    {
        return 'session:gc';
    }

    public function run(): void
    {
        $lifetime = (int) Config::get('app.session.lifetime', 7200);
        $handler = new DatabaseSessionHandler;

        $deletedCount = $handler->gc($lifetime);

        if ($deletedCount === false) {
            Log::get()->warning('Session GC task encountered an error during deletion.');

            return;
        }

        Log::get()->info("Session GC completed: purged {$deletedCount} expired sessions (older than {$lifetime}s).", [
            'deleted_sessions' => $deletedCount,
            'lifetime_seconds' => $lifetime,
        ]);
    }
}
