<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChordDiagram extends Model
{
    use HasFactory;

    public const DEFAULT_PLACEHOLDER_PATH = 'diagrams/No_Image_Available.jpg';

    public $timestamps = false;

    protected $fillable = [
        'chord_id',
        'file_path',
    ];

    /**
     * Chord pemilik diagram ini.
     */
    public function chord(): BelongsTo
    {
        return $this->belongsTo(Chord::class, 'chord_id');
    }

    /**
     * Menghapus record diagram beserta file gambar fisiknya di storage.
     * File placeholder bawaan tidak ikut dihapus agar tetap tersedia bagi chord lain.
     * Sesuai aturan CHORDCUE_RULES.md §5.
     *
     * Fungsi ini digunakan pada:
     * - Chord::deleteWithFiles()
     * - ChordController::update()
     */
    public function deleteWithFiles(): void
    {
        if (
            $this->file_path
            && $this->file_path !== self::DEFAULT_PLACEHOLDER_PATH
            && Storage::disk('public')->exists($this->file_path)
        ) {
            Storage::disk('public')->delete($this->file_path);
        }

        $this->delete();
    }
}
