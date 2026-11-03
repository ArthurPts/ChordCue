<?php

return [

    // Matikan dengan TTS_ENABLED=false (mis. saat testing / tanpa internet)
    'enabled' => env('TTS_ENABLED', true),

    /*
     | Perintah edge-tts. Dipisah spasi menjadi array argumen (aman di Windows/Mac/Linux).
     | Contoh nilai .env:
     |   TTS_COMMAND=edge-tts
     |   TTS_COMMAND="python -m edge_tts"      (kalau edge-tts tidak ada di PATH)
     |   TTS_COMMAND="py -m edge_tts"          (Windows, Python launcher)
     | Catatan: path executable yang mengandung spasi tidak didukung; pakai "python -m edge_tts".
     */
    'command' => array_values(array_filter(preg_split('/\s+/', trim(env('TTS_COMMAND', 'edge-tts'))))),

    // Daftar suara: jalankan `edge-tts --list-voices`
    // id-ID-ArdiNeural (pria) | id-ID-GadisNeural (wanita) | en-US-AriaNeural (Inggris)
    'voice' => env('TTS_VOICE', 'id-ID-ArdiNeural'),

    // Kecepatan bicara, format edge-tts: "+0%", "-10%", "+20%"
    'rate' => env('TTS_RATE', '-10%'),

    // Batas waktu satu proses TTS (detik)
    'timeout' => (int) env('TTS_TIMEOUT', 60),

    // Lokasi penyimpanan: storage/app/public/chords/audio/*.mp3
    // Pastikan sudah menjalankan: php artisan storage:link
    'disk' => env('TTS_DISK', 'public'),
    'directory' => env('TTS_DIRECTORY', 'chords/audio'),
];
