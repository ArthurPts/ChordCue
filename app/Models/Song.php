<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'artist_id',
        'default_key',
        'bpm',
        'time_signature_numerator',
        'time_signature_denominator',
        'file_path',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'bpm' => 'integer',
            'time_signature_numerator' => 'integer',
            'time_signature_denominator' => 'integer',
        ];
    }

    /**
     * Pemilik lagu (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Artis dari lagu.
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * Kategori yang terhubung dengan lagu (mood, location, genre).
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_song');
    }

    /**
     * Bagian-bagian lagu (verse, chorus, etc).
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SongSection::class);
    }
}
