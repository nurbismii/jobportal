@extends('layouts.app-pic')

@section('content-admin')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0">Detail Pelamar</h4>
                </div>

                <div class="card-body p-4">
                    <!-- Informasi Akun -->
                    <h5 class="fw-bold mb-3 text-primary">Informasi Akun</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>No. KTP:</strong> {{ $user->no_ktp }}</p>
                            <p><strong>Nama:</strong> {{ $user->name }}</p>
                            <p><strong>Email:</strong> {{ $user->email }}</p>
                        </div>

                        <div class="col-md-6">
                            <p><strong>Status Akun:</strong> {{ $user->status_akun ? 'Aktif' : 'Non Aktif' }}</p>
                            <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
                            <p><strong>Tanggal Dibuat:</strong> {{ $user->created_at }}</p>
                            <p><strong>Rekomendasi:</strong> {{ $user->rekomendasi }}</p>
                        </div>
                    </div>

                    <hr>

                    <!-- Biodata -->
                    <h5 class="fw-bold mb-3 text-primary">Biodata Pelamar</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>No. KTP:</strong> {{ $user->biodataUser->no_ktp ?? '-' }}</p>
                        </div>
                    </div>

                    <hr>

                    <!-- Riwayat Lamaran -->
                    <h5 class="fw-bold mb-3 text-primary">Riwayat Lamaran</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lowongan</th>
                                    <th>Status Proses</th>
                                    <th>Tanggal Lamar</th>
                                    <th>Update Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($user->biodataUser && $user->biodataUser->getRiwayatLamaran->count() > 0)
                                @foreach ($user->biodataUser->getRiwayatLamaran as $index => $riwayat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $riwayat->lowongan->nama_lowongan }}</td>
                                    <td class="text-danger fw-bold">{{ $riwayat->status_proses }}</td>
                                    <td>{{ $riwayat->created_at }}</td>
                                    <td>{{ $riwayat->updated_at }}</td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada data riwayat lamaran</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer text-end bg-light">
                    <a href="{{ route('pengguna.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection