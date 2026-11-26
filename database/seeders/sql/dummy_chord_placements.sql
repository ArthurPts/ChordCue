-- ============================================================================
-- Dummy SQL: chord_placements untuk 2 Lagu Pilihan (Sempurna & Dummy Waltz)
-- ============================================================================
-- Aturan yang dipatuhi (SPESIFIKASI_SISTEM_ChordCue.md):
-- 1. Idempoten: Dibungkus transaksi; membersihkan placement lama HANYA untuk
--    2 lagu target sebelum memasukkan data baru.
-- 2. Pembaruan default_key spesifik: Mengisi songs.default_key = 'C' untuk
--    lagu 'Sempurna' ('Andra and the Backbone') HANYA bila bernilai NULL.
-- 3. Keunikan koordinat:
--    - start_beat (1-based global) unik per lagu lintas section & baris.
--    - position (0-based indeks huruf) unik per lyric_line_id dan memenuhi
--      0 <= position <= CHAR_LENGTH(content) - 1 tanpa tumpang tindih label.
-- 4. Variasi status untuk pengembangan Player, Simple Editor, & Beat Editor:
--    - Sempurna (4/4, BPM 80): 100% placement Lengkap (60 items, memakai seluruh 20 chord).
--    - Dummy Waltz (3/4, BPM 90): Intro..Chorus 2 Lengkap + sampel lengkap &
--      tidak lengkap di Bridge & Outro (40 Lengkap, 2 hanya position,
--      2 hanya start_beat, 1 kosong keduanya; memakai seluruh 20 chord).
-- ============================================================================

START TRANSACTION;

-- 1. Set default_key = 'C' untuk Sempurna hanya jika masih NULL
UPDATE songs
SET default_key = 'C'
WHERE title = 'Sempurna'
  AND artist_id IN (
      SELECT id FROM artists WHERE name = 'Andra and the Backbone'
  )
  AND default_key IS NULL;

-- 2. Hapus chord_placements lama HANYA milik 2 lagu target agar aman diulang
DELETE FROM chord_placements
WHERE lyric_line_id IN (
    SELECT ll.id
    FROM lyric_lines ll
    INNER JOIN song_sections ss ON ss.id = ll.song_section_id
    INNER JOIN songs s ON s.id = ss.song_id
    INNER JOIN artists a ON a.id = s.artist_id
    WHERE (s.title = 'Sempurna' AND a.name = 'Andra and the Backbone')
       OR (s.title = 'Dummy Waltz' AND a.name = 'Duo Dummy')
);

