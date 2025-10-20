<?php

use App\Models\Track;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('track_has_duplicates', function (Blueprint $table) {
            $table->foreignIdFor(Track::class, 'original_id');
            $table->foreignIdFor(Track::class, 'duplicate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_has_duplicates');
    }
};
