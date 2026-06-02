<?php

namespace App\Services;

/**
 * Сервис сортировки числовых массивов.
 *
 * Пузырьковая сортировка: O(n²) по времени, O(1) по доп. памяти.
 * На 200 000 элементов — очень долго; для production используем sort().
 *
 * Пузырёк оставлен как демонстрация алгоритма с оптимизациями (см. bubbleSort).
 */
final class SortService
{
    /**
     * Сортирует массив по возрастанию.
     *
     * @param array<int|float> $items
     * @param bool $forceBubble true — пузырёк (демо), false — нативный sort()
     */
    public function sort(array &$items, bool $forceBubble = false): void
    {
        $count = count($items);
        if ($count < 2) {
            return;
        }

        if ($forceBubble) {
            $this->bubbleSort($items, $count);

            return;
        }

        // Production-путь: нативная сортировка PHP (C), O(n log n).
        sort($items, SORT_NUMERIC);
    }

    /**
     * Оптимизированная пузырьковая сортировка.
     *
     * Оптимизации:
     * 1. Отслеживание последнего обмена — сокращает правую границу.
     * 2. Ранний выход, если за проход не было обменов.
     * 3. Работа по ссылке — без копирования массива.
     *
     * @param array<int|float> $items
     */
    private function bubbleSort(array &$items, int $count): void
    {
        $rightBorder = $count - 1;

        while ($rightBorder > 0) {
            $lastSwapIndex = 0;

            for ($j = 0; $j < $rightBorder; $j++) {
                if ($items[$j] > $items[$j + 1]) {
                    $tmp = $items[$j];
                    $items[$j] = $items[$j + 1];
                    $items[$j + 1] = $tmp;
                    $lastSwapIndex = $j;
                }
            }

            // Если обменов не было, массив уже отсортирован.
            if ($lastSwapIndex === 0) {
                break;
            }

            $rightBorder = $lastSwapIndex;
        }
    }
}
