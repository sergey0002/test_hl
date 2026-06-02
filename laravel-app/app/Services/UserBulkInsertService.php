<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Массовая вставка пользователей через generate_series (быстро для 500k+).
 */
final class UserBulkInsertService
{
    /** Количество пользователей по умолчанию для задания 3. */
    public const DEFAULT_COUNT = 500_000;

    /**
     * Вставляет N пользователей одним SQL-запросом PostgreSQL.
     */
    public static function insert(int $count = self::DEFAULT_COUNT): void
    {
        if ($count < 1) {
            return;
        }

        $passwordHash = bcrypt('password');
        $now = now()->toDateTimeString();

        DB::insert(
            <<<'SQL'
INSERT INTO users (name, first_name, last_name, phone, email, password, created_at, updated_at)
SELECT
    'User ' || i,
    'Name' || i,
    'Surname' || i,
    '+7900' || LPAD(i::text, 7, '0'),
    'user' || i || '@example.com',
    ?,
    ?::timestamp,
    ?::timestamp
FROM generate_series(1, ?) AS i
SQL,
            [$passwordHash, $now, $now, $count]
        );
    }

    /**
     * Очищает таблицу users и сбрасывает счётчик id.
     */
    public static function truncate(): void
    {
        DB::statement('TRUNCATE TABLE users RESTART IDENTITY CASCADE');
    }
}
