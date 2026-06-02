<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->smallInteger('year_start');
            $table->smallInteger('year_end');
            $table->timestamps();
        });

        // CHECK только в PostgreSQL (в SQLite тестах ограничение не требуется).
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE seasons ADD CONSTRAINT chk_years CHECK (year_end >= year_start)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
