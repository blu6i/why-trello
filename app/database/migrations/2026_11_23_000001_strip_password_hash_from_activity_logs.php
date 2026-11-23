<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Обновляем триггерную функцию аудита: исключаем password_hash из JSONB-снимков
        Capsule::connection()->unprepared("
            CREATE OR REPLACE FUNCTION log_table_activity() RETURNS TRIGGER AS $$
            DECLARE
                entity_pk BIGINT;
                old_row JSONB := NULL;
                new_row JSONB := NULL;
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    entity_pk := (to_jsonb(NEW) ->> (TG_ARGV[0]))::BIGINT;
                    new_row := to_jsonb(NEW) - 'password_hash';
                ELSIF (TG_OP = 'UPDATE') THEN
                    entity_pk := (to_jsonb(NEW) ->> (TG_ARGV[0]))::BIGINT;
                    old_row := to_jsonb(OLD) - 'password_hash';
                    new_row := to_jsonb(NEW) - 'password_hash';
                ELSIF (TG_OP = 'DELETE') THEN
                    entity_pk := (to_jsonb(OLD) ->> (TG_ARGV[0]))::BIGINT;
                    old_row := to_jsonb(OLD) - 'password_hash';
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

            -- Очищаем уже сохраненные снимки пользователей от password_hash
            UPDATE activity_logs
            SET
                old_values = CASE WHEN old_values IS NOT NULL THEN old_values - 'password_hash' ELSE NULL END,
                new_values = CASE WHEN new_values IS NOT NULL THEN new_values - 'password_hash' ELSE NULL END
            WHERE entity_type = 'users';
        ");
    }

    public function down(): void
    {
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
        ");
    }
};
