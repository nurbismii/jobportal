<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi PIN Penilaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">Verifikasi PIN Penilaian</h1>
                        <p class="text-muted">Masukkan PIN yang diberikan untuk membuka formulir penilaian.</p>
                        <form method="POST" action="{{ route('assessment-links.public.unlock', $link->public_token) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="pin" class="form-label">PIN</label>
                                <input id="pin" name="pin" type="password" class="form-control @error('pin') is-invalid @enderror" required autocomplete="off">
                                @error('pin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Buka Formulir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
