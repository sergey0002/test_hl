<?php

namespace App\Http\Controllers;

use App\Services\Task2\DatabaseDumpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Задание 2: SQL-консоль, пресеты запросов и скачивание дампа БД.
 */
class Task2Controller extends Controller
{
    /**
     * Страница с меню запросов и редактором SQL.
     */
    public function index(): View
    {
        $queries = $this->queries();

        return view('task2', [
            'queries' => $queries,
            'selectedKey' => array_key_first($queries),
            'sqlInput' => $queries[array_key_first($queries)]['sql'] ?? '',
            'resultRows' => [],
            'execMessage' => null,
        ]);
    }

    /**
     * Выполнение SQL из формы (SELECT — таблица, иначе — число затронутых строк).
     */
    public function runQuery(Request $request): View
    {
        $queries = $this->queries();
        $selectedKey = (string) $request->input('preset', array_key_first($queries));
        if (! isset($queries[$selectedKey])) {
            $selectedKey = array_key_first($queries);
        }
        $sqlInput = (string) $request->input('sql', $queries[$selectedKey]['sql'] ?? '');
        $resultRows = [];
        $execMessage = null;

        try {
            $trimmed = trim($sqlInput);
            if ($trimmed !== '') {
                if (preg_match('/^\s*(SELECT|WITH|SHOW)\b/i', $trimmed) === 1) {
                    $resultRows = array_map(fn ($row) => (array) $row, DB::select($trimmed));
                    $execMessage = 'Запрос выполнен успешно. Строк: '.count($resultRows);
                } else {
                    $affected = DB::affectingStatement($trimmed);
                    $execMessage = 'Команда выполнена. Затронуто строк: '.$affected;
                }
            } else {
                $execMessage = 'SQL-запрос пустой.';
            }
        } catch (\Throwable $e) {
            $execMessage = 'Ошибка: '.$e->getMessage();
        }

        return view('task2', compact('queries', 'selectedKey', 'sqlInput', 'resultRows', 'execMessage'));
    }

    /**
     * Динамический SQL-дамп таблиц хоккейной БД (структура + данные).
     */
    public function downloadDump(DatabaseDumpService $dumpService): Response
    {
        $dump = $dumpService->generate();
        $filename = 'hockey_db_dump_'.gmdate('Y-m-d_His').'.sql';

        return response($dump, 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Готовые демо-запросы для кнопок меню.
     *
     * @return array<string, array{title: string, description: string, sql: string}>
     */
    private function queries(): array
    {
        return [
            'roster_join' => [
                'title' => 'Состав команды (базовый JOIN)',
                'description' => 'Список игроков выбранного клуба за сезон: ФИО, номер, клуб и сезон.',
                'sql' => "SELECT \n    p.full_name_ru,\n    p.full_name_en,\n    psc.player_number,\n    c.name_ru AS club_name,\n    s.name AS season_name\nFROM player_season_club psc\nJOIN players p ON psc.player_id = p.id\nJOIN clubs c ON psc.club_id = c.id\nJOIN seasons s ON psc.season_id = s.id\nWHERE c.name_ru = 'СКА' \n  AND s.name = '2023/2024'\nORDER BY psc.player_number ASC;",
            ],
            'clubs_stats' => [
                'title' => 'Статистика по клубам (GROUP BY)',
                'description' => 'Агрегация по клубам за сезон: количество игроков и min/avg/max роста.',
                'sql' => "SELECT \n    c.name_ru AS club,\n    COUNT(psc.id) AS players_count,\n    AVG(p.height) AS avg_height_cm,\n    MIN(p.height) AS min_height,\n    MAX(p.height) AS max_height\nFROM player_season_club psc\nJOIN players p ON psc.player_id = p.id\nJOIN clubs c ON psc.club_id = c.id\nJOIN seasons s ON psc.season_id = s.id\nWHERE s.name = '2024/2025'\nGROUP BY c.id, c.name_ru\nORDER BY avg_height_cm DESC;",
            ],
            'veteran_window' => [
                'title' => 'Поиск ветерана клуба (Window Function)',
                'description' => 'Топ игроков по числу сезонов в одном клубе с ранжированием внутри клуба.',
                'sql' => "WITH player_club_stats AS (\n    SELECT \n        p.full_name_ru,\n        c.name_ru AS club,\n        psc.club_id,\n        COUNT(*) AS seasons_count\n    FROM player_season_club psc\n    JOIN players p ON psc.player_id = p.id\n    JOIN clubs c ON psc.club_id = c.id\n    GROUP BY psc.player_id, psc.club_id, p.full_name_ru, c.name_ru\n), ranked AS (\n    SELECT \n        full_name_ru,\n        club,\n        seasons_count,\n        RANK() OVER (PARTITION BY club_id ORDER BY seasons_count DESC) AS rnk\n    FROM player_club_stats\n)\nSELECT full_name_ru, club, seasons_count\nFROM ranked\nWHERE rnk = 1\nORDER BY seasons_count DESC, full_name_ru\nLIMIT 10;",
            ],
        ];
    }
}
