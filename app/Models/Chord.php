<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Chord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'pronunciation',
        'file_path',
    ];

    /**
     * Penempatan chord pada baris lirik.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class);
    }

    /**
     * Diagram visual chord.
     */
    public function diagrams(): HasMany
    {
        return $this->hasMany(ChordDiagram::class, 'chord_id');
    }

    /**
     * URL file audio pelafalan chord untuk player.
     * Mengembalikan null jika file belum digenerate atau belum siap di disk.
     *
     * Fungsi ini digunakan pada:
     * - SongTimelineController::show (GET /songs/{song}/timeline)
     * - resources/views/songs/player.blade.php
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $diskName = config('tts.disk', 'public');
        $disk = Storage::disk($diskName);

        if ($disk->exists($this->file_path)) {
            return $disk->url($this->file_path);
        }

        return null;
    }

    /**
     * URL diagram gambar jika tersedia, fallback ke No_Image_Available.jpg.
     * Mengakses file via Storage facade sesuai aturan CHORDCUE_RULES.md §7.
     */
    public function getDiagramUrlAttribute(): string
    {
        $diagram = $this->diagrams->first();
        if ($diagram && $diagram->file_path) {
            if (str_starts_with($diagram->file_path, 'http')) {
                return $diagram->file_path;
            }
            return Storage::disk('public')->url($diagram->file_path);
        }
        return Storage::disk('public')->url('diagrams/No_Image_Available.jpg');
    }

    /**
     * Menghapus record chord beserta file audio dan seluruh file diagramnya.
     * Sesuai aturan CHORDCUE_RULES.md §5.
     *
     * Fungsi ini digunakan pada:
     * - ChordController::destroy
     */
    public function deleteWithFiles(): void
    {
        // Tolak jika masih dipakai oleh placement lagu
        if ($this->placements()->exists()) {
            throw new DomainException('Chord tidak dapat dihapus karena masih digunakan pada lagu.');
        }

        foreach ($this->diagrams as $diagram) {
            $diagram->deleteWithFiles();
        }

        $diskName = config('tts.disk', 'public');
        if ($this->file_path && Storage::disk($diskName)->exists($this->file_path)) {
            Storage::disk($diskName)->delete($this->file_path);
        }

        $this->delete();
    }
}
