@extends('layouts.app-pic')

@section('content-admin')
<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between mb-3">
    <h2 class="m-0 font-weight-bold text-primary">Pengaturan PKWT 1</h2>
    <a href="{{ route('pkwt-contracts.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow">
    <div class="card-body">
        <form method="POST" action="{{ route('pkwt-contract-settings.update') }}">
            @csrf
            @method('PATCH')

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Durasi Kontrak</label>
                        <input type="number" min="1" max="120" name="duration_value" class="form-control @error('duration_value') is-invalid @enderror" value="{{ old('duration_value', $setting->duration_value) }}" required>
                        @error('duration_value')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Satuan Durasi</label>
                        <select name="duration_unit" class="form-control @error('duration_unit') is-invalid @enderror" required>
                            <option value="day" {{ old('duration_unit', $setting->duration_unit) === 'day' ? 'selected' : '' }}>Hari</option>
                            <option value="week" {{ old('duration_unit', $setting->duration_unit) === 'week' ? 'selected' : '' }}>Minggu</option>
                            <option value="month" {{ old('duration_unit', $setting->duration_unit) === 'month' ? 'selected' : '' }}>Bulan</option>
                            <option value="year" {{ old('duration_unit', $setting->duration_unit) === 'year' ? 'selected' : '' }}>Tahun</option>
                        </select>
                        @error('duration_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Default Metode Tanda Tangan</label>
                        <select name="default_signing_method" class="form-control @error('default_signing_method') is-invalid @enderror" required>
                            <option value="electronic" {{ old('default_signing_method', $setting->default_signing_method) === 'electronic' ? 'selected' : '' }}>Electronic</option>
                            <option value="manual" {{ old('default_signing_method', $setting->default_signing_method) === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                        @error('default_signing_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </form>
    </div>
</div>
@endsection
