<?php

namespace Tests\Unit;

use App\Services\SortService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit-тесты сортировки (задание 1).
 */
class SortServiceTest extends TestCase
{
    private SortService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SortService;
    }

    #[Test]
    public function bubble_sort_orders_array_ascending(): void
    {
        $items = [3.0, 1.0, 2.0];
        $this->service->sort($items, forceBubble: true);

        $this->assertSame([1.0, 2.0, 3.0], $items);
    }

    #[Test]
    public function native_sort_orders_array_ascending(): void
    {
        $items = [15, 23, 1, -234, 400, 92];
        $this->service->sort($items, forceBubble: false);

        $this->assertSame([-234, 1, 15, 23, 92, 400], $items);
    }

    #[Test]
    public function empty_and_single_element_arrays_are_unchanged(): void
    {
        $empty = [];
        $this->service->sort($empty, forceBubble: true);
        $this->assertSame([], $empty);

        $single = [42];
        $this->service->sort($single, forceBubble: true);
        $this->assertSame([42], $single);
    }

    #[Test]
    public function bubble_sort_exits_early_on_sorted_input(): void
    {
        $items = [1, 2, 3, 4, 5];
        $this->service->sort($items, forceBubble: true);

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }
}
