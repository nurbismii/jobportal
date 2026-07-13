# Link Penilaian Tes Kandidat Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyediakan link ber-PIN untuk petugas lapangan mengisi hasil tes dinamis tanpa mengubah status proses lamaran.

**Architecture:** Tiga tabel menyimpan link, kandidat/hasil terbaru, dan audit revisi. `AssessmentLinkService` adalah satu-satunya pemilik aturan form, token/PIN, masa berlaku, dan penyimpanan atomik; controller hanya menangani HTTP admin atau publik.

**Tech Stack:** Laravel 8, PHP 7.3+/8, Eloquent, Blade Bootstrap, PHPUnit 9, SQLite in-memory feature test.

## Global Constraints

- Tipe hanya `kesehatan` atau `lapangan`; endpoint publik tidak boleh mengubah `lamaran.status_proses` maupun `lamaran.status_lamaran`.
- Kedaluwarsa adalah pukul 23:59:59 hari pembuatan pada `Asia/Makassar`; admin dapat menonaktifkan lebih awal.
- Gunakan token unik `Str::random(64)` dan `Hash::make`/`Hash::check` untuk PIN; plaintext PIN tidak pernah disimpan atau ditampilkan kembali.
- Field tambahan hanya `select`, `number`, `text`: maksimal 20 field, label 100 karakter, 20 pilihan per select, teks/catatan 2.000 karakter.
- Schema kesehatan selalu diawali field terkunci `health_status`: label `Hasil tes kesehatan`, `select`, wajib, opsi tepat `Sehat` dan `Tidak Sehat`.
- Setiap hasil kandidat menyimpan `petugas_note`; setiap simpan menyimpan audit old/new, IP, dan user-agent.
- Terapkan `throttle:5,1` pada PIN dan `throttle:30,1` pada simpan hasil; validasi server-side adalah sumber kebenaran.

---

## File Structure

- `database/migrations/2026_07_13_000000_create_assessment_link_tables.php`: tabel link, kandidat, audit serta indeks dan unique pair kandidat/link.
- `app/Models/AssessmentLink.php`, `AssessmentLinkCandidate.php`, `AssessmentResultAudit.php`: casts JSON/datetime dan relasi Eloquent.
- `app/Services/AssessmentLinkService.php`: pembentukan schema, create, verifikasi akses/PIN, validasi nilai, save audit transaksional.
- `app/Http/Requests/Admin/StoreAssessmentLinkRequest.php`: validasi dan autorisasi admin saat membuat link.
- `app/Http/Requests/PublicAssessmentResultRequest.php`: validasi dasar hasil publik.
- `app/Http/Controllers/Admin/AssessmentLinkController.php`: daftar/buat/detail/nonaktifkan untuk HR.
- `app/Http/Controllers/PublicAssessmentLinkController.php`: gate PIN dan simpan hasil petugas.
- `routes/web.php`: route admin di grup `redirect.role` dan route publik.
- `resources/views/admin/assessment-links/{index,create,show}.blade.php`: UI HR.
- `resources/views/public-assessment-links/{pin,form}.blade.php`: UI petugas.
- `resources/views/admin/lamaran/index.blade.php`: aksi membuat link dari `selected_ids`.
- `tests/Feature/AssessmentLinkTest.php`: test keamanan, form, audit, dan regresi status.

### Task 1: Fondasi database dan model

**Files:**
- Create: `database/migrations/2026_07_13_000000_create_assessment_link_tables.php`
- Create: `app/Models/AssessmentLink.php`
- Create: `app/Models/AssessmentLinkCandidate.php`
- Create: `app/Models/AssessmentResultAudit.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** menghasilkan `AssessmentLink::candidates()`, `AssessmentLink::isAccessibleAt(Carbon $now): bool`, `AssessmentLinkCandidate::lamaran()`, dan `AssessmentLinkCandidate::audits()`.

- [ ] **Step 1: Buat test gagal untuk migration/model.**

```php
public function test_link_casts_schema_and_has_candidate_relation()
{
    $link = AssessmentLink::create(['assessment_type' => 'lapangan', 'public_token' => str_repeat('a', 64), 'pin_hash' => bcrypt('123456'), 'form_schema' => [['id' => 'run_time', 'type' => 'number']], 'created_by' => $this->admin->id, 'expires_at' => now()->endOfDay(), 'is_active' => true]);
    $candidate = AssessmentLinkCandidate::create(['assessment_link_id' => $link->id, 'lamaran_id' => $this->lamaran->id]);
    $this->assertIsArray($link->fresh()->form_schema);
    $this->assertTrue($link->isAccessibleAt(now()));
    $this->assertSame($this->lamaran->id, $candidate->lamaran->id);
}
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus FAIL karena tabel/model belum ada.**

