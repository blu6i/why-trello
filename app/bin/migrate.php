<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;

$capsule = Database::init();
$db = $capsule->getDatabaseManager();
$files = new Filesystem;

// 1. Создаем репозиторий для таблицы migrations
$repository = new DatabaseMigrationRepository($db, 'migrations');

// 2. Создаем сам мигратор
$migrator = new Migrator($repository, $db, $files);

// 3. Создаем таблицу migrations, если ее еще нет в базе
if (! $repository->repositoryExists()) {
    $repository->createRepository();
    echo "Created 'migrations' table.\n";
}

// 4. Запускаем миграции из папки!
$migrationPaths = [__DIR__.'/../database/migrations'];

echo "Running migrations...\n";
$ran = $migrator->run($migrationPaths);
if (empty($ran)) {
    echo "Nothing to migrate.\n";
} else {
    foreach ($ran as $migration) {
        echo "Migrated: {$migration}\n";
    }
}
echo "Done!\n";
