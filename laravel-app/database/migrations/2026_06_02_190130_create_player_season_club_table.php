<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_season_club', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->smallInteger('player_number');
            $table->timestamps();

            $table->unique(['player_id', 'season_id', 'club_id'], 'uq_player_season_club');
            $table->index('player_id', 'idx_psc_player');
            $table->index('season_id', 'idx_psc_season');
            $table->index('club_id', 'idx_psc_club');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_season_club');
    }
};
