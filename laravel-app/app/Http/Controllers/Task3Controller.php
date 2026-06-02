<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvExport;
use App\Models\ExportStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Задание 3: экспорт пользователей в CSV через очередь Redis.
 */
class Task3Controller extends Controller
{
    /** Страница с кнопкой запуска и AJAX-опросом статуса. */
    public function index(): View
    {
        return view('task3', [
            'chunkSize' => ProcessCsvExport::CHUNK_SIZE,
            'progressEvery' => ProcessCsvExport::PROGRESS_EVERY,
            'jobSourcePath' => 'laravel-app/app/Jobs/ProcessCsvExport.php',
        ]);
    }

    /**
     * Создаёт задачу экспорта и ставит job в очередь.
     */
    public function startExport(): JsonResponse
    {
        // total сразу — в UI не будет 0/0, пока job ждёт воркер.
        $total = User::count();

        $export = ExportStatus::create([
            'status' => 'pending',
            'processed' => 0,
            'total' => $total,
        ]);

        ProcessCsvExport::dispatch($export->id);

        return response()->json([
            'export_id' => $export->id,
            'status' => $export->status,
            'processed' => $export->processed,
            'total' => $export->total,
            'progress' => 0,
        ]);
    }

    /**
     * Текущий прогресс экспорта для polling на фронте.
     */
    public function getStatus(int $id): JsonResponse
    {
        $export = ExportStatus::findOrFail($id);
        $progress = $export->total > 0
            ? round(($export->processed / $export->total) * 100, 2)
            : 0;

        return response()->json([
            'id' => $export->id,
            'status' => $export->status,
            'processed' => $export->processed,
            'total' => $export->total,
            'progress' => $progress,
            'download_url' => $export->status === 'completed'
                ? url('/api/export/download/'.$export->id)
                : null,
            'error' => $export->error_message,
        ]);
    }

    /**
     * Отдаёт готовый CSV-файл после status=completed.
     */
    public function download(int $id): BinaryFileResponse
    {
        $export = ExportStatus::findOrFail($id);
        if ($export->status !== 'completed' || ! $export->file_path || ! is_file($export->file_path)) {
            abort(404, 'Файл экспорта недоступен');
        }

        return Response::download($export->file_path, basename($export->file_path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
