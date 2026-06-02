<?php

namespace Tests\Feature;

use App\Models\ExportStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * API экспорта CSV (задание 3). В phpunit.xml: QUEUE_CONNECTION=sync.
 */
class ExportApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function export_page_shows_batch_settings(): void
    {
        $this->get('/export')
            ->assertOk()
            ->assertSee('CHUNK_SIZE', false)
            ->assertSee('ProcessCsvExport.php', false);
    }

    #[Test]
    public function export_start_returns_total_users_count(): void
    {
        User::factory()->count(5)->create();

        $this->postJson('/api/export/start')
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
                'total' => 5,
                'processed' => 0,
            ])
            ->assertJsonStructure(['export_id']);
    }

    #[Test]
    public function export_completes_via_sync_queue_and_csv_is_downloadable(): void
    {
        User::factory()->count(4)->create();

        $exportId = $this->postJson('/api/export/start')
            ->json('export_id');

        $this->getJson('/api/export/status/'.$exportId)
            ->assertOk()
            ->assertJson([
                'status' => 'completed',
                'processed' => 4,
                'total' => 4,
                'progress' => 100,
            ]);

        $this->get('/api/export/download/'.$exportId)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function export_job_is_dispatched_when_queue_is_faked(): void
    {
        Queue::fake();
        User::factory()->create();

        $this->postJson('/api/export/start')->assertOk();

        Queue::assertPushed(\App\Jobs\ProcessCsvExport::class);

        $this->assertSame(1, ExportStatus::count());
    }
}
