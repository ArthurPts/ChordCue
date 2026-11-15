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
--    - Sempurna (4/4, BPM 80): 100% placement Lengkap (60 items).
--    - Dummy Waltz (3/4, BPM 90): Intro..Chorus 2 Lengkap + sampel lengkap &
--      tidak lengkap di Bridge & Outro (40 Lengkap, 2 hanya position,
--      2 hanya start_beat, 1 kosong keduanya).
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
    SELECT 1 AS sec_seq, 1 AS line_no, 'C' AS chord_name, 0 AS position, 1 AS start_beat
    UNION ALL SELECT 1, 1, 'G', 11, 5
    UNION ALL SELECT 1, 2, 'Am', 3, 9
    UNION ALL SELECT 1, 2, 'F', 21, 13
    UNION ALL SELECT 1, 3, 'C', 0, 17
    UNION ALL SELECT 1, 3, 'G', 12, 21
    UNION ALL SELECT 1, 4, 'Am', 5, 25
    UNION ALL SELECT 1, 4, 'G', 12, 29

    -- [Section 2] Verse 2 (Bar 9 - Bar 16, Beat 33 - 64)
    UNION ALL SELECT 2, 1, 'C', 3, 33
    UNION ALL SELECT 2, 1, 'G', 10, 37
    UNION ALL SELECT 2, 2, 'Am', 1, 41
    UNION ALL SELECT 2, 2, 'F', 27, 45
    UNION ALL SELECT 2, 3, 'C', 0, 49
    UNION ALL SELECT 2, 3, 'G', 12, 53
    UNION ALL SELECT 2, 4, 'Am', 0, 57
    UNION ALL SELECT 2, 4, 'F', 14, 61

    -- [Section 3] Pre-Chorus (Bar 17 - Bar 22, Beat 65 - 88)
    UNION ALL SELECT 3, 1, 'Dm', 0, 65
    UNION ALL SELECT 3, 1, 'G', 25, 69
    UNION ALL SELECT 3, 2, 'Em', 0, 73
    UNION ALL SELECT 3, 2, 'Am', 24, 77
    UNION ALL SELECT 3, 3, 'F', 6, 81
    UNION ALL SELECT 3, 3, 'G', 25, 85

    -- [Section 4] Chorus (Bar 23 - Bar 27, Beat 89 - 108)
    UNION ALL SELECT 4, 1, 'C', 0, 89
    UNION ALL SELECT 4, 1, 'G', 11, 91
    UNION ALL SELECT 4, 2, 'Am', 0, 93
    UNION ALL SELECT 4, 2, 'Em', 11, 95
    UNION ALL SELECT 4, 3, 'F', 0, 97
    UNION ALL SELECT 4, 3, 'C', 11, 99
    UNION ALL SELECT 4, 4, 'Dm', 0, 101
    UNION ALL SELECT 4, 4, 'G', 9, 103
    UNION ALL SELECT 4, 5, 'F', 4, 105
    UNION ALL SELECT 4, 5, 'G', 17, 107

    -- [Section 5] Post-Chorus (Bar 28 - Bar 29, Beat 109 - 116)
    UNION ALL SELECT 5, 1, 'C', 0, 109
    UNION ALL SELECT 5, 2, 'G', 0, 113

    -- [Section 6] Verse 2 (Bar 30 - Bar 37, Beat 117 - 148)
    UNION ALL SELECT 6, 1, 'C', 0, 117
    UNION ALL SELECT 6, 1, 'G', 12, 121
    UNION ALL SELECT 6, 2, 'Am', 5, 125
    UNION ALL SELECT 6, 2, 'F', 22, 129
    UNION ALL SELECT 6, 3, 'C', 0, 133
    UNION ALL SELECT 6, 3, 'G', 13, 137
    UNION ALL SELECT 6, 4, 'Am', 4, 141
    UNION ALL SELECT 6, 4, 'F', 16, 145

    -- [Section 7] Pre-Chorus (Bar 38 - Bar 43, Beat 149 - 172)
    UNION ALL SELECT 7, 1, 'Dm', 0, 149
    UNION ALL SELECT 7, 1, 'G', 25, 153
    UNION ALL SELECT 7, 2, 'Em', 0, 157
    UNION ALL SELECT 7, 2, 'Am', 24, 161
    UNION ALL SELECT 7, 3, 'F', 6, 165
    UNION ALL SELECT 7, 3, 'G', 25, 169

    -- [Section 8] Chorus (Bar 44 - Bar 48, Beat 173 - 192)
    UNION ALL SELECT 8, 1, 'C', 0, 173
    UNION ALL SELECT 8, 1, 'G', 11, 175
    UNION ALL SELECT 8, 2, 'Am', 0, 177
    UNION ALL SELECT 8, 2, 'Em', 11, 179
    UNION ALL SELECT 8, 3, 'F', 0, 181
    UNION ALL SELECT 8, 3, 'C', 11, 183
    UNION ALL SELECT 8, 4, 'Dm', 0, 185
    UNION ALL SELECT 8, 4, 'G', 9, 187
    UNION ALL SELECT 8, 5, 'F', 4, 189
    UNION ALL SELECT 8, 5, 'G', 17, 191

    -- [Section 9] Post-Chorus (Bar 49 - Bar 50, Beat 193 - 200)
    UNION ALL SELECT 9, 1, 'C', 0, 193
    UNION ALL SELECT 9, 2, 'C', 0, 197
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
    SELECT 1 AS sec_seq, 1 AS line_no, 'C' AS chord_name, 0 AS position, 1 AS start_beat
    UNION ALL SELECT 1, 1, 'G', 13, 4
    UNION ALL SELECT 1, 2, 'Am', 0, 7
    UNION ALL SELECT 1, 2, 'G', 13, 10

    -- [Section 2] Verse 1 (3/4: Bar 5 - Bar 12, Beat 13 - 36) - Lengkap
    UNION ALL SELECT 2, 1, 'C', 0, 13
    UNION ALL SELECT 2, 1, 'F', 13, 16
    UNION ALL SELECT 2, 2, 'Dm', 0, 19
    UNION ALL SELECT 2, 2, 'G7', 13, 22
    UNION ALL SELECT 2, 3, 'C', 0, 25
    UNION ALL SELECT 2, 3, 'Am', 13, 28
    UNION ALL SELECT 2, 4, 'Dm', 0, 31
    UNION ALL SELECT 2, 4, 'G', 13, 34

    -- [Section 3] Chorus 1 (3/4: Bar 13 - Bar 16, Beat 37 - 48) - Lengkap
    UNION ALL SELECT 3, 1, 'F', 0, 37
    UNION ALL SELECT 3, 1, 'G7', 13, 39
    UNION ALL SELECT 3, 2, 'Em7', 0, 40
    UNION ALL SELECT 3, 2, 'Am7', 13, 42
    UNION ALL SELECT 3, 3, 'Dm7', 0, 43
    UNION ALL SELECT 3, 3, 'G7', 13, 45
    UNION ALL SELECT 3, 4, 'Cmaj7', 0, 46
    UNION ALL SELECT 3, 4, 'C7', 13, 48

    -- [Section 4] Verse 2 (3/4: Bar 17 - Bar 24, Beat 49 - 72) - Lengkap
    UNION ALL SELECT 4, 1, 'C', 0, 49
    UNION ALL SELECT 4, 1, 'G', 13, 52
    UNION ALL SELECT 4, 2, 'Am', 0, 55
    UNION ALL SELECT 4, 2, 'Em', 13, 58
    UNION ALL SELECT 4, 3, 'F', 0, 61
    UNION ALL SELECT 4, 3, 'C', 13, 64
    UNION ALL SELECT 4, 4, 'Dm', 0, 67
    UNION ALL SELECT 4, 4, 'G7', 13, 70

    -- [Section 5] Chorus 2 (3/4: Bar 25 - Bar 28, Beat 73 - 84) - Lengkap
    UNION ALL SELECT 5, 1, 'F', 0, 73
    UNION ALL SELECT 5, 1, 'G', 13, 75
    UNION ALL SELECT 5, 2, 'Em', 0, 76
    UNION ALL SELECT 5, 2, 'Am', 13, 78
    UNION ALL SELECT 5, 3, 'Dm', 0, 79
    UNION ALL SELECT 5, 3, 'G7', 13, 81
    UNION ALL SELECT 5, 4, 'C', 0, 82
    UNION ALL SELECT 5, 4, 'C7', 13, 84

    -- [Section 6] Bridge (Campuran Lengkap & Tidak Lengkap untuk uji Simple/Beat Editor)
    -- Baris 1: Lengkap (Bar 29 - Bar 30)
    UNION ALL SELECT 6, 1, 'Fmaj7', 0, 85
    UNION ALL SELECT 6, 1, 'G', 13, 88
    -- Baris 2: Hanya position (start_beat = NULL) -> masuk daftar belum ditempatkan Beat Editor
    UNION ALL SELECT 6, 2, 'Em7', 0, NULL
    UNION ALL SELECT 6, 2, 'Am', 13, NULL
    -- Baris 3: Hanya start_beat (position = NULL) -> masuk daftar belum ditempatkan Simple Editor
    UNION ALL SELECT 6, 3, 'Dm7', NULL, 91
    UNION ALL SELECT 6, 3, 'G7', NULL, 94

    -- [Section 7] Outro
    -- Baris 1: 1 Lengkap (Bar 33) + 1 Kosong keduanya (position = NULL, start_beat = NULL)
    UNION ALL SELECT 7, 1, 'Cadd9', 0, 97
    UNION ALL SELECT 7, 1, 'G', NULL, NULL
    -- Baris 2: 1 Lengkap penutup (Bar 34)
    UNION ALL SELECT 7, 2, 'C', 13, 100
) AS v
INNER JOIN songs s ON s.title = 'Dummy Waltz'
INNER JOIN artists a ON a.id = s.artist_id AND a.name = 'Duo Dummy'
INNER JOIN song_sections ss ON ss.song_id = s.id AND ss.sequence = v.sec_seq
INNER JOIN lyric_lines ll ON ll.song_section_id = ss.id AND ll.line_number = v.line_no
INNER JOIN chords c ON c.name = v.chord_name;

COMMIT;

