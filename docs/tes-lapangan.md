# Tes lapangan alat berat

Fitur menggunakan link asesmen dan hubungan `lamaran_id` yang sudah ada. Form umum lama dan tes kesehatan tetap dapat digunakan.

## Persiapan HR

1. HR membuka **Link Asesmen → Buat Link**, lalu memilih **Lapangan**, jenis alat, lokasi, tanggal, masa berlaku link, dan kandidat yang berada pada tahap **Tes Lapangan**. Penilai 1, Penilai 2, dan penanggung jawab divisi tidak memerlukan akun login.
2. Isi **nama lengkap** Penilai 1, Penilai 2, dan penanggung jawab divisi langsung pada form pembuatan tes. Ketiga nama harus berbeda. Petugas tidak memerlukan akun login atau PIN penandatangan. Nama tetap tersedia jika validasi formulir gagal.
3. Periksa nilai maksimum setiap aspek. Excavator/Loader mengikuti bobot 20/40/20/20, Mobile Crane 10/60/20/10, dan Trailer 20/40/20/20. Masing-masing total 100 poin, dengan urutan P2H / mengemudi / keselamatan / pemeliharaan. Dump Truck wajib diisi bobotnya. Tidak ada ambang kelulusan yang diasumsikan aplikasi. Divisi perlu mengesahkan rubrik serta kesesuaian indikator Trailer sebelum dipakai.
4. Bagikan tautan dan **PIN pembuka link** hanya kepada petugas yang berwenang. PIN ini digunakan untuk membuka daftar kandidat. Setelah link terbuka, petugas cukup memeriksa nama, menggambar tanda tangan, dan menyimpan tanpa PIN tambahan. Pemegang akses link dapat menandatangani dan meminta revisi atas nama petugas yang ditugaskan; tidak ada autentikasi identitas penanda tangan secara terpisah.

## Pelaksanaan

Label, komponen, jawaban, petunjuk, dan status pada form petugas serta laporan cetak ditampilkan dalam bahasa Indonesia dan Mandarin sederhana (简体中文). Nama, lokasi, dan catatan bebas tetap sesuai isian petugas; tidak diterjemahkan otomatis. Jawaban yang disimpan dan bobot penilaian tetap memakai format asli sehingga hasil lama tetap kompatibel. Panjang teks dua bahasa dapat menambah jumlah halaman PDF.

- Petugas memilih kandidat, mengonfirmasi dokumen, kemudian memilih **Ya/Tidak untuk setiap komponen**. Ya berarti pemeriksaan/tindakan dilakukan dengan benar dan menghasilkan poin; Tidak menghasilkan nol. Setiap komponen dalam aspek yang sama berbobot sama. Nilai aspek = jumlah Ya ÷ jumlah komponen × poin maksimum aspek, dibulatkan dua desimal setelah perhitungan aspek. Contoh P2H Excavator/Loader: 6 komponen, maksimum 20; 5 Ya menghasilkan 16,67, sedangkan 6 Ya menghasilkan 20. Spion samping dan spion dalam adalah dua komponen terpisah.
- Pilihan kosong berarti **belum dinilai**, bukan Tidak. Draf boleh belum lengkap, tetapi semua komponen wajib dijawab sebelum tanda tangan. Nilai dihitung ulang oleh server sehingga poin dari perangkat tidak dapat dimanipulasi. Hasil Lulus/Tidak Lulus tetap diputuskan penilai dan alasan wajib ditulis untuk hasil Tidak Lulus.
- Daftar komponen serta bobot disimpan bersama hasil untuk menjaga riwayat perhitungan. Hasil format angka lama yang sudah ditandatangani tetap ditampilkan dengan nilainya semula. Bila dibuka sebagai revisi, petugas melengkapi pilihan Ya/Tidak untuk perhitungan baru; skor lama tidak dianggap sebagai jawaban komponen.
- Draf disimpan otomatis setelah perubahan berhenti sejenak. Status **Tersimpan** baru muncul setelah server berhasil menyimpan. Jika gagal, isian dipertahankan di halaman dan tersedia tombol **Simpan draf** untuk mencoba lagi. Hindari menutup halaman saat status belum tersimpan. Mode luring penuh belum tersedia.
- Penilai 1 memeriksa ringkasan dan namanya, menandatangani dengan jari/mouse (atau nama), lalu menyetujui pernyataan dan menyimpan. Tanda tangan kosong atau persetujuan yang belum dicentang ditolak. Nilai kemudian terkunci.
- Form yang sama otomatis beralih ke Penilai 2, kemudian penanggung jawab divisi. Mereka dapat bergantian menggunakan perangkat yang sama atau membuka tautan di perangkat berbeda. Tidak ada permintaan PIN penandatangan.
- Hasil menjadi final hanya setelah tiga tanda tangan. Hasil tersedia pada detail link asesmen dan tahapan lamaran sesuai izin modul. Tahapan rekrutmen tidak dipindahkan otomatis.
- Koreksi dilakukan melalui **Kembalikan untuk perbaikan**, dengan memilih nama/peran petugas dan mengisi alasan. Alasan ditampilkan kepada pengisi. Versi lama beserta berkas tanda tangannya tersimpan dalam audit; revisi baru memerlukan tiga tanda tangan ulang. Cetakan lama tetap menggambarkan versi lama.
- **Laporan / Simpan PDF** membuka laporan yang dapat dicetak atau disimpan sebagai PDF melalui dialog cetak browser. Hasil belum final diberi label DRAF.

## Penerapan

Jalankan migrasi berikut pada server aplikasi sebelum membuka fitur baru:

```sh
php artisan migrate --path=database/migrations/2026_11_11_000000_add_field_test_workflow.php --force
```

Muat ulang cache konfigurasi/rute/view sesuai prosedur deployment proyek. Berkas tanda tangan disimpan di disk privat `assessment_private`; pastikan direktori storage dapat ditulis dan dicadangkan bersama database. Tanda tangan tidak dipublikasikan melalui direktori public.

Penguncian transaksi dan nomor versi mencegah penimpaan dari perangkat lain. Permintaan tanda tangan ulang untuk pihak/revisi yang sama mengembalikan tanda tangan yang sudah ada. Sesi link yang belum dibuka, kandidat di link lain, link kedaluwarsa/nonaktif, dan endpoint hasil lama tidak dapat mengesahkan hasil baru. Link lama juga menggunakan alur tanpa PIN penandatangan. Tanda tangan yang sudah tersimpan tetap dipertahankan; tanda tangan baru mencatat nama penugasan tanpa mengklaim login pengguna dan tidak membuat atau mengubah akun.

Penambahan database bersifat aditif. Rollback migrasi menghapus kolom konfigurasi/PIN/tanda tangan baru; jangan rollback setelah fitur dipakai tanpa mencadangkan data dan berkas terlebih dahulu.

## Pemeriksaan

`FieldTestWorkflowTest` mencakup penugasan nama tanpa akun/PIN penandatangan, draf, nilai nol/batas nilai, konflik versi, urutan tanda tangan, persetujuan, pengiriman ulang, kelulusan final, revisi/audit, PIN pembuka link, otorisasi laporan, dan kandidat lintas link. Gunakan database SQLite sintetis untuk pengujian; jangan menyiapkan akun atau hasil uji pada database kandidat asli.
