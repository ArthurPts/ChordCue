<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateChordAudio;
use App\Models\Chord;
use App\Models\ChordDiagram;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ChordController extends Controller
{
    /**
     * Tampilkan daftar pustaka chord (dapat diakses Guest & Auth).
     */
    public function index(Request $request)
    {
        $query = Chord::with('diagrams');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('name', 'like', "%{$search}%");
        }

        // Paginate 25 kartu per halaman
        $chords = $query->orderBy('name')->paginate(25)->withQueryString();

        // Chord yang sedang terpilih untuk panel kanan
        $selectedChord = null;
        if ($request->filled('selected')) {
            $selectedChord = Chord::with('diagrams')->find($request->input('selected'));
        }

        if (!$selectedChord && $chords->count() > 0) {
            $selectedChord = $chords->first();
        } elseif (!$selectedChord) {
            $selectedChord = Chord::with('diagrams')->first();
        }

        return view('chords.index', compact('chords', 'selectedChord'));
    }

    /**
     * Simpan chord baru ke database beserta antrean pembuatan audio TTS (Admin Only).
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /chords (chords.store)
     */
    public function store(Request $request)
    {
        // 1. Otorisasi & Validasi Input (Otorisasi ditangani oleh middleware 'admin')
        $maxDiagramMb = (int) config('chordcue.chord_diagram_max_mb', 2);
        $maxDiagramKb = $maxDiagramMb * 1024;

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:chords,name',
            'pronunciation' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9 ]+$/'],
            'diagram' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:' . $maxDiagramKb],
        ], $this->chordValidationMessages($maxDiagramMb));

        // 2. Query Database & Simpan File
        $chord = Chord::create([
            'name' => trim($validated['name']),
            'pronunciation' => trim($validated['pronunciation']),
        ]);

        if ($request->hasFile('diagram')) {
            $path = $request->file('diagram')->store('chords/diagrams', 'public');
            ChordDiagram::create([
                'chord_id' => $chord->id,
                'file_path' => $path,
            ]);
        } else {
            ChordDiagram::create([
                'chord_id' => $chord->id,
                'file_path' => 'diagrams/No_Image_Available.jpg',
            ]);
        }

        if (config('tts.enabled')) {
            GenerateChordAudio::dispatch($chord->id);
        }

        // 3. Response / Redirect
        return redirect()->route('chords.index', ['selected' => $chord->id])
            ->with('success', 'Chord berhasil ditambahkan.');
    }

    /**
     * Perbarui data chord; bila nama atau cara baca berubah, hapus audio lama
     * lalu buat ulang audio TTS baru (Admin Only).
     *
     * Fungsi ini digunakan pada:
     * - Route: PUT /chords/{chord} (chords.update)
     */
    public function update(Request $request, Chord $chord)
    {
        // 1. Otorisasi & Validasi Input (Otorisasi ditangani oleh middleware 'admin')
        $maxDiagramMb = (int) config('chordcue.chord_diagram_max_mb', 2);
        $maxDiagramKb = $maxDiagramMb * 1024;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('chords', 'name')->ignore($chord->id)],
            'pronunciation' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9 ]+$/'],
            'diagram' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:' . $maxDiagramKb],
        ], $this->chordValidationMessages($maxDiagramMb));

        // 2. Query Database & Perbarui File Audio/Diagram
        $newName = trim($validated['name']);
        $newPronunciation = trim($validated['pronunciation']);
        $oldAudioPath = $chord->file_path;
        $needsAudioRegeneration = ($chord->name !== $newName || $chord->pronunciation !== $newPronunciation);

        $updatePayload = [
            'name' => $newName,
            'pronunciation' => $newPronunciation,
        ];

        if ($needsAudioRegeneration) {
            $updatePayload['file_path'] = null;
        }

        $chord->update($updatePayload);

        if ($needsAudioRegeneration) {
            $ttsDiskName = config('tts.disk', 'public');
            if ($oldAudioPath && Storage::disk($ttsDiskName)->exists($oldAudioPath)) {
                Storage::disk($ttsDiskName)->delete($oldAudioPath);
            }

            if (config('tts.enabled')) {
                GenerateChordAudio::dispatch($chord->id, true);
            }
        }

        if ($request->hasFile('diagram')) {
            $path = $request->file('diagram')->store('chords/diagrams', 'public');
            foreach ($chord->diagrams as $oldDiagram) {
                $oldDiagram->deleteWithFiles();
            }
            ChordDiagram::create([
                'chord_id' => $chord->id,
                'file_path' => $path,
            ]);
        }

        // 3. Response / Redirect
        return redirect()->route('chords.index', ['selected' => $chord->id])
            ->with('success', 'Chord berhasil diperbarui.');
    }

    /**
     * Hapus chord beserta file audio TTS dan file diagramnya di storage (Admin Only).
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /chords/{chord} (chords.destroy)
     */
    public function destroy(Chord $chord)
    {
        try {
            $chord->deleteWithFiles();
        } catch (DomainException $exception) {
            return redirect()->route('chords.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('chords.index')->with('success', 'Chord berhasil dihapus.');
    }

    /**
     * Menyusun pesan validasi terstandar untuk form tambah dan ubah chord.
     *
     * Fungsi ini digunakan pada:
     * - ChordController::store()
     * - ChordController::update()
     *
     * @return array<string, string>
     */
    private function chordValidationMessages(int $maxDiagramMb): array
    {
        return [
            'name.required' => 'Nama chord wajib diisi.',
            'name.unique' => 'Chord dengan nama ini sudah ada.',
            'pronunciation.required' => 'Cara baca chord wajib diisi.',
            'pronunciation.regex' => 'Cara baca hanya boleh berisi huruf, angka, dan spasi (tanpa simbol).',
            'diagram.image' => 'File diagram harus berupa gambar.',
            'diagram.mimes' => 'Format gambar diagram harus JPEG, PNG, JPG, atau SVG.',
            'diagram.max' => 'Ukuran gambar diagram maksimal ' . $maxDiagramMb . ' MB.',
        ];
    }
}
