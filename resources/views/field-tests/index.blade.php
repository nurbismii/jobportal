@php
$labels = trans('field_test', [], 'id');
@endphp
@extends('field-tests.layout')
@section('title', $labels['Daftar kandidat tes lapangan'])
@section('content')
<div class="ft-hero">
    <div>
        <p class="ft-eyebrow">{{ $labels['Siap untuk tes hari ini'] }}</p>
        <h1>{{ $labels['Nilai keterampilan. Catat dengan mudah.'] }}</h1>
        <p>{{ $labels['Pilih kandidat, isi penilaian, lalu tanda tangani. Semua hasil tersimpan pada kandidat yang sesuai.'] }}</p>
    </div><span class="ft-badge">{{ $labels[$link->field_test_config['name']] ?? $link->field_test_config['name'] }}</span>
</div>
<div class="ft-meta"><span>{{ \Carbon\Carbon::parse($link->field_test_config['test_date'])->format('d M Y') }}</span><span>{{ $link->field_test_config['location'] }}</span><span>{{ $candidates->total() }} {{ $labels['kandidat'] }}</span></div>
<form class="ft-search" method="GET"><label for="search" class="ft-label" hidden>{{ $labels['Cari nama kandidat'] }}</label><input id="search" class="ft-input" name="q" type="search" value="{{ request('q') }}" placeholder="{{ $labels['Cari nama kandidat…'] }}" aria-label="{{ $labels['Cari nama kandidat'] }}"><button class="ft-btn primary" type="submit">{{ $labels['Cari'] }}</button></form>
<div class="ft-grid">
    @forelse($candidates as $candidate)
    @php($signed = count($candidate->field_test_signatures ?? []))
    <article class="ft-card ft-candidate">
        <div class="ft-card-head"><span class="ft-eyebrow">{{ $labels['Kandidat'] }} #{{ $candidate->lamaran_id }}</span><span class="ft-badge {{ $signed === 3 ? 'green' : ($signed ? 'amber' : '') }}">{{ $signed === 3 ? $labels['Sudah disahkan'] : ($signed ? __('field_test.:count/3 tanda tangan', ['count' => $signed], 'id') : $labels[$candidate->result_values ? 'Draf tersimpan' : 'Belum dinilai']) }}</span></div>
        <div>
            <h2>{{ $candidate->lamaran?->biodata?->user?->name ?: 'Kandidat #'.$candidate->lamaran_id }}</h2><small>{{ $labels['No. KTP'] }}: {{ $candidate->lamaran?->biodata?->no_ktp ?: $labels['Belum tersedia'] }}</small>
        </div>
        <a class="ft-btn {{ $signed === 3 ? '' : 'primary' }} ft-full" href="{{ route('field-tests.show', [$link->public_token, $candidate->id]) }}">{{ $labels[$signed === 3 ? 'Lihat hasil penilaian' : ($candidate->result_values ? 'Lanjutkan penilaian' : 'Mulai penilaian')] }} <span aria-hidden="true">→</span></a>
    </article>
    @empty
    <div class="ft-card ft-empty">
        <h2>{{ $labels[request('q') ? 'Nama belum ditemukan' : 'Belum ada kandidat'] }}</h2>
        <p class="ft-muted">{{ $labels[request('q') ? 'Coba sebagian nama atau hapus pencarian.' : 'Hubungi HR untuk menambahkan kandidat pada tes ini.'] }}</p>@if(request('q'))<a class="ft-btn" href="{{ route('assessment-links.public.show', $link->public_token) }}">{{ $labels['Tampilkan semua kandidat'] }}</a>@endif
    </div>
    @endforelse
</div>
<nav class="ft-actions" aria-label="{{ $labels['Halaman kandidat'] }}">@if($candidates->previousPageUrl())<a class="ft-btn" href="{{ $candidates->previousPageUrl() }}">{{ $labels['Sebelumnya'] }}</a>@endif @if($candidates->hasMorePages())<a class="ft-btn" href="{{ $candidates->nextPageUrl() }}">{{ $labels['Berikutnya'] }}</a>@endif</nav>
@endsection