- [ ] **Step 3: Tambahkan tabel berikut, tanpa foreign key agar konsisten dengan skema lama dan test minimal.**

```php
Schema::create('assessment_links', function (Blueprint $table) { $table->id(); $table->string('assessment_type', 20)->index(); $table->string('public_token', 64)->unique(); $table->string('pin_hash'); $table->json('form_schema'); $table->unsignedBigInteger('created_by')->index(); $table->timestamp('expires_at')->index(); $table->boolean('is_active')->default(true)->index(); $table->timestamp('deactivated_at')->nullable(); $table->unsignedBigInteger('deactivated_by')->nullable(); $table->timestamps(); });
Schema::create('assessment_link_candidates', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('assessment_link_id')->index(); $table->unsignedBigInteger('lamaran_id')->index(); $table->json('result_values')->nullable(); $table->text('petugas_note')->nullable(); $table->timestamp('last_submitted_at')->nullable(); $table->timestamps(); $table->unique(['assessment_link_id', 'lamaran_id']); });
Schema::create('assessment_result_audits', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('assessment_link_candidate_id')->index(); $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->string('ip_address', 45)->nullable(); $table->string('user_agent', 255)->nullable(); $table->timestamps(); });
```

Model casts: `form_schema`, `result_values`, `old_values`, `new_values` sebagai `array`; seluruh timestamp relevan `datetime`. Tambahkan relasi `belongsTo`/`hasMany` dengan foreign key eksplisit.

- [ ] **Step 4: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus PASS.**
- [ ] **Step 5: Commit:** `git add database/migrations/2026_07_13_000000_create_assessment_link_tables.php app/Models/AssessmentLink.php app/Models/AssessmentLinkCandidate.php app/Models/AssessmentResultAudit.php tests/Feature/AssessmentLinkTest.php; git commit -m "feat: add assessment link persistence"`.

### Task 2: Service untuk schema dinamis, PIN, dan audit

**Files:**
- Create: `app/Services/AssessmentLinkService.php`
- Modify: `app/Models/AssessmentLink.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** `create(array $attributes, array $lamaranIds, int $creatorId): AssessmentLink`; `schemaFor(string $type, array $fields): array`; `verifyPin(AssessmentLink $link, string $pin): bool`; `saveResult(AssessmentLinkCandidate $candidate, array $values, ?string $note, Request $request): AssessmentLinkCandidate`.

- [ ] **Step 1: Buat test gagal untuk schema kesehatan dan revisi audit.**

```php
public function test_health_schema_is_locked_and_each_result_revision_is_audited()
{
    $link = app(AssessmentLinkService::class)->create(['assessment_type' => 'kesehatan', 'pin' => '123456', 'fields' => []], [$this->lamaran->id], $this->admin->id);
    $this->assertSame(['Sehat', 'Tidak Sehat'], $link->form_schema[0]['options']);
    $candidate = $link->candidates()->first();
    app(AssessmentLinkService::class)->saveResult($candidate, ['health_status' => 'Sehat'], 'Normal', request());
    app(AssessmentLinkService::class)->saveResult($candidate->fresh(), ['health_status' => 'Tidak Sehat'], 'Kontrol', request());
    $this->assertSame(2, AssessmentResultAudit::where('assessment_link_candidate_id', $candidate->id)->count());
}
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus FAIL karena service belum ada.**

