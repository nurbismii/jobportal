# Desain Link Penilaian Tes Kandidat

## Tujuan

Memungkinkan HR membuat tautan penilaian terbatas untuk kandidat yang dipilih. Petugas lapangan membuka tautan tersebut, memasukkan PIN, lalu mengisi hasil tes tanpa dapat mengubah status proses lamaran. HR meninjau hasil di V-Hire dan tetap menentukan sendiri kandidat yang lanjut ke tahap berikutnya.

## Ruang Lingkup

- HR admin membuat link dari kandidat yang dipilih pada daftar lamaran.
- Link memiliki tipe `kesehatan` atau `lapangan`.
- Kedua tipe memakai konfigurasi form dinamis per link.
- Link kesehatan selalu mempunyai satu poin wajib bernama `Hasil tes kesehatan`, bertipe pilihan, dengan opsi tetap `Sehat` dan `Tidak Sehat`. Poin ini tidak dapat dihapus atau diubah opsinya.
- HR dapat menambah poin untuk kedua tipe tes dan menentukan label serta tipe input: pilihan, angka, atau teks.
- Petugas dapat memperbarui hasil sampai link kedaluwarsa.
- Semua perubahan hasil direkam sebagai audit trail.
- Link kedaluwarsa pada pukul 23:59:59 (zona waktu aplikasi Asia/Makassar) di tanggal pembuatannya dan dapat dinonaktifkan lebih awal oleh HR.

## Di Luar Ruang Lingkup

- Link tidak memindahkan `status_proses`, `status_lamaran`, atau riwayat proses kandidat.
- Link tidak memerlukan akun/login petugas.
- Template form yang dapat digunakan ulang tidak dibuat pada tahap awal.
- Penilaian otomatis atau aturan kelulusan berdasarkan nilai tidak dibuat pada tahap awal.

## Arsitektur dan Data

Gunakan tiga kelompok data baru:

1. `assessment_links` menyimpan tipe link, token publik acak, hash PIN, pembuat, waktu kedaluwarsa, status aktif, dan konfigurasi field JSON.
2. `assessment_link_candidates` menghubungkan link dengan `lamaran` yang dipilih HR dan menyimpan hasil form kandidat dalam JSON serta waktu pengisian terakhir.
3. `assessment_result_audits` menyimpan setiap nilai sebelum dan sesudah perubahan, kandidat, waktu, dan konteks link.

Konfigurasi field disimpan sebagai JSON terstruktur supaya label dan jenis penilaian dapat berubah tanpa menambah kolom database. Nilai hasil juga menggunakan kunci field yang stabil (UUID), bukan label, agar perubahan label tidak memutus data historis.

Contoh konfigurasi tes lapangan:

```json
[
  {"id":"uuid-1","label":"Waktu lari 100 m","type":"number","required":true},
  {"id":"uuid-2","label":"Catatan petugas","type":"text","required":false}
]
```

## Akses dan Keamanan

- URL hanya memakai token acak berentropi tinggi; ID database tidak diekspos.
- PIN disimpan dengan `Hash::make` dan diverifikasi dengan `Hash::check`.
- Akses form memerlukan token valid, link aktif, belum kedaluwarsa, dan PIN terverifikasi.
- PIN tidak pernah dikirim kembali dalam respons maupun disimpan sebagai plaintext.
- Endpoint publik diberi throttle untuk percobaan PIN dan penyimpanan hasil.
- Semua input divalidasi berdasarkan konfigurasi field server-side; browser hanya membantu UX.
- Kandidat pada URL harus terikat pada link, sehingga hasil tidak dapat disimpan untuk kandidat lain.

## Alur

1. HR memilih kandidat pada daftar lamaran dan memilih tindakan “Buat Link Penilaian”.
2. HR memilih tipe tes dan menyusun field. Untuk tes kesehatan, field hasil standar dibuat otomatis.
3. HR mengatur PIN dan membuat link. Sistem menetapkan waktu kedaluwarsa akhir hari.
4. Petugas membuka link dan memasukkan PIN.
5. Petugas mengisi atau memperbarui hasil masing-masing kandidat selama link aktif.
6. Sistem menyimpan hasil terbaru dan audit perubahan secara atomik.
7. HR melihat ringkasan serta detail hasil dan menggunakan aksi perubahan status massal yang sudah ada untuk melanjutkan kandidat.

## Antarmuka

- Tambah aksi pembuatan link pada halaman admin daftar lamaran; hanya kandidat yang dipilih dapat dilampirkan.
- Tambah halaman admin daftar link: tipe, kandidat, status aktif/kedaluwarsa, pembuat, waktu kedaluwarsa, dan aksi lihat hasil/nonaktifkan.
- Tambah halaman detail hasil untuk melihat nilai tiap kandidat serta riwayat perubahan.
- Halaman publik memuat PIN gate, daftar kandidat, field yang dikonfigurasi, validasi jelas, status simpan berhasil/gagal, dan kondisi link tidak valid atau kedaluwarsa.

## Penanganan Error

- Token tidak ditemukan, link nonaktif, atau link kedaluwarsa: tampilkan halaman tidak dapat diakses tanpa membocorkan data kandidat.
- PIN salah: tampilkan pesan generik dan terapkan throttle.
- Nilai tidak valid: tampilkan kesalahan pada field terkait, tanpa menyimpan perubahan parsial.
- Konflik perubahan bersamaan: simpan transaksi tunggal per kandidat dan catat audit berdasarkan nilai yang benar-benar tersimpan.

## Pengujian

- Feature test pembuatan link, token/PIN yang aman, masa berlaku akhir hari, dan penonaktifan HR.
- Feature test akses publik: PIN salah, throttle, token invalid, nonaktif, dan kedaluwarsa.
- Feature test validasi field dinamis untuk pilihan, angka, teks, dan field wajib.
- Feature test hasil kesehatan dengan field wajib `Sehat/Tidak Sehat`.
- Feature test pembaruan hasil dan audit nilai sebelum/sesudah.
- Regression test bahwa penyimpanan hasil tidak mengubah `lamaran.status_proses` atau `status_lamaran`.
