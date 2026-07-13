# Task 3 — Manajemen Link Asesmen HR

## Implementasi

- Menambahkan lima route admin di dalam grup `admin` dengan middleware `redirect.role`:
  `assessment-links.index`, `create`, `store`, `show`, dan `deactivate`.
- Menambahkan `StoreAssessmentLinkRequest` dengan otorisasi persis
  `optional($this->user())->role === 'admin'`, validasi tipe asesmen, PIN 6–32 karakter,
  kandidat terpilih unik dan valid, serta struktur dasar field tambahan.
- Menambahkan `AssessmentLinkController` untuk daftar, formulir, penyimpanan melalui
  `AssessmentLinkService::create()`, detail eager-loaded, dan penonaktifan idempoten.
  PIN tidak pernah di-flash atau dirender; hanya URL publik yang di-flash satu kali.
- Menambahkan Blade Bootstrap responsif untuk daftar, pembuatan field dinamis, URL salin,
  hasil terbaru, dan audit nilai lama/revisi.
- Menambahkan tombol pada daftar lamaran yang meneruskan `selected_ids[]` ke formulir create;
  tombol tersebut memakai GET ke route baru dan tidak memutasi status lamaran.
- Menambahkan test HTTP admin yang membuat link lapangan dengan satu field angka, memastikan
  PIN ter-hash, lalu menonaktifkan link.

## TDD

1. Test baru dijalankan sebelum implementasi dan gagal seperti yang diharapkan karena route
   `assessment-links.store` belum didefinisikan.
2. Setelah implementasi, test sempat mencapai middleware sesi dan menemukan test environment
   tanpa `APP_KEY`; setup test diberi key lokal deterministik agar pengujian HTTP dapat berjalan.
3. Focused GREEN:
   `php artisan test tests/Feature/AssessmentLinkTest.php --filter=admin_can_create_and_deactivate_a_lapangan_assessment_link`
   menghasilkan 1 test lulus.

## Validasi

- `php artisan test tests/Feature/AssessmentLinkTest.php` — **5 passed**.
- `php -l app/Http/Controllers/Admin/AssessmentLinkController.php` — tidak ada error sintaks.
- `php -l app/Http/Requests/Admin/StoreAssessmentLinkRequest.php` — tidak ada error sintaks.
- `php artisan route:list --name=assessment-links` — kelima named route terdaftar di bawah
  middleware `web` dan `redirect.role`.
- `git diff --check` — tidak ada whitespace error.

## Suite Penuh

`php artisan test` menghasilkan **27 passed, 8 failed**. Ini sama dengan baseline yang dilaporkan;
tidak ada kegagalan baru dari Task 3.

Kegagalan baseline tetap:

- 2 test `AdminTermsApprovalProofTest` karena `No application encryption key has been specified`.
- `Feature/ExampleTest` dengan penyebab `APP_KEY` yang sama.
- `VersionedAssetTest` karena `public/user/css/vhire-custom.css` tidak ada.
- 4 test `VhirePkwtIntegrationTest`: 1 ketidaksesuaian nilai `status_karyawan` (`PKWT` vs
  `PKWT 合同工`) dan 3 karena `APP_KEY` tidak tersedia.

## Catatan

- URL publik menggunakan pola `/assessment/{public_token}` tetapi tidak menambahkan route petugas
  publik, sesuai batas scope Task 3. Route publik dapat ditambahkan pada task berikutnya.
- Form dinamis adalah bantuan UX. Service tetap melakukan normalisasi dan validasi schema sebagai
  source of truth.
