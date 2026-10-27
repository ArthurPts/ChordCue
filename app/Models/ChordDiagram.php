<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChordDiagram extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'chords_id',
        'file_path',
    ];

    /**
     * Chord pemilik diagram ini.
     */
    public function chord(): BelongsTo
    {
        return $this->belongsTo(Chord::class, 'chords_id');
    }
}
