<?php

namespace Database\Seeders;

use App\Services\UserBulkInsertService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Демо-данные: хоккейная БД + 500 000 пользователей для экспорта (задание 3).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HockeySeeder::class);

        // 500k пользователей — один SQL через generate_series (см. UserBulkInsertService).
        $this->command?->info('Генерация '.UserBulkInsertService::DEFAULT_COUNT.' пользователей...');
        UserBulkInsertService::insert(UserBulkInsertService::DEFAULT_COUNT);
    }
}