-- 3. Insert chord_placements untuk Lagu 1: Sempurna (4/4, BPM 80, Key C)
--    Seluruh 60 placement berstatus Lengkap (position & start_beat terisi)
INSERT INTO chord_placements (lyric_line_id, chord_id, position, start_beat)
SELECT ll.id, c.id, v.position, v.start_beat
FROM (
    -- [Section 1] Verse 1 (Bar 1 - Bar 8, Beat 1 - 32)
    SELECT 1 AS sec_seq, 1 AS line_no, 'A' AS chord_name, 0 AS position, 1 AS start_beat
    UNION ALL SELECT 1, 1, 'Am', 11, 5
    UNION ALL SELECT 1, 2, 'Bb', 3, 9
    UNION ALL SELECT 1, 2, 'Bdim', 21, 13
    UNION ALL SELECT 1, 3, 'Bm7b5', 0, 17
    UNION ALL SELECT 1, 3, 'Cadd9', 12, 21
    UNION ALL SELECT 1, 4, 'Caug', 5, 25
    UNION ALL SELECT 1, 4, 'Cdim7', 12, 29

    -- [Section 2] Verse 2 (Bar 9 - Bar 16, Beat 33 - 64)
    UNION ALL SELECT 2, 1, 'Csus2', 3, 33
    UNION ALL SELECT 2, 1, 'Csus4', 10, 37
    UNION ALL SELECT 2, 2, 'C7', 1, 41
    UNION ALL SELECT 2, 2, 'Cmaj7', 27, 45
    UNION ALL SELECT 2, 3, 'Cm7', 0, 49
    UNION ALL SELECT 2, 3, 'Cmaj9', 12, 53
    UNION ALL SELECT 2, 4, 'C7#5', 0, 57
    UNION ALL SELECT 2, 4, 'C7b9', 14, 61

    -- [Section 3] Pre-Chorus (Bar 17 - Bar 22, Beat 65 - 88)
    UNION ALL SELECT 3, 1, 'D7sus4', 0, 65
    UNION ALL SELECT 3, 1, 'E/G#', 25, 69
    UNION ALL SELECT 3, 2, 'Fmaj7', 0, 73
    UNION ALL SELECT 3, 2, 'G13', 24, 77
    UNION ALL SELECT 3, 3, 'A', 6, 81
    UNION ALL SELECT 3, 3, 'Am', 25, 85

    -- [Section 4] Chorus (Bar 23 - Bar 27, Beat 89 - 108)
    UNION ALL SELECT 4, 1, 'Bb', 0, 89
    UNION ALL SELECT 4, 1, 'Bdim', 11, 91
    UNION ALL SELECT 4, 2, 'Bm7b5', 0, 93
    UNION ALL SELECT 4, 2, 'Cadd9', 11, 95
    UNION ALL SELECT 4, 3, 'Caug', 0, 97
    UNION ALL SELECT 4, 3, 'Cdim7', 11, 99
    UNION ALL SELECT 4, 4, 'Csus2', 0, 101
    UNION ALL SELECT 4, 4, 'Csus4', 9, 103
    UNION ALL SELECT 4, 5, 'C7', 4, 105
    UNION ALL SELECT 4, 5, 'Cmaj7', 17, 107

    -- [Section 5] Post-Chorus (Bar 28 - Bar 29, Beat 109 - 116)
    UNION ALL SELECT 5, 1, 'Cm7', 0, 109
    UNION ALL SELECT 5, 2, 'Cmaj9', 0, 113

    -- [Section 6] Verse 2 (Bar 30 - Bar 37, Beat 117 - 148)
    UNION ALL SELECT 6, 1, 'C7#5', 0, 117
    UNION ALL SELECT 6, 1, 'C7b9', 12, 121
    UNION ALL SELECT 6, 2, 'D7sus4', 5, 125
    UNION ALL SELECT 6, 2, 'E/G#', 22, 129
    UNION ALL SELECT 6, 3, 'Fmaj7', 0, 133
    UNION ALL SELECT 6, 3, 'G13', 13, 137
    UNION ALL SELECT 6, 4, 'A', 4, 141
    UNION ALL SELECT 6, 4, 'Am', 16, 145

    -- [Section 7] Pre-Chorus (Bar 38 - Bar 43, Beat 149 - 172)
    UNION ALL SELECT 7, 1, 'Bb', 0, 149
    UNION ALL SELECT 7, 1, 'Bdim', 25, 153
    UNION ALL SELECT 7, 2, 'Bm7b5', 0, 157
    UNION ALL SELECT 7, 2, 'Cadd9', 24, 161
    UNION ALL SELECT 7, 3, 'Caug', 6, 165
    UNION ALL SELECT 7, 3, 'Cdim7', 25, 169

    -- [Section 8] Chorus (Bar 44 - Bar 48, Beat 173 - 192)
    UNION ALL SELECT 8, 1, 'Csus2', 0, 173
    UNION ALL SELECT 8, 1, 'Csus4', 11, 175
    UNION ALL SELECT 8, 2, 'C7', 0, 177
    UNION ALL SELECT 8, 2, 'Cmaj7', 11, 179
    UNION ALL SELECT 8, 3, 'Cm7', 0, 181
    UNION ALL SELECT 8, 3, 'Cmaj9', 11, 183
    UNION ALL SELECT 8, 4, 'C7#5', 0, 185
    UNION ALL SELECT 8, 4, 'C7b9', 9, 187
    UNION ALL SELECT 8, 5, 'D7sus4', 4, 189
    UNION ALL SELECT 8, 5, 'E/G#', 17, 191

    -- [Section 9] Post-Chorus (Bar 49 - Bar 50, Beat 193 - 200)
    UNION ALL SELECT 9, 1, 'Fmaj7', 0, 193
    UNION ALL SELECT 9, 2, 'G13', 0, 197
) AS v
INNER JOIN songs s ON s.title = 'Sempurna'
INNER JOIN artists a ON a.id = s.artist_id AND a.name = 'Andra and the Backbone'
INNER JOIN song_sections ss ON ss.song_id = s.id AND ss.sequence = v.sec_seq
INNER JOIN lyric_lines ll ON ll.song_section_id = ss.id AND ll.line_number = v.line_no
INNER JOIN chords c ON c.name = v.chord_name;

