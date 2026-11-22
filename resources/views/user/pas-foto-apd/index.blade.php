@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('profil.index') }}" class="btn btn-light mb-3">
                <i class="fa fa-arrow-left me-2" aria-hidden="true"></i>Kembali ke Profil
            </a>
            <section class="card shadow-sm" aria-labelledby="apd-photo-heading">
                <div class="card-body p-3 p-md-4">
                    <h1 id="apd-photo-heading" class="h4 mb-3">Pas Foto APD</h1>
                    @if(session('apd_photo_success'))
                        <div class="alert alert-success" role="status">{{ session('apd_photo_success') }}</div>
                    @endif

                    @if($biodata->pas_foto_apd)
                        <figure class="mb-4">
                            <img src="{{ candidate_document_url($biodata->no_ktp, $biodata->pas_foto_apd) }}"
                                alt="Pas foto APD saat ini" width="200" class="img-fluid rounded border">
                            <figcaption class="small text-muted mt-2">Pas foto APD saat ini</figcaption>
                        </figure>
                    @endif

                    <p id="apd-photo-guide">Gunakan latar polos dan hadap lurus ke kamera seperti pas foto KTP. Ambil foto dari kepala hingga pinggang. Pastikan wajah, APD, dan helm keselamatan terlihat jelas.</p>

                    <form id="apd-photo-form" method="POST" action="{{ route('pas-foto-apd.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="pas-foto-apd" class="form-label fw-semibold">Pilih file pas foto</label>
                            <input type="file" id="pas-foto-apd" name="pas_foto_apd"
                                class="form-control @error('pas_foto_apd') is-invalid @enderror"
                                accept="image/jpeg,image/png" required
                                aria-describedby="apd-photo-guide apd-photo-hint @error('pas_foto_apd') apd-photo-error @enderror"
                                @error('pas_foto_apd') aria-invalid="true" @enderror>
                            <div class="form-text" id="apd-photo-hint">JPG atau PNG, maksimal 2 MB.</div>
                            @error('pas_foto_apd')
                                <div id="apd-photo-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" id="konfirmasi-apd" name="konfirmasi_apd" value="1"
                                class="form-check-input @error('konfirmasi_apd') is-invalid @enderror" required
                                @checked(old('konfirmasi_apd'))
                                @error('konfirmasi_apd') aria-invalid="true" aria-describedby="konfirmasi-apd-error" @enderror>
                            <label for="konfirmasi-apd" class="form-check-label text-wrap">
                                Foto saya menampilkan wajah, APD, dan helm keselamatan dengan jelas.
                            </label>
                            @error('konfirmasi_apd')
                                <div id="konfirmasi-apd-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Pas Foto</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const apdForm = document.getElementById('apd-photo-form');
    const apdSubmit = apdForm.querySelector('button[type="submit"]');
    apdForm.addEventListener('submit', () => {
        apdSubmit.disabled = true;
        apdSubmit.textContent = 'Menyimpan…';
    });
    window.addEventListener('pageshow', () => {
        apdSubmit.disabled = false;
        apdSubmit.textContent = 'Simpan Pas Foto';
    });
</script>
@endpush
