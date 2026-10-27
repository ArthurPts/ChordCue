<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(ChordDiagram::class, 'chords_id');
    }
}
