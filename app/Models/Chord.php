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

    /**
     * Data penempatan jari gitar untuk visualisasi diagram SVG standar.
     */
    public function getFretDataAttribute(): array
    {
        $map = [
            'C' => ['frets' => ['x', 3, 2, 0, 1, 0]],
            'Cm' => ['frets' => ['x', 3, 5, 5, 4, 3], 'baseFret' => 3, 'barre' => 3],
            'C7' => ['frets' => ['x', 3, 2, 3, 1, 0]],
            'Cadd9' => ['frets' => ['x', 3, 2, 0, 3, 0]],
            'Cmaj7' => ['frets' => ['x', 3, 2, 0, 0, 0]],
            'Csus4' => ['frets' => ['x', 3, 3, 0, 1, 1]],
            'C#m' => ['frets' => ['x', 4, 6, 6, 5, 4], 'baseFret' => 4, 'barre' => 4],

            'D' => ['frets' => ['x', 'x', 0, 2, 3, 2]],
            'Dm' => ['frets' => ['x', 'x', 0, 2, 3, 1]],
            'D7' => ['frets' => ['x', 'x', 0, 2, 1, 2]],
            'Dm7' => ['frets' => ['x', 'x', 0, 2, 1, 1]],
            'Dadd9' => ['frets' => ['x', 'x', 0, 2, 3, 0]],
            'Dsus4' => ['frets' => ['x', 'x', 0, 2, 3, 3]],

            'E' => ['frets' => [0, 2, 2, 1, 0, 0]],
            'Em' => ['frets' => [0, 2, 2, 0, 0, 0]],
            'E7' => ['frets' => [0, 2, 0, 1, 0, 0]],
            'Em7' => ['frets' => [0, 2, 0, 0, 0, 0]],
            'Eb' => ['frets' => ['x', 6, 8, 8, 8, 6], 'baseFret' => 6, 'barre' => 6],

            'F' => ['frets' => [1, 3, 3, 2, 1, 1], 'barre' => 1],
            'Fm' => ['frets' => [1, 3, 3, 1, 1, 1], 'barre' => 1],
            'F7' => ['frets' => [1, 3, 1, 2, 1, 1], 'barre' => 1],
            'Fmaj7' => ['frets' => ['x', 'x', 3, 2, 1, 0]],
            'F#m' => ['frets' => [2, 4, 4, 2, 2, 2], 'baseFret' => 2, 'barre' => 2],
            'F#7' => ['frets' => [2, 4, 2, 3, 2, 2], 'baseFret' => 2, 'barre' => 2],

            'G' => ['frets' => [3, 2, 0, 0, 0, 3]],
            'Gm' => ['frets' => [3, 5, 5, 3, 3, 3], 'baseFret' => 3, 'barre' => 3],
            'G7' => ['frets' => [3, 2, 0, 0, 0, 1]],
            'Gadd9' => ['frets' => [3, 2, 0, 2, 0, 3]],
            'Gmaj7' => ['frets' => [3, 2, 0, 0, 0, 2]],
            'Gsus4' => ['frets' => [3, 3, 0, 0, 1, 3]],
            'G#m' => ['frets' => [4, 6, 6, 4, 4, 4], 'baseFret' => 4, 'barre' => 4],

            'A' => ['frets' => ['x', 0, 2, 2, 2, 0]],
            'Am' => ['frets' => ['x', 0, 2, 2, 1, 0]],
            'A7' => ['frets' => ['x', 0, 2, 0, 2, 0]],
            'Am7' => ['frets' => ['x', 0, 2, 0, 1, 0]],
            'Asus4' => ['frets' => ['x', 0, 2, 2, 3, 0]],
            'Ab' => ['frets' => [4, 6, 6, 5, 4, 4], 'baseFret' => 4, 'barre' => 4],

            'B' => ['frets' => ['x', 2, 4, 4, 4, 2], 'baseFret' => 2, 'barre' => 2],
            'Bm' => ['frets' => ['x', 2, 4, 4, 3, 2], 'baseFret' => 2, 'barre' => 2],
            'B7' => ['frets' => ['x', 2, 1, 2, 0, 2]],
            'Bm7' => ['frets' => ['x', 2, 0, 2, 0, 2]],
            'Bb' => ['frets' => ['x', 1, 3, 3, 3, 1], 'barre' => 1],
            'Bdim' => ['frets' => ['x', 2, 3, 4, 3, 'x']],
        ];

        return $map[$this->name] ?? ['frets' => ['x', 3, 2, 0, 1, 0]];
    }
}
