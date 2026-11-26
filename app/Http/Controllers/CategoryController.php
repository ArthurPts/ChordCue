<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Metadata tampilan untuk ketiga tipe kategori pada halaman Kelola Kategori.
     *
     * @var array<string, array<string, string>>
     */
    public const TYPES = [
        'genre' => [
            'label'       => 'Genre',
            'singular'    => 'genre',
            'icon'        => 'tag',
            'description' => 'Kelompokkan lagu berdasarkan aliran musik seperti Pop, Rock, Jazz, atau Acoustic.',
            'placeholder' => 'contoh: Indie Pop',
            'search_hint' => 'Cari nama genre...',
        ],
        'mood' => [
            'label'       => 'Mood',
            'singular'    => 'mood',
            'icon'        => 'mood-smile',
            'description' => 'Kelompokkan lagu berdasarkan suasana hati seperti Romantis, Sendu, atau Ceria.',
            'placeholder' => 'contoh: Romantis',
            'search_hint' => 'Cari nama mood...',
        ],
        'location' => [
            'label'       => 'Lokasi',
            'singular'    => 'lokasi',
            'icon'        => 'map-pin',
            'description' => 'Kelompokkan lagu berdasarkan asal wilayah seperti Indonesia, Barat, atau Jepang.',
            'placeholder' => 'contoh: Indonesia',
            'search_hint' => 'Cari nama lokasi...',
        ],
    ];

    /**
     * Memastikan pengguna yang mengakses adalah administrator.
     *
     * Fungsi ini digunakan pada:
     * - CategoryController::index()
     * - CategoryController::store()
     * - CategoryController::update()
     * - CategoryController::destroy()
     */
    private function ensureAdmin(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses khusus administrator.');
        }
    }

    /**
     * Menampilkan halaman Kelola Kategori (Genre, Mood, Lokasi) dalam satu halaman terpadu.
     *
     * Fungsi ini digunakan pada:
     * - Route: GET /admin/categories (admin.categories.index)
     * - resources/views/components/navbar.blade.php
     */
    public function index(Request $request): View
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $requestedType = (string) $request->input('type', 'genre');
        $activeType = array_key_exists($requestedType, self::TYPES) ? $requestedType : 'genre';
        $searchKeyword = trim((string) $request->input('q', ''));

        // 2. Query Database
        $typeCounts = [];
        foreach (array_keys(self::TYPES) as $typeKey) {
            $typeCounts[$typeKey] = Category::where('type', $typeKey)->count();
        }

        $categoriesQuery = Category::withCount('songs')
            ->where('type', $activeType);

        if ($searchKeyword !== '') {
            $categoriesQuery->where('name', 'like', '%' . $searchKeyword . '%');
        }

        $categories = $categoriesQuery
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // 3. Response / Render View
        return view('admin.categories.index', [
            'categories'    => $categories,
            'activeType'    => $activeType,
            'activeMeta'    => self::TYPES[$activeType],
            'categoryTypes' => self::TYPES,
            'typeCounts'    => $typeCounts,
            'searchKeyword' => $searchKeyword,
        ]);
    }

    /**
     * Menyimpan kategori baru (Genre, Mood, atau Lokasi) ke database.
     *
     * Fungsi ini digunakan pada:
     * - Route: POST /admin/categories (admin.categories.store)
     * - resources/views/admin/categories/index.blade.php
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validatedInput = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')],
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max'      => 'Nama kategori maksimal 120 karakter.',
            'name.unique'   => 'Nama kategori tersebut sudah terdaftar. Gunakan nama lain.',
            'type.required' => 'Tipe kategori wajib dipilih.',
            'type.in'       => 'Tipe kategori tidak valid.',
        ]);

        // 2. Query Database
        $category = Category::create([
            'name' => $validatedInput['name'],
            'type' => $validatedInput['type'],
        ]);

        $typeLabel = self::TYPES[$category->type]['label'];

        // 3. Response / Render View
        return redirect()
            ->route('admin.categories.index', ['type' => $category->type])
            ->with('status', "{$typeLabel} \"{$category->name}\" berhasil ditambahkan.");
    }

    /**
     * Memperbarui nama atau tipe kategori yang sudah ada.
     *
     * Fungsi ini digunakan pada:
     * - Route: PUT /admin/categories/{category} (admin.categories.update)
     * - resources/views/admin/categories/index.blade.php
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
        ]);

        $validatedInput = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max'      => 'Nama kategori maksimal 120 karakter.',
            'name.unique'   => 'Nama kategori tersebut sudah terdaftar. Gunakan nama lain.',
            'type.required' => 'Tipe kategori wajib dipilih.',
            'type.in'       => 'Tipe kategori tidak valid.',
        ]);

        // 2. Query Database
        $category->update([
            'name' => $validatedInput['name'],
            'type' => $validatedInput['type'],
        ]);

        $typeLabel = self::TYPES[$category->type]['label'];

        // 3. Response / Render View
        return redirect()
            ->route('admin.categories.index', ['type' => $category->type])
            ->with('status', "{$typeLabel} \"{$category->name}\" berhasil diperbarui.");
    }

    /**
     * Menghapus kategori dan melepaskannya dari seluruh lagu terkait tanpa menghapus lagu.
     *
     * Fungsi ini digunakan pada:
     * - Route: DELETE /admin/categories/{category} (admin.categories.destroy)
     * - resources/views/admin/categories/index.blade.php
     */
    public function destroy(Category $category): RedirectResponse
    {
        // 1. Otorisasi & Validasi Input
        $this->ensureAdmin();

        // 2. Query Database
        $categoryName = $category->name;
        $categoryType = $category->type;
        $typeLabel = self::TYPES[$categoryType]['label'] ?? 'Kategori';
        $attachedSongsCount = $category->songs()->count();

        $category->songs()->detach();
        $category->delete();

        $statusMessage = $attachedSongsCount > 0
            ? "{$typeLabel} \"{$categoryName}\" berhasil dihapus dan dilepas dari {$attachedSongsCount} lagu."
            : "{$typeLabel} \"{$categoryName}\" berhasil dihapus.";

        // 3. Response / Render View
        return redirect()
            ->route('admin.categories.index', ['type' => $categoryType])
            ->with('status', $statusMessage);
    }
}
