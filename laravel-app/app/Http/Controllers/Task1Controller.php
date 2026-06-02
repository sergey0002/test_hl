<?php

namespace App\Http\Controllers;

use App\Services\SortService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Задание 1: демонстрация пузырьковой и нативной сортировки.
 */
class Task1Controller extends Controller
{
    public function __construct(private readonly SortService $sortService) {}

    /**
     * Страница задания: по умолчанию textarea с 200k случайных чисел.
     */
    public function index(): View
    {
        ini_set('max_execution_time', '1800');

        // В тестах не генерируем 200k чисел — ускоряет Feature-тесты.
        if (app()->environment('testing')) {
            $rawInput = '15, 23, 1, -234, 400, 92';
        } else {
            $items = [];
            for ($i = 0; $i < 200_000; $i++) {
                $items[] = mt_rand(-1_000_000, 1_000_000);
            }
            $rawInput = implode(', ', $items);
        }

        return view('task1', $this->viewData(
            rawInput: $rawInput,
            algorithm: 'bubble',
            hasRun: false,
            error: null,
            sortedResult: [],
            elapsed: 0.0,
        ));
    }

    /**
     * Обработка формы «Сортировать»: сортировка данных из textarea.
     */
    public function sort(Request $request): View
    {
        ini_set('max_execution_time', '1800');

        $rawInput = (string) $request->input('numbers', '');
        $algorithm = (string) $request->input('algorithm', 'bubble');
        $items = $this->parseUserArray($rawInput);
        $error = null;
        $hasRun = false;
        $elapsed = 0.0;
        $sortedResult = [];

        if ($items === []) {
            $error = 'Не удалось распознать числа. Введите массив чисел через запятую или пробел.';
            $hasRun = true;
        } elseif (! in_array($algorithm, ['bubble', 'prod'], true)) {
            $error = 'Выбран некорректный режим сортировки.';
            $hasRun = true;
        } else {
            $hasRun = true;
            $start = microtime(true);
            $this->sortService->sort($items, $algorithm === 'bubble');
            $elapsed = microtime(true) - $start;
            $sortedResult = $items;
        }

        return view('task1', $this->viewData(
            rawInput: $rawInput,
            algorithm: $algorithm,
            hasRun: $hasRun,
            error: $error,
            sortedResult: $sortedResult,
            elapsed: $elapsed,
        ));
    }

    /**
     * Парсит строку из textarea в массив чисел.
     *
     * @return list<int|float>
     */
    private function parseUserArray(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/u', trim($raw)) ?: [];
        $numbers = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (is_numeric($part)) {
                $numbers[] = (float) $part;
            }
        }

        return $numbers;
    }

    /**
     * Общие данные для шаблона: путь к файлу, подсветка кода, состояние формы.
     *
     * @param list<int|float> $sortedResult
     * @return array<string, mixed>
     */
    private function viewData(
        string $rawInput,
        string $algorithm,
        bool $hasRun,
        ?string $error,
        array $sortedResult,
        float $elapsed,
    ): array {
        $sourcePath = app_path('Services/SortService.php');
        // Путь от корня репозитория (testhokkey/).
        $repoRoot = str_replace('\\', '/', dirname(base_path()));
        $normalizedSource = str_replace('\\', '/', $sourcePath);
        $sourceRelativePath = str_starts_with($normalizedSource, $repoRoot.'/')
            ? substr($normalizedSource, strlen($repoRoot) + 1)
            : 'laravel-app/app/Services/SortService.php';

        $sourceCode = is_file($sourcePath) ? (string) file_get_contents($sourcePath) : '';
        $highlightedCode = $sourceCode !== ''
            ? highlight_string($sourceCode, true)
            : '<pre>Код не найден</pre>';

        return compact(
            'rawInput',
            'algorithm',
            'hasRun',
            'error',
            'sortedResult',
            'elapsed',
            'highlightedCode',
            'sourceRelativePath',
        );
    }
}
