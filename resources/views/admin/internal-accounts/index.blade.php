@extends('layouts.app-pic')
@section('content-admin')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><h1 class="h3 text-gray-800">Akun Internal</h1><p class="text-muted mb-2">Kelola Manager, Supervisor, dan izin setiap modul.</p></div>
    <a href="{{ route('internal-accounts.create') }}" class="btn btn-primary">Tambah akun internal</a>
</div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
<div class="card shadow mb-4"><div class="card-body">
    <div class="table-responsive"><table class="table table-bordered mb-0">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>@forelse($accounts as $account)
            <tr><td>{{ $account->name }}</td><td>{{ $account->email }}</td><td>{{ ucfirst($account->role) }}</td>
                <td><span class="badge badge-{{ $account->status_akun ? 'success' : 'secondary' }}">{{ $account->status_akun ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td><a class="btn btn-outline-primary btn-sm" href="{{ route('internal-accounts.edit', $account) }}">Atur akun dan izin</a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada akun Manager atau Supervisor.</td></tr>@endforelse</tbody>
    </table></div>
</div></div>
{{ $accounts->links() }}
@endsection
