<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    #[Test]
    public function home_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Контакты', false);
    }
}
