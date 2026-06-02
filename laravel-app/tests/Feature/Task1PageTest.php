<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature-тесты страницы сортировки (задание 1).
 */
class Task1PageTest extends TestCase
{
    #[Test]
    public function task1_page_renders_sort_form(): void
    {
        $this->get('/task1')
            ->assertOk()
            ->assertSee('Пузырек (демо)', false)
            ->assertSee('Продакшен (нативный sort)', false);
    }

    #[Test]
    public function task1_sorts_submitted_numbers_with_bubble(): void
    {
        $this->post('/task1', [
            'numbers' => '5, 1, 3',
            'algorithm' => 'bubble',
        ])
            ->assertOk()
            ->assertSee('1, 3, 5', false)
            ->assertSee('Количество элементов:', false);
    }

    #[Test]
    public function task1_rejects_invalid_algorithm(): void
    {
        $this->post('/task1', [
            'numbers' => '1, 2',
            'algorithm' => 'invalid',
        ])
            ->assertOk()
            ->assertSee('некорректный режим', false);
    }
}