- [ ] **Step 3: Implementasikan service.** `schemaFor()` harus normalisasi field tambahan (UUID/id aman, label, tipe, required/options), menolak lebih dari batas global dan menyisipkan field kesehatan terkunci. `create()` memvalidasi tipe dan id kandidat unik, membuat `Str::random(64)`, hash PIN, `now('Asia/Makassar')->endOfDay()`, lalu membuat kandidat. `ensureAccessible()` melempar `ModelNotFoundException` bila aktif/expiry gagal. `saveResult()` harus memakai `DB::transaction`, `lockForUpdate`, memvalidasi semua nilai terhadap schema, menyimpan nilai/catatan/waktu terbaru, kemudian membuat `AssessmentResultAudit` dengan old/new, IP, dan user-agent. Nilai select di luar opsi, angka nonnumeric, key asing, field wajib kosong, dan teks panjang harus menghasilkan `ValidationException`.

- [ ] **Step 4: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus PASS dengan dua audit dan schema kesehatan tidak dapat dioverride.**
- [ ] **Step 5: Commit:** `git add app/Services/AssessmentLinkService.php app/Models/AssessmentLink.php tests/Feature/AssessmentLinkTest.php; git commit -m "feat: add assessment result service"`.

### Task 3: Manajemen link oleh HR

**Files:**
- Create: `app/Http/Requests/Admin/StoreAssessmentLinkRequest.php`
- Create: `app/Http/Controllers/Admin/AssessmentLinkController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/admin/lamaran/index.blade.php`
- Create: `resources/views/admin/assessment-links/index.blade.php`
- Create: `resources/views/admin/assessment-links/create.blade.php`
- Create: `resources/views/admin/assessment-links/show.blade.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** route `assessment-links.index`, `.create`, `.store`, `.show`, `.deactivate`; request hanya mengizinkan role `admin`.

- [ ] **Step 1: Buat test gagal admin create/deactivate.**

```php
public function test_admin_can_create_and_deactivate_lapangan_link()
{
    $this->withoutMiddleware(VerifyCsrfToken::class)->actingAs($this->admin)->post(route('assessment-links.store'), ['assessment_type' => 'lapangan', 'pin' => '123456', 'selected_ids' => [$this->lamaran->id], 'fields' => [['id' => 'run_time', 'label' => 'Waktu lari', 'type' => 'number', 'required' => true]]])->assertRedirect(route('assessment-links.index'));
    $link = AssessmentLink::firstOrFail();
    $this->assertTrue(Hash::check('123456', $link->pin_hash));
    $this->actingAs($this->admin)->post(route('assessment-links.deactivate', $link))->assertRedirect();
    $this->assertFalse((bool) $link->fresh()->is_active);
}
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus FAIL karena route/controller belum ada.**
- [ ] **Step 3: Tambahkan route admin di grup `/admin` yang sudah memakai `redirect.role`: GET index/create/show dan POST store/deactivate.** `StoreAssessmentLinkRequest::authorize()` wajib `optional($this->user())->role === 'admin'`; rules mencakup PIN 6–32, tipe, selected IDs exists/distinct/minimal satu, dan struktur field. Controller menggunakan service, flash hanya URL publik satu kali, dan tidak pernah flash PIN. View create menerima `selected_ids[]`; builder JS hanya untuk UX. Index menampilkan pembuat/kedaluwarsa/status/jumlah kandidat; show eager-load `lamaran.biodata`, hasil, audit; deactivate menyimpan admin dan waktu.
- [ ] **Step 4: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus PASS.**
- [ ] **Step 5: Commit:** `git add app/Http/Requests/Admin/StoreAssessmentLinkRequest.php app/Http/Controllers/Admin/AssessmentLinkController.php routes/web.php resources/views/admin/lamaran/index.blade.php resources/views/admin/assessment-links tests/Feature/AssessmentLinkTest.php; git commit -m "feat: add admin assessment link management"`.

### Task 4: Form publik ber-PIN

**Files:**
- Create: `app/Http/Requests/PublicAssessmentResultRequest.php`
- Create: `app/Http/Controllers/PublicAssessmentLinkController.php`
- Modify: `routes/web.php`
- Create: `resources/views/public-assessment-links/pin.blade.php`
- Create: `resources/views/public-assessment-links/form.blade.php`
- Test: `tests/Feature/AssessmentLinkTest.php`

**Interfaces:** route `assessment-links.public.show`, `.unlock`, `.results.store`; session key `assessment_link_access.{linkId}`.

