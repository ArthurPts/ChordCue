<?php

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
        Schema::create('chord_diagrams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chords_id')->constrained('chords')->cascadeOnDelete();
            $table->string('file_path', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chord_diagrams');
    }
};
