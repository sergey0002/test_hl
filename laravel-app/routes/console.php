<?php

use App\Services\UserBulkInsertService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Генерация пользователей для экспорта (задание 3).
 * Пример: php artisan users:generate 500000 --fresh
 */
Artisan::command('users:generate {count=500000 : Сколько строк создать} {--fresh : Очистить users перед вставкой}', function () {
    $count = max(1, (int) $this->argument('count'));

    if ($this->option('fresh')) {
        $this->info('Очистка таблицы users...');
        UserBulkInsertService::truncate();
    }

    $this->info("Генерация {$count} пользователей (PostgreSQL generate_series)...");
    $started = microtime(true);

    UserBulkInsertService::insert($count);

    $total = (int) DB::table('users')->count();
    $seconds = round(microtime(true) - $started, 2);

    $this->info("Готово за {$seconds} сек. В таблице users: {$total}");
})->purpose('Создать большой набор users для CSV-экспорта');
