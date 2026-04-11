@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<style>
    .stats-card {
        border-left: 4px solid #4e73df;
    }

    .request-meta {
        font-size: 12px;
        color: #6c757d;
    }
</style>
@endpush

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Request Lupa Akun</h1>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card shadow stats-card h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pending</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow stats-card h-100" style="border-left-color:#1cc88a;">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $stats['approved'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow stats-card h-100" style="border-left-color:#e74a3b;">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $stats['rejected'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Request Pemulihan Akun</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Data Request</th>
                            <th>Data Akun Saat Ini</th>
                            <th width="12%">Status</th>
                            <th width="18%">Diproses</th>
                            <th width="18%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                        <tr>
                            <td>{{ $requests->firstItem() + $loop->index }}</td>
                            <td>
                                <div><strong>{{ $requestItem->requested_name }}</strong></div>
                                <div>{{ $requestItem->no_ktp }}</div>
                                <div>Email baru: {{ $requestItem->requested_email }}</div>
                                <div>No HP: {{ $requestItem->requested_phone ?: '-' }}</div>
                                <div class="request-meta mt-2">Diajukan {{ optional($requestItem->created_at)->format('d M Y H:i') }}</div>
                                @if($requestItem->requested_notes)
                                <div class="small text-muted mt-2">{{ $requestItem->requested_notes }}</div>
                                @endif
                            </td>
                            <td>
                                <div><strong>{{ $requestItem->registered_name ?: ($requestItem->user->name ?? '-') }}</strong></div>
                                <div>Email lama: {{ $requestItem->registered_email ?: ($requestItem->user->email ?? '-') }}</div>
                                <div>Tgl lahir: {{ optional($requestItem->registered_birth_date)->format('d-m-Y') ?: '-' }}</div>
                                <div>No HP: {{ $requestItem->registered_phone ?: '-' }}</div>
                            </td>
                            <td>
                                @if($requestItem->status === 'pending')
                                <span class="badge badge-warning text-uppercase">Pending</span>
                                @elseif($requestItem->status === 'approved')
                                <span class="badge badge-success text-uppercase">Approved</span>
                                @else
                                <span class="badge badge-danger text-uppercase">Rejected</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $requestItem->processor->name ?? '-' }}</div>
                                <div class="request-meta">{{ optional($requestItem->processed_at)->format('d M Y H:i') ?: '-' }}</div>
                                @if($requestItem->approved_email)
                                <div class="small text-success mt-2">Email aktif: {{ $requestItem->approved_email }}</div>
                                @endif
                                @if($requestItem->admin_notes)
                                <div class="small text-muted mt-2">{{ $requestItem->admin_notes }}</div>
                                @endif
                            </td>
                            <td>
                                @if($requestItem->status === 'pending')
                                <form action="{{ route('account-recovery-requests.approve', $requestItem->id) }}" method="POST" class="mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm btn-block" onclick="return confirm('Approve request ini? Email akun akan diganti dan password random baru akan dikirim ke email terbaru.')">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('account-recovery-requests.reject', $requestItem->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm btn-block" onclick="return confirm('Tolak request lupa akun ini?')">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                </form>
                                @else
                                <span class="text-muted small">Sudah diproses</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada request lupa akun.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
