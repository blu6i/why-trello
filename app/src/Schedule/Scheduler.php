<?php

declare(strict_types=1);

namespace App\Schedule;

use App\Log;
use Closure;
use DateTimeImmutable;
use Throwable;

final class Scheduler
{
    /** @var ScheduledTask[] */
    private array $tasks = [];

    /**
     * Регистрация задачи в расписании.
     */
    public function call(Closure|TaskInterface $task, string $name = ''): ScheduledTask
    {
        $scheduled = new ScheduledTask($task, $name);
        $this->tasks[] = $scheduled;

        return $scheduled;
    }

    /**
     * @return ScheduledTask[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Запуск всех задач, чей срок наступил в текущую минуту.
     *
     * @return array<string, array{status: string, duration_ms: float, error?: string}>
     */
    public function run(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $results = [];

        foreach ($this->tasks as $task) {
            if (! $task->isDue($now)) {
                continue;
            }

            $results[$task->getName()] = $this->executeTask($task);
        }

        return $results;
    }

    /**
     * Принудительное выполнение конкретной задачи по имени или объекту.
     *
     * @return array{status: string, duration_ms: float, error?: string}
     */
    public function runByName(string $name): array
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() === $name) {
                return $this->executeTask($task);
            }
        }

        throw new \InvalidArgumentException("Task [{$name}] is not registered in scheduler.");
    }

    /**
     * Выполнение задачи с замером времени и безопасным перехватом ошибок.
     *
     * @return array{status: string, duration_ms: float, error?: string}
     */
    private function executeTask(ScheduledTask $task): array
    {
        $taskName = $task->getName();
        $startTime = microtime(true);

        Log::get()->info("Cron task [{$taskName}] started.", [
            'cron_expression' => $task->getExpression(),
        ]);

        try {
            $task->run();
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::get()->info("Cron task [{$taskName}] finished successfully.", [
                'duration_ms' => $durationMs,
            ]);

            return [
                'status' => 'success',
                'duration_ms' => $durationMs,
            ];
        } catch (Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::get()->error("Cron task [{$taskName}] failed: {$e->getMessage()}", [
                'duration_ms' => $durationMs,
                'exception' => $e,
            ]);

            return [
                'status' => 'failed',
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
            ];
        }
    }
}
