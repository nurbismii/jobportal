@extends('layouts.app-pic')

@section('content-admin')
<div class="container-fluid px-0 px-md-3">

    <div class="card shadow-sm rounded">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Permintaan Tenaga Kerja (PTK)</h5>
            <span class="badge badge-{{ $permintaanTenagaKerja->status_ptk == 'Menunggu' ? 'warning' : 'success' }}">
                {{ $permintaanTenagaKerja->status_ptk }}
            </span>
        </div>
        <div class="card-body">
            <p class="mb-3"><strong>No. Surat PTK</strong> {{ $permintaanTenagaKerja->no_surat_ptk }}</p>

            {{-- Progress Bar --}}
            <div class="mb-4">
                <p class="mb-1"><strong>Progress Rekrutmen:</strong></p>
                <div class="progress" style="height: 20px;">
                    @php
                    $total = $permintaanTenagaKerja->jumlah_ptk;
                    $persen = $total > 0 ? min(100, round(($jumlahMasuk / $total) * 100)) : 0;
                    $warna = $persen < 50 ? 'bg-warning' : 'bg-success' ;
                        @endphp
                        <div class="progress-bar {{ $warna }}" role="progressbar" style="width: {{ $persen }}%;" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $persen }}%
                </div>
            </div>
        </div>

        {{-- Collapse Sections --}}
        <div id="accordion">
            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapsePosisi" class="text-white d-block" data-toggle="collapse" aria-expanded="true" aria-controls="collapsePosisi">
                            <i class="fa fa-briefcase"></i> Informasi Posisi
                        </a>
                    </h6>
                </div>
                <div id="collapsePosisi" class="collapse show">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead><tr><th>Divisi</th><th>Posisi</th><th>Jumlah Permintaan</th><th>Jenis Kelamin</th><th>Usia</th><th>Background Pendidikan</th></tr></thead>
                                <tbody>
                                    @foreach ($permintaanTenagaKerja->rincian_permintaan as $rincian)
                                    <tr>
                                        <td>{{ optional($divisis->get($rincian['divisi'] ?? null))->nama_divisi ?? '-' }}</td>
                                        <td>{{ $rincian['posisi'] }}</td>
                                        <td>{{ $rincian['jumlah_ptk'] }}</td>
                                        <td>{{ $rincian['jenis_kelamin'] }}</td>
                                        <td>{{ $rincian['rentang_usia'] }}</td>
                                        <td>{{ $rincian['background_pendidikan'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p><i class="fa fa-users"></i> <strong>Jumlah PTK:</strong> {{ $permintaanTenagaKerja->jumlah_ptk }}</p>
                        <p>
                            <i class="fa fa-user-check"></i> <strong>Jumlah Masuk:</strong> {{ $jumlahMasuk }}
                            @if ($riwayatKandidat?->total())
                            <a href="#riwayat-kandidat" class="ml-2">Lihat kandidat</a>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseTanggal" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseTanggal">
                            <i class="fa fa-calendar"></i> Jadwal & Tanggal
                        </a>
                    </h6>
                </div>
                <div id="collapseTanggal" class="collapse">
                    <div class="card-body">
                        <p><i class="fa fa-calendar-plus"></i> <strong>Tanggal Pengajuan:</strong> {{ tanggalIndo($permintaanTenagaKerja->tanggal_pengajuan) }}</p>
                        <p><i class="fa fa-calendar-check"></i> <strong>Tanggal Terima:</strong> {{ tanggalIndo($permintaanTenagaKerja->tanggal_terima) }}</p>
                        <p><i class="fa fa-clock"></i> <strong>Dibuat Pada:</strong> {{ tanggalIndo(substr($permintaanTenagaKerja->created_at, 0, 10)) }}</p>
                        <p><i class="fa fa-history"></i> <strong>Diupdate Pada:</strong> {{ tanggalIndo(substr($permintaanTenagaKerja->updated_at, 0, 10)) }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseDept" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseDept">
                            <i class="fa fa-building"></i> Departemen & Divisi
                        </a>
                    </h6>
                </div>
                <div id="collapseDept" class="collapse">
                    <div class="card-body">
                        <p><i class="fa fa-industry"></i> <strong>Departemen:</strong> {{ optional($permintaanTenagaKerja->departemen)->departemen }} ({{ optional($permintaanTenagaKerja->departemen)->status_pengeluaran }})</p>
                        <p><i class="fa fa-sitemap"></i> <strong>Divisi:</strong> {{ count($permintaanTenagaKerja->rincian_permintaan) > 1 ? 'Lihat rincian posisi' : (optional($permintaanTenagaKerja->divisi)->nama_divisi ?? '-') }}</p>
                        <p><i class="fa fa-user-tie"></i> <strong>Kepala Departemen:</strong> {{ optional($permintaanTenagaKerja->departemen)->kepala_dept }}</p>
                        <p><i class="fa fa-phone"></i> <strong>Telp:</strong> {{ optional($permintaanTenagaKerja->departemen)->no_telp_dept }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseKualifikasi" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseKualifikasi">
                            <i class="fa fa-file-alt"></i> Kualifikasi PTK
                        </a>
                    </h6>
                </div>
                <div id="collapseKualifikasi" class="collapse">
                    <div class="card-body">
                        @foreach ($permintaanTenagaKerja->rincian_permintaan as $index => $rincian)
                        <div class="border rounded p-3 mb-3">
                            <h6 class="font-weight-bold">Rincian {{ $index + 1 }} — {{ $rincian['posisi'] }}</h6>
                            {!! \App\Mail\HrBlastEmail::sanitizeMessage($rincian['kualifikasi_ptk'] ?? '') !!}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if ($riwayatKandidat !== null)
        <section class="card mt-3" id="riwayat-kandidat">
            <div class="card-header py-2">
                <h2 class="h6 mb-0 font-weight-bold text-primary" id="judul-riwayat-kandidat">Riwayat Kandidat Masuk</h2>
            </div>
            <div class="card-body p-2 p-md-3">
                @if ($riwayatKandidat->isNotEmpty())
                <div class="table-responsive" tabindex="0" role="region" aria-labelledby="judul-riwayat-kandidat">
                    <table class="table table-bordered table-sm text-gray-800 mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Nama Kandidat</th>
                                <th scope="col" class="d-none d-md-table-cell">Lowongan</th>
                                <th scope="col" class="d-none d-md-table-cell">Tanggal Aktif Bekerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatKandidat as $lamaran)
                            @php
                            $namaKandidat = $lamaran->biodata?->user?->name ?: 'Lamaran #' . $lamaran->id;
                            $namaLowongan = $lamaran->lowongan?->nama_lowongan ?: '—';
                            $tanggalAktif = substr((string) $lamaran->tanggal_aktif_bekerja, 0, 10);
                            $tanggal = date_create_from_format('!Y-m-d', $tanggalAktif);
                            $tanggalTampil = $tanggal && $tanggal->format('Y-m-d') === $tanggalAktif ? tanggalIndo($tanggalAktif) : '—';
                            @endphp
                            <tr>
                                <td class="text-break">
                                    <a class="font-weight-bold" href="{{ route('lamarans.show', $lamaran->id) }}"><span class="sr-only">Lihat tahapan </span>{{ $namaKandidat }}</a>
                                    <div class="d-md-none small">{{ $namaLowongan }}<br>Tanggal aktif {{ $tanggalTampil }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $namaLowongan }}</td>
                                <td class="d-none d-md-table-cell text-nowrap">{{ $tanggalTampil }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">{{ $riwayatKandidat->total() ? 'Tidak ada kandidat di halaman ini.' : 'Belum ada kandidat yang tercatat aktif bekerja pada PTK ini.' }}</p>
                @endif
                @if ($riwayatKandidat->hasPages())
                <div class="mt-3">{{ $riwayatKandidat->links() }}</div>
                @endif
            </div>
        </section>
        @endif

        <div class="mt-3">
            @if(auth()->user()->canAccessAdminRoute('permintaan-tenaga-kerja.index'))
            <a href="{{ route('permintaan-tenaga-kerja.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
