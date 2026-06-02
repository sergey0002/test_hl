<?php

namespace Tests\Unit;

use App\Jobs\ProcessCsvExport;
use App\Models\ExportStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Job экспорта CSV (задание 3) — с QUEUE_CONNECTION=sync в phpunit.xml.
 */
class ProcessCsvExportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function job_writes_csv_and_marks_export_completed(): void
    {
        User::factory()->count(3)->create();

        $export = ExportStatus::create([
            'status' => 'pending',
            'processed' => 0,
            'total' => 0,
        ]);

        $job = new ProcessCsvExport($export->id);
        $job->handle();

        $export->refresh();

        $this->assertSame('completed', $export->status);
        $this->assertSame(3, $export->processed);
        $this->assertSame(3, $export->total);
        $this->assertFileExists($export->file_path);

        $content = file_get_contents($export->file_path);
        $this->assertStringContainsString('Фамилия;Имя;Телефон;E-mail', $content);
    }
}
