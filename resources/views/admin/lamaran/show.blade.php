@extends('layouts.app-pic')

@section('content-admin')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h1 class="h3 text-gray-800 mb-2">Tahapan Proses Pelamar</h1>
    <a href="{{ $lamaran->lowongan ? route('directToLamaran', $lamaran->loker_id) : route('lamarans.index') }}" class="btn btn-outline-secondary mb-2">Kembali ke Daftar Lamaran</a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h2 class="h6 m-0 font-weight-bold text-primary">Detail Lamaran</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Nama Pelamar:</strong> {{ $lamaran->biodata?->user?->name ?: '-' }}</p>
                <p><strong>No. KTP:</strong> {{ $lamaran->biodata?->no_ktp ?: '-' }}</p>
                <p class="mb-md-0"><strong>Lowongan:</strong> {{ $lamaran->lowongan?->nama_lowongan ?: '-' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Status Lamaran:</strong> {{ $lamaran->status_lamaran == 1 ? 'Dalam Proses Rekrutmen' : 'Rekrutmen Selesai' }}</p>
                <p><strong>Status Proses Saat Ini:</strong> {{ $lamaran->status_proses ?: '-' }}</p>
                <p class="mb-0"><strong>Tanggal Lamar:</strong> {{ $lamaran->created_at?->format('d-m-Y H:i') ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h2 class="h6 m-0 font-weight-bold text-primary">Riwayat Tahapan Proses</h2>
    </div>
    <div class="card-body">
        <p class="text-muted">Riwayat ditampilkan dari yang terbaru, termasuk tahapan Tidak Lolos.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th scope="col">Tahapan Proses</th>
                        <th scope="col">Hasil</th>
                        <th scope="col">Tanggal Proses</th>
                        <th scope="col">Jam</th>
                        <th scope="col">Tempat</th>
                        <th scope="col">Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lamaran->riwayatProsesLamaran as $proses)
                    <tr>
                        <td>
                            {{ $proses->status_proses ?: '-' }}
                            @if($loop->first)
                            <span class="badge badge-primary">Terbaru</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $proses->status_lolos === 'Tidak Lolos' ? 'badge-danger' : 'badge-secondary' }}">{{ $proses->status_lolos ?: '-' }}</span></td>
                        <td>{{ $proses->tanggal_proses ? tanggalIndoHari($proses->tanggal_proses) : '-' }}</td>
                        <td class="text-nowrap">{{ $proses->jam ?: '-' }}</td>
                        <td>{{ $proses->tempat ?: '-' }}</td>
                        <td>{!! \App\Mail\HrBlastEmail::sanitizeMessage($proses->pesan ?: '-') !!}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada riwayat proses lamaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
