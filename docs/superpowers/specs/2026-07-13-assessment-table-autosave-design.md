# Desain Tabel dan Autosave Asesmen Petugas

## Tujuan

Mengganti tampilan kartu kandidat pada halaman publik asesmen menjadi tabel yang tetap nyaman untuk lebih dari 100 kandidat. Petugas mencari kandidat melalui nama atau nomor KTP, mengisi hasil langsung di tabel, dan data tersimpan otomatis.

## Antarmuka

- Tabel berisi nomor, nomor KTP, nama, seluruh field schema asesmen, catatan petugas, dan status simpan.
- Field kesehatan bawaan tampil jelas sebagai kolom `Hasil tes kesehatan`; field lapangan dinamis tampil sebagai kolom sesuai schema link.
- Pencarian nama/nomor KTP dilakukan server-side dengan debounce dan autocomplete.
- Tabel memakai pagination 25 kandidat per halaman dan filter status `Semua`, `Belum diisi`, atau `Sudah diisi`.
- Input tidak memiliki tombol simpan manual. Setelah pengguna berhenti mengetik/mengubah nilai selama 700 ms, browser menyimpan seluruh nilai kandidat tersebut.
- Status per baris: `Menyimpan…`, `Tersimpan`, `Gagal disimpan`, dengan tombol `Coba lagi` bila gagal.

## Data dan Keamanan

- Hasil tetap disimpan per kandidat dengan service audit yang ada; satu autosave mengirim seluruh `values` dan `petugas_note` kandidat untuk menghasilkan satu audit atomik.
- Endpoint pencarian dan autosave tetap memakai token link, akses sesi PIN, throttle, validasi schema, serta pembatasan kandidat terhadap link.
- Endpoint pencarian tidak mengembalikan kandidat jika link tidak aktif/kedaluwarsa atau sesi PIN belum valid.

## Pengujian

- Test endpoint daftar/pencarian memverifikasi akses sesi, filter nama/KTP, status hasil, dan pagination.
- Test autosave memverifikasi payload satu kandidat, respons JSON status sukses, audit, dan status lamaran tidak berubah.
- Test akses invalid/nonaktif/kedaluwarsa tetap 404 atau 403 tanpa kebocoran kandidat.
