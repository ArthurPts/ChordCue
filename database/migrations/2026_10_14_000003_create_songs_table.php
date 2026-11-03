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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->foreignId('artist_id')->constrained('artists')->cascadeOnDelete();
            $table->string('default_key', 45)->nullable();
            $table->smallInteger('bpm')->unsigned()->default(80);
            $table->tinyInteger('time_signature_numerator')->unsigned()->default(4);
            $table->tinyInteger('time_signature_denominator')->unsigned()->default(4);
            $table->string('backing_track_path', 255)->nullable();
            $table->decimal('backing_track_offset', 6, 3)->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
