<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

/**
 * Kebijakan otorisasi hak akses untuk model Song.
 * Mengacu pada spesifikasi sistem §7.7.
 *
 * Fungsi ini digunakan pada:
 * - SongTimelineController::show (GET /songs/{song}/timeline)
 * - SongAudioController::updateOffset (PATCH /songs/{song}/backing-track/offset)
 * - SongController::show (GET /songs/{song})
 * - SongController::edit & update
 * - SongController::destroy
 */
class SongPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat lagu dan data timeline-nya.
     * Publik dapat diakses oleh siapa saja (termasuk Guest),
     * privat hanya dapat diakses oleh pemilik atau admin.
     *
     * @param User|null $user Pengguna yang sedang login (bisa null untuk guest)
     * @param Song $song Model lagu yang diakses
     * @return bool
     */
    public function view(?User $user, Song $song): bool
    {
        // Lagu publik terbuka untuk semua orang (termasuk guest)
        if ($song->visibility === 'public') {
            return true;
        }

        // Lagu privat membutuhkan otentikasi
        if ($user === null) {
            return false;
        }

        return $user->id === $song->user_id || $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui lagu, lirik, chord, dan offset backing track.
     * Hanya pemilik lagu atau admin yang diizinkan.
     *
     * @param User $user
     * @param Song $song
     * @return bool
     */
    public function update(User $user, Song $song): bool
    {
        return $user->id === $song->user_id || $user->role === 'admin';
    }

    /**
     * Menentukan apakah pengguna dapat menghapus lagu.
     * Hanya pemilik lagu atau admin yang diizinkan.
     *
     * @param User $user
     * @param Song $song
     * @return bool
     */
    public function delete(User $user, Song $song): bool
    {
        return $user->id === $song->user_id || $user->role === 'admin';
    }
}

