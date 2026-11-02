<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('sessions', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');

            $table->foreign('user_id', 'fk_sessions_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_id', 'idx_sessions_user_id');
            $table->index('last_activity', 'idx_sessions_last_activity');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('sessions');
    }
};