- [ ] **Step 1: Buat test gagal untuk PIN gate dan regresi status.**

```php
public function test_petugas_must_unlock_link_and_saving_never_changes_lamaran_status()
{
    $link = $this->createLapanganLink(); $candidate = $link->candidates()->first();
    $before = $candidate->lamaran->only(['status_proses', 'status_lamaran']);
    $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate->id]), ['values' => ['run_time' => '12.8']])->assertForbidden();
    $this->post(route('assessment-links.public.unlock', $link->public_token), ['pin' => '123456'])->assertRedirect();
    $this->post(route('assessment-links.public.results.store', [$link->public_token, $candidate->id]), ['values' => ['run_time' => '12.8'], 'petugas_note' => 'Lancar'])->assertRedirect();
    $this->assertSame($before, $candidate->lamaran->fresh()->only(['status_proses', 'status_lamaran']));
}
```

- [ ] **Step 2: Jalankan `php artisan test --filter=AssessmentLinkTest`; harus FAIL karena route/controller belum ada.**
- [ ] **Step 3: Implementasikan GET `/penilaian/{token}`, POST `/unlock` dengan `throttle:5,1`, dan POST hasil kandidat dengan `throttle:30,1`.** `show()` harus 404 untuk token invalid/nonaktif/kedaluwarsa dan menampilkan PIN jika session belum unlock. `unlock()` memakai `Hash::check`, memberi pesan generik bila salah, lalu menyimpan session key. `storeResult()` wajib memastikan session, link aktif, dan candidate benar milik link sebelum memanggil service; gunakan 403 untuk session tak ada dan 404 untuk identitas tak valid. Blade menggunakan `e()` pada label, `step="any"` untuk number, select dari schema, textarea `petugas_note`, feedback sukses/gagal per kandidat.
- [ ] **Step 4: Tambahkan test expiry/deactivation:** `get(route('assessment-links.public.show', $expired->public_token))->assertNotFound();` dan jalankan `php artisan test --filter=AssessmentLinkTest`; harus PASS termasuk select invalid, candidate asing, PIN salah, expiry, audit, serta regresi status.
- [ ] **Step 5: Commit:** `git add app/Http/Requests/PublicAssessmentResultRequest.php app/Http/Controllers/PublicAssessmentLinkController.php routes/web.php resources/views/public-assessment-links tests/Feature/AssessmentLinkTest.php; git commit -m "feat: add PIN-protected assessment form"`.

### Task 5: Verifikasi akhir

**Files:**
- Modify: `tests/Feature/AssessmentLinkTest.php` hanya bila perbaikan determinisme diperlukan.

- [ ] **Step 1: Jalankan syntax check:** `php -l app/Services/AssessmentLinkService.php; php -l app/Http/Controllers/Admin/AssessmentLinkController.php; php -l app/Http/Controllers/PublicAssessmentLinkController.php`. Expected: tiga kali `No syntax errors detected`.
- [ ] **Step 2: Jalankan test:** `php artisan test tests/Feature/AssessmentLinkTest.php; php artisan test --filter=VhirePkwtIntegrationTest`. Expected: semua PASS.
- [ ] **Step 3: Smoke test manual:** login admin, pilih dua kandidat, buat kesehatan ber-PIN dan field tambahan; buka URL incognito, uji PIN salah/benar, simpan kandidat dua kali; cek detail admin untuk nilai terbaru dan dua audit; cek status lamaran tetap; nonaktifkan lalu pastikan URL 404; buat lapangan dengan hanya `Waktu lari` lalu schema dropdown berbeda untuk memastikan konfigurasi per link.
- [ ] **Step 4: Jika Step 1–3 mengubah file, commit:** `git add tests/Feature/AssessmentLinkTest.php; git commit -m "test: cover assessment link workflow"`. Jangan membuat commit kosong.

## Self-Review

- Task 1–2 mencakup data dinamis, kesehatan wajib, end-of-day, audit, dan transaksi.
- Task 3 mencakup pembuatan/peninjauan/nonaktifkan oleh HR dan pemilihan kandidat lama.
- Task 4 mencakup token, PIN, throttle, validasi, dan jaminan status lamaran tidak berubah.
- Task 5 mencakup lint, test regresi, dan alur manual production-like.
