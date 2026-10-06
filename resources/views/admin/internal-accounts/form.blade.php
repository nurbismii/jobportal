@extends('layouts.app-pic')
@section('content-admin')
<h1 class="h3 text-gray-800 mb-3">{{ $account->exists ? 'Atur Akun Internal' : 'Tambah Akun Internal' }}</h1>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $account->exists ? route('internal-accounts.update', $account) : route('internal-accounts.store') }}" id="internalAccountForm">
    @csrf
    @if($account->exists)@method('PUT')@endif
    <div class="card shadow mb-4"><div class="card-body">
        <div class="row">
            <div class="form-group col-md-6"><label for="no_ktp">NIK / No. KTP</label><input id="no_ktp" name="no_ktp" class="form-control" value="{{ old('no_ktp', $account->no_ktp) }}" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16"><small class="form-text text-muted">16 digit, unik sesuai struktur akun yang sudah ada.</small></div>
            <div class="form-group col-md-6"><label for="name">Nama</label><input id="name" name="name" class="form-control" value="{{ old('name', $account->name) }}" required maxlength="255"></div>
            <div class="form-group col-md-6"><label for="email">Email</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $account->email) }}" required maxlength="255" autocomplete="off"></div>
            <div class="form-group col-md-6"><label for="role">Role</label><select id="role" name="role" class="form-control">@foreach(['manager' => 'Manager', 'supervisor' => 'Supervisor'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $account->role) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group col-md-6"><label for="status_akun">Status akun</label><select id="status_akun" name="status_akun" class="form-control"><option value="1" @selected((int) old('status_akun', $account->status_akun) === 1)>Aktif</option><option value="0" @selected((int) old('status_akun', $account->status_akun) === 0)>Nonaktif</option></select></div>
            <div class="form-group col-md-6"><label for="password">{{ $account->exists ? 'Password baru (opsional)' : 'Password' }}</label><input id="password" name="password" type="password" class="form-control" minlength="8" maxlength="255" autocomplete="new-password" @required(! $account->exists)><small class="form-text text-muted">Minimal 8 karakter. {{ $account->exists ? 'Kosongkan jika tidak diganti.' : '' }}</small></div>
            <div class="form-group col-md-6"><label for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" @required(! $account->exists)></div>
        </div>
    </div></div>
    <div class="card shadow mb-4"><div class="card-header"><h2 class="h5 mb-0">Izin per modul</h2></div><div class="card-body">
        <p class="text-muted">Centang hanya <strong>Lihat</strong> untuk read-only. Kosongkan seluruh izin untuk menutup akses modul. Dashboard menampilkan ringkasan lintas modul; berikan hanya jika diperlukan.</p>
        <div class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>Modul</th>@foreach(config('admin_access.actions') as $label)<th class="text-center">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>@foreach(config('admin_access.modules') as $module => $definition)
                <tr data-module="{{ $module }}"><th scope="row">{{ $definition['label'] }}</th>
                @foreach(config('admin_access.actions') as $action => $label)<td class="text-center">
                    @if(in_array($action, $definition['actions'], true))
                    <input type="checkbox" name="permissions[{{ $module }}][]" value="{{ $action }}" aria-label="{{ $label }} {{ $definition['label'] }}" @checked(in_array($action, old('permissions.' . $module, session()->hasOldInput() ? [] : ($account->module_permissions[$module] ?? [])), true))>
                    @else<span class="text-muted">—</span>@endif
                </td>@endforeach</tr>
            @endforeach</tbody>
        </table></div>
    </div></div>
    <div class="mb-4"><button class="btn btn-primary" type="submit" id="saveAccount">Simpan akun dan izin</button> <a href="{{ route('internal-accounts.index') }}" class="btn btn-outline-secondary">Kembali</a></div>
</form>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-module]').forEach(row => {
    row.addEventListener('change', event => {
        const view = row.querySelector('input[value="view"]');
        if (event.target.value === 'view' && !view.checked) {
            row.querySelectorAll('input').forEach(input => input.checked = false);
        } else if (event.target.checked) {
            view.checked = true;
        }
    });
});
document.getElementById('internalAccountForm').addEventListener('submit', () => {
    const button = document.getElementById('saveAccount');
    button.disabled = true;
    button.textContent = 'Menyimpan...';
});
</script>
@endpush
