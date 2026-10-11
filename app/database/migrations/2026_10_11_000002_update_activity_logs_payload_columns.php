<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('payload');
            $table->jsonb('old_values')->nullable()->after('action');
            $table->jsonb('new_values')->nullable()->after('old_values');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['old_values', 'new_values']);
            $table->jsonb('payload')->nullable()->after('action');
        });
    }
};
