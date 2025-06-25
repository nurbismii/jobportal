Halo {{ $user->name }},

Status terbaru lamaran Anda untuk posisi:

{{ $lamaran->lowongan->nama_lowongan ?? 'Posisi tidak tersedia' }}

Status proses Anda saat ini:

{{ $status }}

Silakan login ke portal rekrutmen untuk informasi lebih lanjut.