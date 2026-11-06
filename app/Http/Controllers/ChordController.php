<?php

namespace App\Http\Controllers;

use App\Models\Chord;
use App\Models\ChordDiagram;
use Illuminate\Http\Request;
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
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('pronunciation', 'like', "%{$search}%");
            });
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
     * Simpan chord baru ke database (Admin Only).
     */
    public function store(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:chords,name',
            'pronunciation' => 'required|string|max:120',
            'diagram' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'name.required' => 'Nama chord wajib diisi.',
            'name.unique' => 'Chord dengan nama ini sudah ada.',
            'pronunciation.required' => 'Cara baca chord wajib diisi.',
            'diagram.image' => 'File diagram harus berupa gambar.',
        ]);

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

        return redirect()->route('chords.index', ['selected' => $chord->id])
            ->with('success', 'Chord berhasil ditambahkan.');
    }

    /**
     * Perbarui data chord (Admin Only).
     */
    public function update(Request $request, Chord $chord)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('chords', 'name')->ignore($chord->id)],
            'pronunciation' => 'required|string|max:120',
            'diagram' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ], [
            'name.required' => 'Nama chord wajib diisi.',
            'name.unique' => 'Chord dengan nama ini sudah ada.',
            'pronunciation.required' => 'Cara baca chord wajib diisi.',
            'diagram.image' => 'File diagram harus berupa gambar.',
        ]);

        $chord->update([
            'name' => trim($validated['name']),
            'pronunciation' => trim($validated['pronunciation']),
        ]);

        if ($request->hasFile('diagram')) {
            $path = $request->file('diagram')->store('chords/diagrams', 'public');
            $chord->diagrams()->delete();
            ChordDiagram::create([
                'chord_id' => $chord->id,
                'file_path' => $path,
            ]);
        }

        return redirect()->route('chords.index', ['selected' => $chord->id])
            ->with('success', 'Chord berhasil diperbarui.');
    }

    /**
     * Hapus chord dari database (Admin Only).
     */
    public function destroy(Chord $chord)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }

        $chord->diagrams()->delete();
        $chord->delete();

        return redirect()->route('chords.index')->with('success', 'Chord berhasil dihapus.');
    }
}
