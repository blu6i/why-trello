<?php

declare(strict_types=1);

namespace App\Schedule;

interface TaskInterface
{
    /**
     * Человекочитаемое имя задачи для логов и CLI.
     */
    public function name(): string;

    /**
     * Выполнение основной логики задачи.
     */
    public function run(): void;
}
