<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->dropIfExists('demo');
    }

    public function down(): void
    {
        Capsule::schema()->create('demo', function (Blueprint $table) {
            $table->id();
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
