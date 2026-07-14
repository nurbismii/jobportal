# Assessment Table Autosave Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menampilkan kandidat asesmen dalam tabel yang dapat dicari dan menyimpan hasil tiap kandidat secara otomatis.

**Architecture:** Controller publik menambahkan endpoint JSON daftar kandidat terpaginasikan dan endpoint autosave yang tetap memakai `AssessmentLinkService::saveResult`. Blade publik menjadi tabel Bootstrap dengan pencarian debounce; JavaScript mengirim seluruh nilai dan catatan satu baris setelah 700 ms.

**Tech Stack:** Laravel 8, Blade/Bootstrap, Eloquent pagination, JavaScript vanilla, PHPUnit 9.

## Global Constraints

- Endpoint tabel/autosave hanya tersedia untuk token link aktif dan sesi PIN `assessment_link_access.{linkId}`; token invalid/nonaktif/kedaluwarsa adalah 404 dan sesi tidak ada adalah 403.
- Pencarian server-side memakai nama kandidat atau nomor KTP, pagination 25 kandidat, dan filter `all`, `pending`, `completed`.
- Autosave menunggu 700 ms setelah perubahan, mengirim seluruh `values` dan `petugas_note` milik satu kandidat, dan memakai audit service yang sudah ada.
- Tidak ada endpoint baru yang memperbarui status lamaran.

---

### Task 1: API daftar kandidat dan autosave publik

**Files:**
- Modify: `app/Http/Controllers/PublicAssessmentLinkController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** menghasilkan `assessment-links.public.candidates` (GET JSON) dan `assessment-links.public.autosave` (POST JSON); keduanya menerima token, autosave menerima candidate ID.

- [ ] **Step 1: Tulis test gagal.**

```php
public function test_unlocked_public_link_lists_candidates_by_name_or_ktp_with_pagination()
{
    $link = $this->createLapanganLinkWithCandidates(30);
    $this->withSession(['assessment_link_access.' . $link->id => true])
        ->getJson(route('assessment-links.public.candidates', [$link->public_token, 'q' => 'KTP-001', 'status' => 'all']))
        ->assertOk()->assertJsonPath('per_page', 25)->assertJsonCount(1, 'data');
}
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; expected FAIL karena route JSON belum ada.**
- [ ] **Step 3: Implementasi minimal.** Tambahkan GET `/penilaian/{token}/kandidat` dengan throttle `30,1` dan middleware akses PIN yang sudah ada. Query hanya `AssessmentLinkCandidate` milik link, eager load `lamaran.biodata.user`, filter `whereHas` nama/no_ktp, filter pending/completed berdasarkan `last_submitted_at`, `paginate(25)`, lalu kembalikan JSON hanya `id`, `name`, `no_ktp`, `result_values`, `petugas_note`, `last_submitted_at`. Tambahkan POST `/autosave` yang memanggil validasi Request dan `saveResult()`, mengembalikan JSON `saved_at`/`result_values`; gunakan 422 untuk nilai schema invalid.
- [ ] **Step 4: Tambah test autosave yang memastikan JSON sukses, audit bertambah, status lamaran tidak berubah, dan tanpa sesi menghasilkan 403; jalankan test focused hingga PASS.**
- [ ] **Step 5: Commit:** `git add app/Http/Controllers/PublicAssessmentLinkController.php routes/web.php tests/Feature/AssessmentLinkTest.php; git commit -m "feat: add assessment candidate autosave API"`.

### Task 2: Tabel, pencarian autocomplete, dan status autosave

**Files:**
- Modify: `resources/views/public-assessment-links/form.blade.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** Blade menerima link/schema dan endpoint JSON dari Task 1; JavaScript `loadCandidates(query, page, status)` serta `queueAutosave(row)`.

- [ ] **Step 1: Tulis test render gagal.**

```php
$this->withSession(['assessment_link_access.' . $link->id => true])
    ->get(route('assessment-links.public.show', $link->public_token))
    ->assertOk()->assertSee('Cari nama atau nomor KTP')->assertSee('Status simpan')->assertSee('autosave');
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; expected FAIL karena tabel/autosave belum dirender.**
- [ ] **Step 3: Ganti kartu kandidat dengan tabel responsif.** Header tetap: No, No KTP, Nama, setiap field schema, Catatan petugas, Status simpan. Tambahkan search input autocomplete, filter status, pagination, empty state. JavaScript debounce query 300 ms, ambil JSON Task 1, escape text sebelum memasukkannya ke DOM, lalu debounce autosave per candidate 700 ms. Set status row `Menyimpan…`, `Tersimpan`, atau `Gagal disimpan`; kegagalan menyediakan tombol `Coba lagi` dan tidak menghapus input.
- [ ] **Step 4: Jalankan `php artisan test --filter=AssessmentLinkTest`; expected PASS. Verifikasi browser manual dengan 30 kandidat: pencarian KTP, perubahan angka, status tersimpan, filter completed, dan halaman kedua.**
- [ ] **Step 5: Commit:** `git add resources/views/public-assessment-links/form.blade.php tests/Feature/AssessmentLinkTest.php; git commit -m "feat: show assessment candidates in autosave table"`.

## Self-Review

- Task 1 memenuhi akses, pencarian, pagination, filter, JSON autosave, audit, dan regresi status.
- Task 2 memenuhi tabel, kolom schema, autocomplete, debounce 700 ms, status feedback, retry, dan empty state.
- Tidak ada perubahan model/migration karena schema dan audit yang ada sudah mencukupi.
