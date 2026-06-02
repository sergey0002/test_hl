<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->check('year_end >= year_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
