<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Статус фоновой выгрузки CSV (очередь + прогресс для AJAX).
 */
class ExportStatus extends Model
{
    protected $table = 'exports';

    protected $fillable = [
        'status',
        'processed',
        'total',
        'file_path',
        'error_message',
    ];
}
