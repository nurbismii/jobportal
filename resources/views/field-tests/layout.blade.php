@php
$labels = $labels ?? trans('field_test', [], 'id');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="same-origin">
    <title>@yield('title', 'Tes lapangan') · V-Hire</title>
    <link rel="stylesheet" href="{{ asset('admin/css/field-test.css') }}">
</head>
<body>
    <header class="ft-header"><div class="ft-header-inner"><a class="ft-brand" href="{{ route('assessment-links.public.show', $link->public_token) }}"><span class="ft-brand-mark" aria-hidden="true">V</span> V-Hire <span class="ft-brand-divider">{{ $labels['Tes lapangan'] }}</span></a><span class="ft-header-label">{{ $labels['Penilaian alat berat'] }}</span></div></header>
    <main class="ft-main">@yield('content')</main>
    <footer class="ft-page-footer">V-Hire · {{ $labels['Formulir penilaian lapangan'] }}</footer>
    @stack('scripts')
</body>
</html>