-- 4. Insert chord_placements untuk Lagu 2: Dummy Waltz (3/4, BPM 90, Key C)
--    Intro..Chorus 2 Lengkap; Bridge & Outro memuat variasi 4 status placement
INSERT INTO chord_placements (lyric_line_id, chord_id, position, start_beat)
SELECT ll.id, c.id, v.position, v.start_beat
FROM (
    -- [Section 1] Intro (3/4: Bar 1 - Bar 4, Beat 1 - 12) - Lengkap
    SELECT 1 AS sec_seq, 1 AS line_no, 'A' AS chord_name, 0 AS position, 1 AS start_beat
    UNION ALL SELECT 1, 1, 'Am', 13, 4
    UNION ALL SELECT 1, 2, 'Bb', 0, 7
    UNION ALL SELECT 1, 2, 'Bdim', 13, 10

    -- [Section 2] Verse 1 (3/4: Bar 5 - Bar 12, Beat 13 - 36) - Lengkap
    UNION ALL SELECT 2, 1, 'Bm7b5', 0, 13
    UNION ALL SELECT 2, 1, 'Cadd9', 13, 16
    UNION ALL SELECT 2, 2, 'Caug', 0, 19
    UNION ALL SELECT 2, 2, 'Cdim7', 13, 22
    UNION ALL SELECT 2, 3, 'Csus2', 0, 25
    UNION ALL SELECT 2, 3, 'Csus4', 13, 28
    UNION ALL SELECT 2, 4, 'C7', 0, 31
    UNION ALL SELECT 2, 4, 'Cmaj7', 13, 34

    -- [Section 3] Chorus 1 (3/4: Bar 13 - Bar 16, Beat 37 - 48) - Lengkap
    UNION ALL SELECT 3, 1, 'Cm7', 0, 37
    UNION ALL SELECT 3, 1, 'Cmaj9', 13, 39
    UNION ALL SELECT 3, 2, 'C7#5', 0, 40
    UNION ALL SELECT 3, 2, 'C7b9', 13, 42
    UNION ALL SELECT 3, 3, 'D7sus4', 0, 43
    UNION ALL SELECT 3, 3, 'E/G#', 13, 45
    UNION ALL SELECT 3, 4, 'Fmaj7', 0, 46
    UNION ALL SELECT 3, 4, 'G13', 13, 48

    -- [Section 4] Verse 2 (3/4: Bar 17 - Bar 24, Beat 49 - 72) - Lengkap
    UNION ALL SELECT 4, 1, 'A', 0, 49
    UNION ALL SELECT 4, 1, 'Am', 13, 52
    UNION ALL SELECT 4, 2, 'Bb', 0, 55
    UNION ALL SELECT 4, 2, 'Bdim', 13, 58
    UNION ALL SELECT 4, 3, 'Bm7b5', 0, 61
    UNION ALL SELECT 4, 3, 'Cadd9', 13, 64
    UNION ALL SELECT 4, 4, 'Caug', 0, 67
    UNION ALL SELECT 4, 4, 'Cdim7', 13, 70

    -- [Section 5] Chorus 2 (3/4: Bar 25 - Bar 28, Beat 73 - 84) - Lengkap
    UNION ALL SELECT 5, 1, 'Csus2', 0, 73
    UNION ALL SELECT 5, 1, 'Csus4', 13, 75
    UNION ALL SELECT 5, 2, 'C7', 0, 76
    UNION ALL SELECT 5, 2, 'Cmaj7', 13, 78
    UNION ALL SELECT 5, 3, 'Cm7', 0, 79
    UNION ALL SELECT 5, 3, 'Cmaj9', 13, 81
    UNION ALL SELECT 5, 4, 'C7#5', 0, 82
    UNION ALL SELECT 5, 4, 'C7b9', 13, 84

    -- [Section 6] Bridge (Campuran Lengkap & Tidak Lengkap untuk uji Simple/Beat Editor)
    -- Baris 1: Lengkap (Bar 29 - Bar 30)
    UNION ALL SELECT 6, 1, 'D7sus4', 0, 85
    UNION ALL SELECT 6, 1, 'E/G#', 13, 88
    -- Baris 2: Hanya position (start_beat = NULL) -> masuk daftar belum ditempatkan Beat Editor
    UNION ALL SELECT 6, 2, 'Fmaj7', 0, NULL
    UNION ALL SELECT 6, 2, 'G13', 13, NULL
    -- Baris 3: Hanya start_beat (position = NULL) -> masuk daftar belum ditempatkan Simple Editor
    UNION ALL SELECT 6, 3, 'A', NULL, 91
    UNION ALL SELECT 6, 3, 'Am', NULL, 94

    -- [Section 7] Outro
    -- Baris 1: 1 Lengkap (Bar 33) + 1 Kosong keduanya (position = NULL, start_beat = NULL)
    UNION ALL SELECT 7, 1, 'Cadd9', 0, 97
    UNION ALL SELECT 7, 1, 'G13', NULL, NULL
    -- Baris 2: 1 Lengkap penutup (Bar 34)
    UNION ALL SELECT 7, 2, 'Cmaj7', 13, 100
) AS v
INNER JOIN songs s ON s.title = 'Dummy Waltz'
INNER JOIN artists a ON a.id = s.artist_id AND a.name = 'Duo Dummy'
INNER JOIN song_sections ss ON ss.song_id = s.id AND ss.sequence = v.sec_seq
INNER JOIN lyric_lines ll ON ll.song_section_id = ss.id AND ll.line_number = v.line_no
INNER JOIN chords c ON c.name = v.chord_name;

COMMIT;
