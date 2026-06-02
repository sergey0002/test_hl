<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Демо-данные хоккейной БД (задание 2) — используется в сидере и в тестах.
 */
class HockeySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clubs')->insert([
            ['name_ru' => 'СКА', 'name_en' => 'SKA', 'city_ru' => 'Санкт-Петербург', 'city_en' => 'Saint Petersburg', 'created_at' => now(), 'updated_at' => now()],
            ['name_ru' => 'ЦСКА', 'name_en' => 'CSKA', 'city_ru' => 'Москва', 'city_en' => 'Moscow', 'created_at' => now(), 'updated_at' => now()],
            ['name_ru' => 'Ак Барс', 'name_en' => 'Ak Bars', 'city_ru' => 'Казань', 'city_en' => 'Kazan', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('seasons')->insert([
            ['name' => '2023/2024', 'year_start' => 2023, 'year_end' => 2024, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '2024/2025', 'year_start' => 2024, 'year_end' => 2025, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('players')->insert([
            ['full_name_ru' => 'Иван Петров', 'full_name_en' => 'Ivan Petrov', 'weight' => 86, 'height' => 182, 'created_at' => now(), 'updated_at' => now()],
            ['full_name_ru' => 'Алексей Смирнов', 'full_name_en' => 'Aleksey Smirnov', 'weight' => 90, 'height' => 188, 'created_at' => now(), 'updated_at' => now()],
            ['full_name_ru' => 'Дмитрий Волков', 'full_name_en' => 'Dmitry Volkov', 'weight' => 84, 'height' => 180, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('player_season_club')->insert([
            ['player_id' => 1, 'season_id' => 1, 'club_id' => 1, 'player_number' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['player_id' => 2, 'season_id' => 1, 'club_id' => 2, 'player_number' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['player_id' => 3, 'season_id' => 1, 'club_id' => 3, 'player_number' => 11, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
