<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // 1. users
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name', 100);
            $table->string('email', 255)->unique('idx_users_email');
            $table->string('password_hash', 255);
            $table->timestamp('created_datetime')->useCurrent();
            $table->timestamp('updated_datetime')->useCurrent();
        });

        // 2. boards
        Capsule::schema()->create('boards', function (Blueprint $table) {
            $table->id('board_id');
            $table->unsignedBigInteger('owner_user_id');
            $table->string('title', 150);
            $table->boolean('is_public')->default(false);
            $table->timestamp('created_datetime')->useCurrent();
            $table->timestamp('updated_datetime')->useCurrent();

            $table->foreign('owner_user_id', 'fk_boards_owner_user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('owner_user_id', 'idx_boards_owner_user_id');
        });

        // 3. board_columns
        Capsule::schema()->create('board_columns', function (Blueprint $table) {
            $table->id('column_id');
            $table->unsignedBigInteger('board_id');
            $table->string('title', 100);
            $table->string('color', 50)->nullable();
            $table->integer('ordinal_position')->default(0);
            $table->timestamp('created_datetime')->useCurrent();
            $table->timestamp('updated_datetime')->useCurrent();

            $table->foreign('board_id', 'fk_board_columns_board_id')
                ->references('board_id')
                ->on('boards')
                ->onDelete('cascade');

            $table->index('board_id', 'idx_board_columns_board_id');
        });

        // 4. cards
        Capsule::schema()->create('cards', function (Blueprint $table) {
            $table->id('card_id');
            $table->unsignedBigInteger('board_id');
            $table->unsignedBigInteger('column_id');
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->string('color', 50)->nullable();
            $table->integer('sort_index')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('created_datetime')->useCurrent();
            $table->timestamp('updated_datetime')->useCurrent();

            $table->foreign('board_id', 'fk_cards_board_id')
                ->references('board_id')
                ->on('boards')
                ->onDelete('cascade');

            $table->foreign('column_id', 'fk_cards_column_id')
                ->references('column_id')
                ->on('board_columns')
                ->onDelete('cascade');

            $table->index('board_id', 'idx_cards_board_id');
            $table->index('column_id', 'idx_cards_column_id');
        });

        // 5. Partial Index в PostgreSQL для неархивированных карточек
        Capsule::connection()->statement('
            CREATE INDEX idx_cards_active_board_id ON cards (board_id) WHERE is_archived = false;
        ');

        // 6. Триггерная функция аудита PostgreSQL для записи в activity_logs
        Capsule::connection()->unprepared("
            CREATE OR REPLACE FUNCTION log_table_activity() RETURNS TRIGGER AS $$
            DECLARE
                entity_pk BIGINT;
                old_row JSONB := NULL;
                new_row JSONB := NULL;
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    entity_pk := (to_jsonb(NEW) ->> (TG_ARGV[0]))::BIGINT;
                    new_row := to_jsonb(NEW);
                ELSIF (TG_OP = 'UPDATE') THEN
                    entity_pk := (to_jsonb(NEW) ->> (TG_ARGV[0]))::BIGINT;
                    old_row := to_jsonb(OLD);
                    new_row := to_jsonb(NEW);
                ELSIF (TG_OP = 'DELETE') THEN
                    entity_pk := (to_jsonb(OLD) ->> (TG_ARGV[0]))::BIGINT;
                    old_row := to_jsonb(OLD);
                END IF;

                INSERT INTO activity_logs (entity_type, entity_id, action, old_values, new_values, created_at, updated_at)
                VALUES (TG_TABLE_NAME, entity_pk, TG_OP, old_row, new_row, NOW(), NOW());

                IF (TG_OP = 'DELETE') THEN
                    RETURN OLD;
                ELSE
                    RETURN NEW;
                END IF;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER users_activity_trigger
            AFTER INSERT OR UPDATE OR DELETE ON users
            FOR EACH ROW EXECUTE FUNCTION log_table_activity('user_id');

            CREATE TRIGGER boards_activity_trigger
            AFTER INSERT OR UPDATE OR DELETE ON boards
            FOR EACH ROW EXECUTE FUNCTION log_table_activity('board_id');

            CREATE TRIGGER board_columns_activity_trigger
            AFTER INSERT OR UPDATE OR DELETE ON board_columns
            FOR EACH ROW EXECUTE FUNCTION log_table_activity('column_id');

            CREATE TRIGGER cards_activity_trigger
            AFTER INSERT OR UPDATE OR DELETE ON cards
            FOR EACH ROW EXECUTE FUNCTION log_table_activity('card_id');
        ");
    }

    public function down(): void
    {
        // 1. Удаляем триггеры и функцию
        Capsule::connection()->unprepared('
            DROP TRIGGER IF EXISTS cards_activity_trigger ON cards;
            DROP TRIGGER IF EXISTS board_columns_activity_trigger ON board_columns;
            DROP TRIGGER IF EXISTS boards_activity_trigger ON boards;
            DROP TRIGGER IF EXISTS users_activity_trigger ON users;
            DROP FUNCTION IF EXISTS log_table_activity();
        ');

        // 2. Удаляем таблицы в обратном порядке (из-за foreign keys)
        Capsule::schema()->dropIfExists('cards');
        Capsule::schema()->dropIfExists('board_columns');
        Capsule::schema()->dropIfExists('boards');
        Capsule::schema()->dropIfExists('users');
    }
};
