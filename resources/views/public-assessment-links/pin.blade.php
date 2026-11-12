@php
$labels = $link->isFieldTest() ? trans('field_test', [], 'id') : [];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $labels['Verifikasi PIN Penilaian'] ?? 'Verifikasi PIN Penilaian' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">{{ $labels['Verifikasi PIN Penilaian'] ?? 'Verifikasi PIN Penilaian' }}</h1>
                        <p class="text-muted">{{ $labels['Masukkan PIN yang diberikan untuk membuka formulir penilaian.'] ?? 'Masukkan PIN yang diberikan untuk membuka formulir penilaian.' }}</p>
                        <form method="POST" action="{{ route('assessment-links.public.unlock', $link->public_token) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="pin" class="form-label">{{ $labels['PIN'] ?? 'PIN' }}</label>
                                <div class="input-group">
                                    <input id="pin" name="pin" type="password" class="form-control @error('pin') is-invalid @enderror" required autocomplete="off" spellcheck="false" autocapitalize="none" @error('pin') aria-invalid="true" aria-describedby="pin-error" @enderror>
                                    <button type="button" class="btn btn-outline-secondary" data-toggle-pin data-show-label="{{ $labels['Tampilkan'] ?? 'Tampilkan' }}" data-hide-label="{{ $labels['Sembunyikan'] ?? 'Sembunyikan' }}" data-show-aria="{{ $labels['Tampilkan PIN'] ?? 'Tampilkan PIN' }}" data-hide-aria="{{ $labels['Sembunyikan PIN'] ?? 'Sembunyikan PIN' }}" aria-controls="pin" aria-pressed="false" aria-label="{{ $labels['Tampilkan PIN'] ?? 'Tampilkan PIN' }}">{{ $labels['Tampilkan'] ?? 'Tampilkan' }}</button>
                                </div>
                                @error('pin')<div id="pin-error" class="invalid-feedback d-block">{{ $labels[$message] ?? $message }}</div>@enderror
                            </div>
                            <button class="btn btn-primary w-100" type="submit">{{ $labels['Buka Formulir'] ?? 'Buka Formulir' }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @include('partials.assessment-pin-toggle')
</body>
</html>
