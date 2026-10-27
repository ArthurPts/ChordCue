<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChordPlacement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lyric_line_id',
        'chord_id',
        'position',
        'start_beat',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'start_beat' => 'integer',
        ];
    }

    /**
     * Baris lirik tempat chord ditempatkan.
     */
    public function lyricLine(): BelongsTo
    {
        return $this->belongsTo(LyricLine::class, 'lyric_line_id');
    }

    /**
     * Chord yang dimainkan.
     */
    public function chord(): BelongsTo
    {
        return $this->belongsTo(Chord::class, 'chord_id');
    }
}
