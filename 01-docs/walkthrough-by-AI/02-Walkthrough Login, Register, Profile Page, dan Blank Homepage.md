# Walkthrough: Login, Register, Profile Page, dan Blank Homepage (ChordCue)

Seluruh implementasi untuk halaman **Login**, **Register**, **Profile Page**, dan **Blank Homepage** telah selesai dikerjakan sesuai aturan arsitektur [CHORDCUE_RULES.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/.agents/rules/CHORDCUE_RULES.md) dan sistem desain [DESIGN_SYSTEM.md](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/.agents/rules/DESIGN_SYSTEM.md).

---

## 1. Perubahan yang Dilakukan

### A. Design System & Frontend Foundation
- [tailwind.config.js](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tailwind.config.js): Mengganti konfigurasi default Tailwind dengan pemetaan token murni sesuai `DESIGN_SYSTEM.md` §14.2 (palet bawaan dihapus, ukuran berbasis token piksel, tanpa bayangan/gradien).
- [resources/css/app.css](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/css/app.css): Mengimpor `./tokens.css` di baris pertama, mendefinisikan `@layer base` (body latar `bg-base` dan outline ring fokus), serta styling `.ui-progress` dan `.ui-slider`.
- [resources/views/components/icon.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/icon.blade.php): Komponen icon Tabler outline SVG (`music`, `user`, `mail`, `lock`, `check`, `alert-circle`, `trash`, `logout`, `chevron-down`, `x`, dll.) tanpa ketergantungan library pihak ketiga.
- **Komponen Blade Breeze Direstyle**:
  - [primary-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/primary-button.blade.php): `bg-accent hover:bg-accent-hover text-accent-on h-control px-14 py-8 rounded-sm`
  - [secondary-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/secondary-button.blade.php): `bg-transparent hover:bg-surface-2 border border-subtle text-ink`
  - [danger-button.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/danger-button.blade.php): `bg-transparent hover:bg-error-tint border border-error text-error`
  - [text-input.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/text-input.blade.php): `bg-surface-3 border border-subtle text-ink placeholder-ink-tertiary focus:border-accent`
  - [input-label.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/input-label.blade.php), [input-error.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/input-error.blade.php), [modal.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/modal.blade.php), [dropdown.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/dropdown.blade.php), [dropdown-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/dropdown-link.blade.php), [nav-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/nav-link.blade.php), [responsive-nav-link.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/responsive-nav-link.blade.php), [application-logo.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/components/application-logo.blade.php).
  - [layouts/guest.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/guest.blade.php) & [layouts/app.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/app.blade.php): Menghapus CDN Google/Bunny fonts eksternal dan menetapkan Dark Theme murni.
  - [layouts/navigation.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/layouts/navigation.blade.php): Menampilkan navbar dengan brand ChordCue, username `Auth::user()->username`, dan menu responsive.

### B. Backend Logic & Model
- [app/Models/User.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/User.php): Menambahkan method `deleteWithSongs()` untuk menghapus seluruh lagu dan file fisik secara eksplisit.
- [app/Models/Song.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Models/Song.php): Menambahkan method `deleteWithFiles()` untuk menghapus backing track fisik sebelum record dihapus.
- [RegisteredUserController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/Auth/RegisteredUserController.php):
  - Mengganti validasi `name` menjadi `username` (unik, max 120, alpha_dash).
  - Alur linear terstruktur: `1. Otorisasi & Validasi Input`, `2. Query Database`, `3. Response / Render View`.
  - Dilengkapi dokumentasi PHPDoc bahasa Indonesia.
- [ProfileUpdateRequest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Requests/ProfileUpdateRequest.php): Memvalidasi `username` dan `email` unik dengan mengabaikan ID user yang sedang login.
- [ProfileController.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/app/Http/Controllers/ProfileController.php):
  - Menghapus referensi `email_verified_at` yang tidak ada pada database.
  - Menggunakan `$user->deleteWithSongs()` pada aksi hapus akun.
- [routes/web.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/routes/web.php): Menamai route root `home`, menghapus middleware `verified` pada `/dashboard`, dan mengarahkan dashboard ke beranda.

### C. Halaman Pengguna (Views)
- [resources/views/welcome.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/welcome.blade.php): Halaman beranda awal (*blank homepage*) yang bersih dengan Dark Blue Design System, navbar navigasi dinamis (status guest vs auth), serta kanvas sambutan yang siap untuk integrasi Katalog Lagu.
- [resources/views/auth/login.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/auth/login.blade.php): Formulir login (email, password, remember me, link register, link forgot password) dengan gaya kartu `bg-surface-1 border border-subtle rounded-lg`.
- [resources/views/auth/register.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/auth/register.blade.php): Formulir pendaftaran akun (username, email, kata sandi, konfirmasi kata sandi) dengan umpan balik error token semantik.
- [resources/views/profile/edit.blade.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/resources/views/profile/edit.blade.php) & partials: Formulir pembaruan data profil (`username` & `email`), pembaruan kata sandi, dan modal hapus akun dengan tombol konfirmasi bahaya (*danger button*).

### D. Testing & Factories
- [database/factories/UserFactory.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/database/factories/UserFactory.php): Menghasilkan `username` unik dan menghapus kolom `name` serta `email_verified_at`.
- [tests/Feature/Auth/RegistrationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/RegistrationTest.php): Diperbarui untuk menguji pendaftaran dengan field `username`.
- [tests/Feature/Auth/AuthenticationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/AuthenticationTest.php): Diperbarui untuk redirect ke rute `home`.
- [tests/Feature/ProfileTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/ProfileTest.php): Diperbarui untuk menguji pembaruan field `username`.
- [tests/Feature/Auth/EmailVerificationTest.php](file:///c:/xampp/new/htdocs/LearnLaravel/chordcue/tests/Feature/Auth/EmailVerificationTest.php): Ditandai skipped karena verifikasi email out-of-scope pada spesifikasi ChordCue.

---

## 2. Hasil Verifikasi

### A. Pengujian Otomatis (Pest PHP)
```
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\Auth\AuthenticationTest (4 tests)
   WARN  Tests\Feature\Auth\EmailVerificationTest (1 skipped: intentionally out-of-scope)
   PASS  Tests\Feature\Auth\PasswordConfirmationTest (3 tests)
   PASS  Tests\Feature\Auth\PasswordResetTest (4 tests)
   PASS  Tests\Feature\Auth\PasswordUpdateTest (2 tests)
   PASS  Tests\Feature\Auth\RegistrationTest (2 tests)
   PASS  Tests\Feature\ExampleTest (1 test)
   PASS  Tests\Feature\ProfileTest (4 tests)

Tests:    1 skipped, 21 passed (50 assertions)
Duration: 27.54s
```

### B. Kompilasi Aset Frontend (Vite)
```
✓ 59 modules transformed.
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-CSVh3feE.css   21.80 kB │ gzip:  4.98 kB
public/build/assets/app-WC-ZjLzv.js   106.90 kB │ gzip: 38.68 kB
✓ built in 3.34s
```

### C. Pengecekan Sintaks PHP (Lint)
Seluruh berkas PHP baru dan yang dimodifikasi telah diperiksa dengan `php -l` tanpa kesalahan sintaks.
