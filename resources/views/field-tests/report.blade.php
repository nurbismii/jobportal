<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="same-origin">
    <title>{{ __('field_test.Hasil tes lapangan', [], 'id') }} — {{ $candidate->lamaran?->biodata?->user?->name }}</title>
    <link rel="stylesheet" href="{{ asset('admin/css/field-test.css') }}">
</head>

<body class="ft-report">
    @php
    $labels = trans('field_test', [], 'id');
    $config = $link->field_test_config;
    $values = $candidate->result_values ?? [];
    $signatures = $candidate->field_test_signatures ?? [];
    $final = count($signatures) === 3;
    $sections = $values['component_sections'] ?? $config['sections'];
    $maximum = array_sum(array_column($config['sections'], 'max'));
    $total = collect($config['sections'])->keys()->sum(fn ($id) => is_numeric($values[$id] ?? null) ? $values[$id] : 0);
    @endphp
    <main class="ft-main">
        <div class="ft-actions ft-no-print" style="margin-bottom:24px"><button class="ft-btn primary" type="button" onclick="window.print()">{{ $labels['Cetak / Simpan PDF'] }}</button>
            <p class="ft-caption" style="margin:0">{{ $labels['Pilih “Simpan sebagai PDF” pada dialog cetak browser.'] }}</p>
        </div>
        <div class="ft-card-head ft-report-heading">
            <p class="ft-eyebrow">{{ $labels['V-Hire · Tes lapangan alat berat'] }}</p>
            <span class="ft-badge {{ $final ? 'green' : 'amber' }}">{{ $labels[$final ? 'FINAL · Disahkan' : 'DRAF · Belum disahkan'] }}</span>
        </div>
        <div class="ft-hero">
            <div>
                <h1>{{ $labels['Form penilaian'] }} {{ $labels[$config['name']] ?? $config['name'] }}</h1>
                <p>{{ __('field_test.Kandidat #:id · Revisi :revision', ['id' => $candidate->lamaran_id, 'revision' => $candidate->field_test_revision], 'id') }}</p>
            </div>
        </div>
        @unless($final)<div class="ft-note warm">{{ $labels['Hasil sementara. Pengesahan belum lengkap dan belum menjadi hasil kelulusan final.'] }}</div>@endunless
        <div class="ft-card">
            <div class="ft-info-grid">@foreach(['Nama kandidat' => $candidate->lamaran?->biodata?->user?->name, 'No. KTP' => $candidate->lamaran?->biodata?->no_ktp, 'Tanggal tes' => \Carbon\Carbon::parse($config['test_date'])->format('d-m-Y'), 'Lokasi' => $config['location'], 'SIM B2 Umum' => $values['sim'] ?? null, 'KTP ditunjukkan' => $values['ktp'] ?? null] as $label => $value)<dl>
                    <dt>{{ $labels[$label] ?? $label }}</dt>
                    <dd>{{ in_array($label, ['SIM B2 Umum', 'KTP ditunjukkan'], true) ? ($labels[$value] ?? $value ?? '-') : ($value ?: '-') }}</dd>
                </dl>@endforeach</div>
        </div>
        <div class="ft-card ft-assessment-detail">
            <table class="ft-report-table">
                <thead>
                    <tr>
                        <th scope="col">{{ $labels['Aspek penilaian'] }}</th>
                        <th scope="col">{{ $labels[isset($values['components']) ? 'Komponen dan jawaban' : 'Komponen'] }}</th>
                        <th scope="col">{{ $labels['Nilai'] }}</th>
                    </tr>
                </thead>
                <tbody>@foreach($sections as $id => $section)<tr>
                        <th scope="row">{{ $labels[$section['label']] ?? $section['label'] }}</th>
                        <td>
                            @if(isset($values['components']))
                            <ul class="ft-report-components">@foreach($section['indicators'] as $index => $indicator)<li><span>{{ $labels[$indicator] ?? $indicator }}</span><strong>{{ $labels[$values['components'][$id][$index] ?? 'Belum dijawab'] ?? $values['components'][$id][$index] }}</strong></li>@endforeach</ul>
                            <small>{{ __('field_test.:yes Ya ÷ :count × :max poin', ['yes' => count(array_filter($values['components'][$id] ?? [], fn ($answer) => $answer === 'Ya')), 'count' => count($section['indicators']), 'max' => $section['max']], 'id') }}</small>
                            @else{{ implode('; ', array_map(fn ($indicator) => $labels[$indicator] ?? $indicator, $section['indicators'])) }}@endif
                        </td>
                        <td>{{ isset($values[$id]) ? number_format((float) $values[$id], 2, ',', '.') : '—' }} / {{ $section['max'] }}</td>
                    </tr>@endforeach<tr>
                        <th scope="row" colspan="2">{{ $labels['Total nilai'] }}</th>
                        <td><strong>{{ count(array_filter(array_keys($sections), fn ($id) => isset($values[$id]))) ? number_format($total, 2, ',', '.') : '—' }} / {{ $maximum }}</strong></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top:20px"><strong>{{ $labels[$final ? 'Hasil final' : 'Hasil sementara'] }}: {{ $labels[$values['decision'] ?? 'Belum ditentukan'] ?? $values['decision'] }}</strong></p>
            <h3>{{ $labels['Catatan penilai'] }}</h3>
            <p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $candidate->petugas_note ?: $labels['Tidak ada catatan.'] }}</p>
        </div>
        <div class="ft-signers report">@foreach(\App\Services\FieldTestService::ROLES as $role => $label)<div class="ft-signer"><small>{{ $labels[$label] ?? $label }}</small><strong>{{ $signatures[$role]['name'] ?? $config['signers'][$role]['name'] }}</strong>
                @if(isset($signatures[$role]) && \Illuminate\Support\Facades\Storage::disk('assessment_private')->exists($signatures[$role]['path']))<img class="ft-report-signature" alt="{{ $labels['Tanda tangan'] }} {{ $labels[$label] ?? $label }}" src="data:image/png;base64,{{ base64_encode(\Illuminate\Support\Facades\Storage::disk('assessment_private')->get($signatures[$role]['path'])) }}"><small>{{ \Carbon\Carbon::parse($signatures[$role]['signed_at'])->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA</small>@else<p class="ft-caption">{{ $labels[isset($signatures[$role]) ? 'Berkas tanda tangan tidak tersedia. Hubungi admin.' : 'Belum ditandatangani'] }}</p>@endif</div>@endforeach</div>
    </main>
</body>

</html>
