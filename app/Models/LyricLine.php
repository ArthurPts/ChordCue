<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LyricLine extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'song_section_id',
        'line_number',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
        ];
    }

    /**
     * Section lagu dari baris lirik ini.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(SongSection::class, 'song_section_id');
    }

    /**
     * Chord placements yang menempel pada baris lirik ini.
     */
    public function chordPlacements(): HasMany
    {
        return $this->hasMany(ChordPlacement::class, 'lyric_line_id');
    }
}
