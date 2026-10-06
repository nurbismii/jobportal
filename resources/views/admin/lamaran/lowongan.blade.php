@extends('layouts.app-pic')
@section('content-admin')
<h1 class="h3 text-gray-800 mb-3">Lamaran</h1>
<p class="text-muted">Pilih lowongan untuk melihat dan memproses lamaran sesuai izin akun.</p>
<form method="GET" class="form-inline mb-3"><label for="search" class="sr-only">Cari lowongan</label><input id="search" name="search" class="form-control mr-2" placeholder="Cari lowongan" value="{{ request('search') }}"><button class="btn btn-primary">Cari</button></form>
<div class="card shadow mb-4"><div class="card-body"><div class="table-responsive"><table class="table table-bordered">
<thead><tr><th>Lowongan</th><th>Aksi</th></tr></thead><tbody>
@forelse($lowongans as $lowongan)<tr><td>{{ $lowongan->nama_lowongan }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('directToLamaran', $lowongan->id) }}">Lihat lamaran</a></td></tr>
@empty<tr><td colspan="2" class="text-center text-muted">Tidak ada lowongan yang sesuai.</td></tr>@endforelse
</tbody></table></div></div></div>
{{ $lowongans->links() }}
@endsection
