@extends('layouts.app-pic')

@section('content-admin')
<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between mb-3">
    <h2 class="m-0 font-weight-bold text-primary">Kontrak PKWT 1</h2>
    <a href="{{ route('pkwt-contract-settings.edit') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-cog"></i> Pengaturan Durasi
    </a>
</div>

<div class="card shadow mb-3">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4 mb-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, kode, no PKWT, NIK HRIS" value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2">
                <select name="signing_method" class="form-control form-control-sm">
                    <option value="">Semua metode</option>
                    <option value="electronic" {{ request('signing_method') === 'electronic' ? 'selected' : '' }}>Electronic</option>
                    <option value="manual" {{ request('signing_method') === 'manual' ? 'selected' : '' }}>Manual</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <select name="signature_status" class="form-control form-control-sm">
                    <option value="">Semua status tanda tangan</option>
                    @foreach(['draft', 'waiting_signature', 'signed', 'rejected', 'cancelled'] as $status)
                    <option value="{{ $status }}" {{ request('signature_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

@if($failedOnboardingCandidates->isNotEmpty())
<div class="card shadow mb-3 border-left-warning">
    <div class="card-header font-weight-bold text-warning">Sync Onboarding Gagal</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Kandidat</th>
                        <th>No KTP</th>
                        <th>Error</th>
                        <th>Percobaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedOnboardingCandidates as $candidate)
                    <tr>
                        <td>{{ $candidate->candidate_code }} - {{ $candidate->nama }}</td>
                        <td>{{ $candidate->masked_no_ktp }}</td>
                        <td>{{ $candidate->last_sync_error }}</td>
                        <td>{{ $candidate->retry_count }}</td>
                        <td>
                            <form method="POST" action="{{ route('pkwt-onboarding-candidates.retry-sync', $candidate->id) }}">
                                @csrf
                                <button class="btn btn-warning btn-sm">Retry</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Kandidat</th>
                        <th>Kontrak</th>
                        <th>Periode</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Visible</th>
                        <th>NIK HRIS</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                    <tr>
                        <td>
                            <div class="font-weight-bold">{{ $contract->nama }}</div>
                            <div class="small text-muted">{{ $contract->candidate_code }}</div>
                            <div class="small text-muted">{{ $contract->masked_no_ktp }}</div>
                        </td>
                        <td>
                            <div>{{ $contract->kode_kontrak ?: '-' }}</div>
                            <div class="small text-muted">{{ $contract->no_pkwt ?: '-' }}</div>
                            <div class="small">{{ $contract->jabatan ?: '-' }}</div>
                        </td>
                        <td>
                            <div>{{ optional($contract->tanggal_mulai_kontrak)->format('d-m-Y') ?: '-' }}</div>
                            <div class="small text-muted">s/d {{ optional($contract->tanggal_akhir_kontrak)->format('d-m-Y') ?: '-' }}</div>
                            <div class="small">{{ $contract->durasi_kontrak ?: '-' }}</div>
                        </td>
                        <td>{{ ucfirst($contract->signing_method) }}</td>
                        <td>
                            <span class="badge badge-info">{{ $contract->signature_status }}</span>
                            @if($contract->signed_at)
                            <div class="small text-muted">{{ $contract->signed_at->format('d-m-Y H:i') }}</div>
                            @endif
                        </td>
                        <td>
                            @if($contract->visible_in_vhire)
                            <span class="badge badge-success">Visible</span>
                            @else
                            <span class="badge badge-secondary">Hidden</span>
                            <div class="small text-muted">{{ $contract->hidden_reason }}</div>
                            @endif
                        </td>
                        <td>{{ $contract->employee_nik ?: '-' }}</td>
                        <td style="min-width: 240px">
                            <form method="POST" action="{{ route('pkwt-contracts.visibility', $contract->id) }}" class="mb-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="visible_in_vhire" value="{{ $contract->visible_in_vhire ? 0 : 1 }}">
                                @if($contract->visible_in_vhire)
                                <input type="text" name="hidden_reason" class="form-control form-control-sm mb-1" placeholder="Alasan disembunyikan">
                                <button class="btn btn-secondary btn-sm btn-block">Sembunyikan</button>
                                @else
                                <button class="btn btn-success btn-sm btn-block" {{ $contract->employee_nik ? 'disabled' : '' }}>Tampilkan</button>
                                @endif
                            </form>

                            @if($contract->last_hris_sync_error)
                            <form method="POST" action="{{ route('pkwt-contracts.retry-signature-sync', $contract->id) }}" class="mb-2">
                                @csrf
                                <button class="btn btn-warning btn-sm btn-block">Retry Sync Signature</button>
                                <div class="small text-danger mt-1">{{ $contract->last_hris_sync_error }}</div>
                            </form>
                            @endif

                            @if($contract->contract_file_path)
                            <a class="btn btn-outline-primary btn-sm btn-block" href="{{ route('pkwt-contracts.download', $contract->id) }}" target="_blank">Lihat File</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada kontrak PKWT 1.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $contracts->links() }}
    </div>
</div>
@endsection
