<?php

namespace Tests\Feature;

use Database\Seeders\HockeySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature-тесты SQL-консоли (задание 2).
 */
class Task2PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HockeySeeder::class);
    }

    #[Test]
    public function task2_page_is_available(): void
    {
        $this->get('/task2')
            ->assertOk()
            ->assertSee('Скачать дамп БД (SQL)', false);
    }

    #[Test]
    public function task2_executes_select_query(): void
    {
        $this->post('/task2/query', [
            'preset' => 'roster_join',
            'sql' => 'SELECT 1 AS value',
        ])
            ->assertOk()
            ->assertSee('Запрос выполнен успешно', false);
    }

    #[Test]
    public function roster_join_preset_returns_rows_for_demo_data(): void
    {
        $sql = <<<'SQL'
SELECT p.full_name_ru
FROM player_season_club psc
JOIN players p ON psc.player_id = p.id
JOIN clubs c ON psc.club_id = c.id
JOIN seasons s ON psc.season_id = s.id
WHERE c.name_ru = 'СКА' AND s.name = '2023/2024'
SQL;

        $this->post('/task2/query', [
            'preset' => 'roster_join',
            'sql' => $sql,
        ])
            ->assertOk()
            ->assertSee('Иван Петров', false);
    }
}
