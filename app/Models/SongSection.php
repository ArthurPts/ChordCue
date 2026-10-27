<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SongSection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'song_id',
        'name',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }

    /**
     * Lagu yang memuat section ini.
     */
    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Baris-baris lirik dalam section ini.
     */
    public function lyricLines(): HasMany
    {
        return $this->hasMany(LyricLine::class, 'section_id');
    }
}
