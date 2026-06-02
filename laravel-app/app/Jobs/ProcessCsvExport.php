<?php

namespace App\Jobs;

use App\Models\ExportStatus;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Фоновая выгрузка users в CSV батчами (без загрузки всей таблицы в память).
 */
class ProcessCsvExport implements ShouldQueue
{
    use Queueable;

    /** Без лимита: 500k строк могут обрабатываться несколько минут. */
    public int $timeout = 0;

    /** Шаг чтения users из БД за один проход chunkById. */
    public const CHUNK_SIZE = 5000;

    /** Как часто обновлять processed в таблице exports. */
    public const PROGRESS_EVERY = 5000;

    public function __construct(private readonly int $exportId) {}

    /**
     * Пишет CSV на диск и обновляет прогресс в exports.
     */
    public function handle(): void
    {
        $export = ExportStatus::findOrFail($this->exportId);
        $filePath = storage_path('app/exports/users_export_'.$this->exportId.'.csv');
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $total = User::count();
        $export->update([
            'status' => 'processing',
            'total' => $total,
            'processed' => 0,
        ]);

        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Не удалось открыть CSV файл: '.$filePath);
        }

        fputcsv($handle, ['Фамилия', 'Имя', 'Телефон', 'E-mail'], ';');

        $processed = 0;

        User::query()
            ->select(['id', 'last_name', 'first_name', 'phone', 'email'])
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use ($handle, $export, &$processed) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->last_name,
                        $user->first_name,
                        $user->phone,
                        $user->email,
                    ], ';');
                    $processed++;
                }

                if ($processed % self::PROGRESS_EVERY === 0) {
                    $export->update(['processed' => $processed]);
                }
            });

        fclose($handle);

        $export->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'processed' => $processed,
            'total' => $total,
        ]);
    }

    /**
     * Если job упал — фиксируем ошибку в БД (видно в UI polling).
     */
    public function failed(?Throwable $exception): void
    {
        $export = ExportStatus::find($this->exportId);
        if ($export === null) {
            return;
        }

        $export->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Неизвестная ошибка воркера',
        ]);
    }
}
