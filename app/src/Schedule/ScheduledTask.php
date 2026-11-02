<?php

declare(strict_types=1);

namespace App\Schedule;

use Closure;
use DateTimeImmutable;

final class ScheduledTask
{
    /** @var Closure|TaskInterface */
    private $handler;

    private string $name;

    private string $expression = '* * * * *';

    public function __construct(Closure|TaskInterface $handler, string $name = '')
    {
        $this->handler = $handler;

        if ($name !== '') {
            $this->name = $name;
        } elseif ($handler instanceof TaskInterface) {
            $this->name = $handler->name();
        } else {
            $this->name = 'closure_task_'.bin2hex(random_bytes(3));
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Установка произвольного cron-выражения (например, "0 0 * * *").
     */
    public function cron(string $expression): self
    {
        $this->expression = trim($expression);

        return $this;
    }

    /**
     * Каждую минуту: "* * * * *".
     */
    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    /**
     * Каждые N минут: "* / N * * * *".
     */
    public function everyMinutes(int $minutes): self
    {
        return $this->cron("*/{$minutes} * * * *");
    }

    public function everyFiveMinutes(): self
    {
        return $this->everyMinutes(5);
    }

    public function everyTenMinutes(): self
    {
        return $this->everyMinutes(10);
    }

    public function everyFifteenMinutes(): self
    {
        return $this->everyMinutes(15);
    }

    public function everyThirtyMinutes(): self
    {
        return $this->everyMinutes(30);
    }

    /**
     * Каждый час в 00 минут: "0 * * * *".
     */
    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Каждый час в указанную минуту: "M * * * *".
     */
    public function hourlyAt(int $minute): self
    {
        return $this->cron("{$minute} * * * *");
    }

    /**
     * Раз в сутки в полночь (00:00): "0 0 * * *".
     */
    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Раз в сутки в указанное время (например, "03:15"): "15 3 * * *".
     */
    public function dailyAt(string $time): self
    {
        $parts = explode(':', $time);
        $hour = isset($parts[0]) ? (int) $parts[0] : 0;
        $minute = isset($parts[1]) ? (int) $parts[1] : 0;

        return $this->cron("{$minute} {$hour} * * *");
    }

    /**
     * Проверяет, должна ли задача запускаться в момент $now.
     */
    public function isDue(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable('now');

        $parts = preg_split('/\s+/', trim($this->expression));
        if ($parts === false || count($parts) !== 5) {
            return false;
        }

        [$minuteExpr, $hourExpr, $dayExpr, $monthExpr, $weekdayExpr] = $parts;

        $currentMinute = (int) $now->format('i');
        $currentHour = (int) $now->format('G');
        $currentDay = (int) $now->format('j');
        $currentMonth = (int) $now->format('n');
        $currentWeekday = (int) $now->format('w'); // 0 (Вс) .. 6 (Сб)

        return $this->matchesField($minuteExpr, $currentMinute)
            && $this->matchesField($hourExpr, $currentHour)
            && $this->matchesField($dayExpr, $currentDay)
            && $this->matchesField($monthExpr, $currentMonth)
            && $this->matchesField($weekdayExpr, $currentWeekday, true);
    }

    /**
     * Вызов задачи.
     */
    public function run(): void
    {
        if ($this->handler instanceof TaskInterface) {
            $this->handler->run();
        } else {
            ($this->handler)();
        }
    }

    /**
     * Проверка соответствия одного поля cron-выражения значению времени.
     */
    private function matchesField(string $expression, int $value, bool $isWeekday = false): bool
    {
        if ($expression === '*') {
            return true;
        }

        // Поддержка списков через запятую (например, "1,15,30")
        if (str_contains($expression, ',')) {
            foreach (explode(',', $expression) as $part) {
                if ($this->matchesField(trim($part), $value, $isWeekday)) {
                    return true;
                }
            }

            return false;
        }

        // Шаг: "* / 5"
        if (str_starts_with($expression, '*/')) {
            $step = (int) substr($expression, 2);
            if ($step <= 0) {
                return false;
            }

            return ($value % $step) === 0;
        }

        // Диапазон: "1-5"
        if (str_contains($expression, '-')) {
            $range = explode('-', $expression, 2);
            $start = (int) $range[0];
            $end = (int) ($range[1] ?? $range[0]);

            return $value >= $start && $value <= $end;
        }

        // Для дня недели 7 = 0 (Воскресенье)
        if ($isWeekday && (int) $expression === 7) {
            return $value === 0;
        }

        return (int) $expression === $value;
    }
}
