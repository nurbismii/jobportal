@php
$labels = trans('field_test', [], 'id');
@endphp
@extends('field-tests.layout')
@section('title', $labels['Penilaian'].' '.($labels[$link->field_test_config['name']] ?? $link->field_test_config['name']))
@section('content')
@php
$config = $link->field_test_config;
$values = $candidate->result_values ?? [];
$signatures = $candidate->field_test_signatures ?? [];
$signed = count($signatures);
$locked = $signed > 0;
$legacy = $locked && !array_key_exists('components', $values);
$sections = $legacy ? $config['sections'] : app(\App\Services\FieldTestService::class)->sectionsFor($link, $values);
$roles = \App\Services\FieldTestService::ROLES;
$nextRole = array_keys($roles)[$signed] ?? null;
$biodata = $candidate->lamaran?->biodata;
$maximum = array_sum(array_column($config['sections'], 'max'));
@endphp
<a class="ft-back" href="{{ route('assessment-links.public.show', $link->public_token) }}">{{ $labels['← Daftar kandidat'] }}</a>
<div class="ft-hero">
    <div>
        <p class="ft-eyebrow">{{ $labels[$config['name']] ?? $config['name'] }} · {{ $labels['Revisi'] }} {{ $candidate->field_test_revision }}</p>
        <h1>{{ $biodata?->user?->name ?: 'Kandidat #'.$candidate->lamaran_id }}</h1>
        <p>{{ $labels['Satu formulir bersama. Periksa nama, lalu tanda tangani langsung di layar.'] }}</p>
    </div><span class="ft-badge {{ $signed === 3 ? 'green' : 'amber' }}">{{ $signed === 3 ? $labels['Hasil sudah disahkan'] : ($signed ? __('field_test.:count/3 tanda tangan', ['count' => $signed], 'id') : $labels['Draf penilaian']) }}</span>
</div>
<div class="ft-meta"><span>{{ \Carbon\Carbon::parse($config['test_date'])->format('d M Y') }}</span><span>{{ $config['location'] }}</span></div>
@if(!$locked && $revisionAudit)
<div class="ft-note warm"><strong>{{ __('field_test.Dikembalikan untuk perbaikan oleh :name', ['name' => data_get($revisionAudit->new_values, 'requested_by')], 'id') }}</strong>
    <p style="margin-bottom:0;white-space:pre-wrap">{{ data_get($revisionAudit->new_values, 'reason') }}</p>
</div>
@endif
<noscript>
    <p class="ft-message">{{ $labels['Aktifkan JavaScript pada browser untuk mengisi dan menandatangani formulir ini.'] }}</p>
</noscript>
<div id="form-message" class="ft-message" role="alert" tabindex="-1" hidden></div>
<ol class="ft-steps" aria-label="{{ $labels['Langkah penilaian'] }}">
    <li data-step-label="0" aria-current="step"><span class="ft-step-number">1</span> <span>{{ $labels['Periksa kandidat'] }}</span></li>
    <li data-step-label="1"><span class="ft-step-number">2</span> <span>{{ $labels['Cek komponen'] }}</span></li>
    <li data-step-label="2"><span class="ft-step-number">3</span> <span>{{ $labels['Tanda tangan'] }}</span></li>
</ol>
<form id="field-test-form" novalidate data-locked="{{ $locked ? '1' : '0' }}">
    <section data-step="0" aria-labelledby="identity-title">
        <div class="ft-card">
            <div class="ft-card-head">
                <div>
                    <h2 id="identity-title" tabindex="-1">{{ $labels['Pastikan kandidat sudah sesuai'] }}</h2>
                    <p class="ft-muted">{{ $labels['Cocokkan identitas sebelum memulai tes.'] }}</p>
                </div><span class="ft-section-index" aria-hidden="true">01</span>
            </div>
            <div class="ft-info-grid">
                <dl>
                    <dt>{{ $labels['Nama kandidat'] }}</dt>
                    <dd>{{ $biodata?->user?->name ?: '-' }}</dd>
                </dl>
                <dl>
                    <dt>{{ $labels['Nomor KTP'] }}</dt>
                    <dd>{{ $biodata?->no_ktp ?: '-' }}</dd>
                </dl>
                <dl>
                    <dt>{{ $labels['Nomor telepon'] }}</dt>
                    <dd>{{ $biodata?->no_telp ?: '-' }}</dd>
                </dl>
                <dl>
                    <dt>{{ $labels['Umur saat tes'] }}</dt>
                    <dd>{{ $biodata?->tanggal_lahir ? (int) \Carbon\Carbon::parse($biodata->tanggal_lahir)->diffInYears(\Carbon\Carbon::parse($config['test_date'])) . ' '.$labels['tahun'] : '-' }}</dd>
                </dl>
            </div>
        </div>
        <div class="ft-card">
            <h2>{{ $labels['Pemeriksaan dokumen'] }}</h2>
            <p class="ft-muted">{{ $labels['Pilih sesuai dokumen yang ditunjukkan kandidat.'] }}</p>
            @foreach(['sim' => 'SIM B2 Umum', 'ktp' => 'KTP'] as $field => $label)
            <fieldset class="ft-field" style="border:0;padding:0">
                <legend class="ft-label">{{ $labels[$label] ?? $label }}</legend>
                <div class="ft-choices">@foreach(['Ada', 'Tidak Ada'] as $option)<label class="ft-choice"><input type="radio" name="{{ $field }}" data-value="{{ $field }}" value="{{ $option }}" @checked(($values[$field] ?? '' )===$option) @disabled($locked) required><span>{{ $labels[$option] ?? $option }}</span></label>@endforeach</div>
            </fieldset>
            @endforeach
        </div>
    </section>
    <section data-step="1" aria-labelledby="scores-title">
        <h2 id="scores-title" tabindex="-1">{{ $labels['Amati praktik, lalu pilih Ya atau Tidak'] }}</h2>
        <p class="ft-muted">{{ $labels['Ya berarti pemeriksaan atau tindakan dilakukan dengan benar. Tidak berarti belum sesuai. Poin dihitung otomatis; setiap komponen dalam satu aspek memiliki bobot yang sama.'] }}</p>
        @if($legacy)<p class="ft-note">{{ $labels['Hasil ini sudah ditandatangani dengan format nilai manual sebelumnya. Nilai tersimpan tetap dipertahankan. Gunakan revisi jika perlu menilai ulang dengan pilihan Ya/Tidak.'] }}</p>@elseif($values && !array_key_exists('components', $values))<p class="ft-note warm">{{ $labels['Draf sebelumnya menggunakan nilai manual. Lengkapi pilihan Ya/Tidak untuk menghitung ulang nilai. Riwayat nilai sebelumnya tetap disimpan.'] }}</p>@endif
        @foreach($sections as $id => $section)
        <div class="ft-card" data-checklist-section="{{ $id }}" data-max="{{ $section['max'] }}" @if($legacy) data-legacy-score="{{ $values[$id] ?? '' }}" @endif>
            <div class="ft-card-head">
                <div class="ft-section-title"><span class="ft-section-index" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $labels[$section['label']] ?? $section['label'] }}</h3>
                </div><span class="ft-badge">{{ $labels['Maks.'] }} {{ $section['max'] }}</span>
            </div>
            @if($legacy)
            <ul class="ft-indicators">@foreach($section['indicators'] as $indicator)<li>{{ $labels[$indicator] ?? $indicator }}</li>@endforeach</ul>
            @else
            @foreach($section['indicators'] as $index => $indicator)
            <div class="ft-component-row" role="group" aria-labelledby="component-{{ $id }}-{{ $index }}">
                <span id="component-{{ $id }}-{{ $index }}" class="ft-component-label"><span class="ft-component-number">{{ $index + 1 }}.</span> {{ $labels[$indicator] ?? $indicator }}</span>
                <div class="ft-choices">@foreach(['Ya', 'Tidak'] as $answer)<label class="ft-choice"><input type="radio" name="component_{{ $id }}_{{ $index }}" data-component="{{ $id }}" data-index="{{ $index }}" value="{{ $answer }}" aria-label="{{ ($labels[$answer] ?? $answer).' — '.($labels[$indicator] ?? $indicator) }}" @checked(($values['components'][$id][$index] ?? null)===$answer) @disabled($locked) required><span>{{ $labels[$answer] ?? $answer }}</span></label>@endforeach</div>
            </div>
            @endforeach
            @endif
            <div class="ft-component-result" aria-live="polite">
                <div><strong>{{ $labels[$legacy ? 'Nilai tersimpan' : 'Nilai otomatis'] }}</strong><small data-component-progress>{{ $labels[$legacy ? 'Format nilai manual sebelumnya' : 'Belum ada komponen dijawab'] }}</small></div>
                <div><strong data-section-score>—</strong> / {{ $section['max'] }}</div>
            </div>
            @unless($legacy)<small class="ft-caption" style="margin-top:10px">{{ __('field_test.Rumus: jumlah Ya ÷ :count komponen × :max poin. Lengkapi semua pilihan sebelum tanda tangan.', ['count' => count($section['indicators']), 'max' => $section['max']], 'id') }}</small>@endunless
        </div>
        @endforeach
        <div class="ft-total">
            <div>
                <div class="ft-total-label">{{ $labels['Total nilai sementara'] }}</div><small id="score-progress" aria-live="polite"></small>
            </div>
            <div><strong data-total>—</strong> <span>/ {{ $maximum }}</span></div>
        </div>
        <div class="ft-card">
            <h2>{{ $labels['Hasil dan catatan penilai'] }}</h2>
            <p class="ft-muted">{{ $labels['Sepakati hasil bersama kedua penilai berdasarkan praktik kandidat.'] }}</p>
            <fieldset class="ft-field" style="border:0;padding:0">
                <legend class="ft-label">{{ $labels['Hasil tes lapangan'] }}</legend>
                <div class="ft-choices">@foreach(['Lulus', 'Tidak Lulus'] as $option)<label class="ft-choice"><input type="radio" name="decision" data-value="decision" value="{{ $option }}" @checked(($values['decision'] ?? '' )===$option) @disabled($locked) required><span>{{ $labels[$option] ?? $option }}</span></label>@endforeach</div>
            </fieldset><label class="ft-label" for="petugas-note">{{ $labels['Catatan penilai'] }} <span class="ft-muted">{{ $labels['(wajib jika tidak lulus)'] }}</span></label><textarea id="petugas-note" class="ft-input" maxlength="2000" @disabled($locked) placeholder="{{ $labels['Contoh: perlu latihan pengoperasian rem dan pemeriksaan harian.'] }}">{{ $candidate->petugas_note }}</textarea>
        </div>
    </section>
    <section data-step="2" aria-labelledby="signing-title">
        <h2 id="signing-title" tabindex="-1">{{ $labels['Periksa bersama, lalu tanda tangani'] }}</h2>
        <p class="ft-muted">{{ $labels['Perangkat yang sama dapat diserahkan bergantian kepada petugas berikutnya.'] }}</p>
        <div class="ft-card">
            <div class="ft-card-head">
                <h3>{{ $labels['Ringkasan penilaian'] }}</h3><span class="ft-badge" id="decision-summary">{{ $labels['Belum dipilih'] }}</span>
            </div>
            <table class="ft-report-table">
                <caption class="ft-caption">{{ $labels['Nilai yang disetujui oleh ketiga penanda tangan'] }}</caption>
                <thead>
                    <tr>
                        <th scope="col">{{ $labels['Aspek'] }}</th>
                        <th scope="col">{{ $labels['Nilai'] }}</th>
                    </tr>
                </thead>
                <tbody>@foreach($sections as $id => $section)<tr>
                        <td>{{ $labels[$section['label']] ?? $section['label'] }}<small data-summary-progress="{{ $id }}"></small></td>
                        <td><span data-summary="{{ $id }}">—</span> / {{ $section['max'] }}</td>
                    </tr>@endforeach<tr>
                        <th scope="row">{{ $labels['Total'] }}</th>
                        <td><strong data-total>—</strong> / {{ $maximum }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="ft-caption" style="margin-top:16px">{{ $labels['Catatan penilai'] }}</p>
            <p id="note-summary" style="white-space:pre-wrap;overflow-wrap:anywhere">—</p>
        </div>
        <div class="ft-signers">@foreach($roles as $role => $label)<div class="ft-signer {{ isset($signatures[$role]) ? 'done' : ($role === $nextRole ? 'active' : '') }}"><small>{{ $labels[$label] ?? $label }}</small><strong>{{ $config['signers'][$role]['name'] }}</strong><small>{{ isset($signatures[$role]) ? '✓ '.$labels['Ditandatangani'].' '.\Carbon\Carbon::parse($signatures[$role]['signed_at'])->timezone('Asia/Makassar')->format('d/m H:i') : $labels[$role === $nextRole ? 'Giliran tanda tangan' : 'Menunggu giliran'] }}</small></div>@endforeach</div>
        @if($nextRole)
        <div class="ft-card">
            <p class="ft-eyebrow">{{ $labels['Giliran'] }} {{ $labels[$roles[$nextRole]] }}</p>
            <h3>{{ __('field_test.:name, silakan tanda tangan', ['name' => $config['signers'][$nextRole]['name']], 'id') }}</h3>
            <p class="ft-note {{ $locked ? '' : 'warm' }}">{{ $labels[$locked ? 'Tanda tangan sebelumnya sudah tersimpan. Serahkan perangkat kepada petugas yang namanya tercantum di atas.' : 'Setelah tanda tangan pertama, nilai dikunci. Pastikan semua nilai dan catatan sudah disepakati.'] }}</p>
            <p class="ft-label" id="signature-label" style="margin-top:22px">{{ $labels['Tanda tangan Anda'] }}</p><canvas id="signature-pad" class="ft-signature" width="640" height="220" aria-labelledby="signature-label" aria-describedby="signature-help"></canvas><small id="signature-help">{{ $labels['Gambar dengan jari atau mouse. Anda juga dapat memakai tombol nama di bawah.'] }}</small>
            <div class="ft-actions" style="margin-top:12px"><button class="ft-btn subtle" id="clear-signature" type="button">{{ $labels['Hapus & ulangi'] }}</button><button class="ft-btn subtle" id="name-signature" type="button">{{ $labels['Gunakan nama sebagai tanda tangan'] }}</button></div>
            <label class="ft-consent"><input type="checkbox" id="signing-consent"><span>{{ __('field_test.Saya, :name, sudah memeriksa dan menyetujui nilai serta catatan pada formulir ini.', ['name' => $config['signers'][$nextRole]['name']], 'id') }}</span></label>
            <button class="ft-btn primary ft-full" id="sign-button" type="button">{{ $labels[$nextRole === 'division_head' ? 'Tanda tangani & sahkan hasil' : 'Simpan tanda tangan '.$roles[$nextRole]] }}</button>
        </div>
        @else
        <div class="ft-card">
            <p class="ft-eyebrow">{{ $labels['Penilaian selesai'] }}</p>
            <h2>{{ $labels['Hasil sudah disahkan oleh tiga petugas'] }}</h2>
            <p class="ft-muted">{{ $labels['Hasil final telah terhubung ke lamaran kandidat. Anda dapat mencetak laporan atau melanjutkan ke kandidat berikutnya.'] }}</p>
            <div class="ft-actions"><a class="ft-btn primary" href="{{ route('field-tests.report', [$link->public_token, $candidate->id]) }}">{{ $labels['Lihat laporan / Simpan PDF'] }}</a><a class="ft-btn" href="{{ route('assessment-links.public.show', $link->public_token) }}">{{ $labels['Kandidat berikutnya'] }}</a></div>
        </div>
        @endif
        @if($locked)
        <div class="ft-note"><strong>{{ $labels['Ada yang perlu dikoreksi?'] }}</strong>
            <p>{{ $labels['Penanda tangan yang ditugaskan dapat membuka revisi. Tanda tangan sebelumnya disimpan dalam riwayat dan ketiga petugas perlu menandatangani ulang.'] }}</p><button class="ft-btn" id="open-revision" type="button">{{ $labels['Kembalikan untuk perbaikan'] }}</button>
        </div>
        @endif
    </section>
    <div class="ft-navigation"><button class="ft-btn subtle" id="previous-step" type="button">{{ $labels['← Kembali'] }}</button><span id="save-state" class="ft-save-state" role="status">{{ $locked ? __('field_test.Nilai terkunci · :count/3 tanda tangan', ['count' => $signed], 'id') : $labels['Draf belum berubah'] }}</span>
        <div class="ft-actions">@unless($locked)<button class="ft-btn" id="save-draft" type="button">{{ $labels['Simpan draf'] }}</button>@endunless<button class="ft-btn primary" id="next-step" type="button">{{ $labels['Lanjut →'] }}</button></div>
    </div>
</form>
@if($locked)
<dialog id="revision-dialog" class="ft-dialog" aria-labelledby="revision-title">
    <h2 id="revision-title">{{ $labels['Kembalikan untuk perbaikan'] }}</h2>
    <p class="ft-muted">{{ $labels['Seluruh tanda tangan pada revisi ini akan diarsipkan. Nilai tetap tersedia untuk diperbaiki.'] }}</p>
    <form id="revision-form">
        <div class="ft-field"><label class="ft-label" for="revision-role">{{ $labels['Anda bertugas sebagai'] }}</label><select class="ft-input" id="revision-role">@foreach($roles as $role => $label)<option value="{{ $role }}">{{ $labels[$label] ?? $label }} — {{ $config['signers'][$role]['name'] }}</option>@endforeach</select></div>
        <div class="ft-field"><label class="ft-label" for="revision-reason">{{ $labels['Bagian yang perlu diperbaiki'] }}</label><textarea id="revision-reason" class="ft-input" minlength="5" maxlength="1000" required placeholder="{{ $labels['Jelaskan koreksi agar petugas mudah menindaklanjuti.'] }}"></textarea></div>
        <div id="revision-error" class="ft-message" role="alert" hidden></div>
        <div class="ft-actions"><button class="ft-btn danger" type="submit">{{ $labels['Buka revisi'] }}</button><button class="ft-btn" id="close-revision" type="button">{{ $labels['Batal'] }}</button></div>
    </form>
</dialog>
@endif
@endsection
@push('scripts')
<script id="field-test-data" type="application/json">{!! json_encode(['version' => $candidate->field_test_version, 'revision' => $candidate->field_test_revision, 'locked' => $locked, 'legacy' => $legacy, 'signedCount' => $signed, 'nextRole' => $nextRole, 'signerName' => $nextRole ? $config['signers'][$nextRole]['name'] : null, 'saveUrl' => route('field-tests.save', [$link->public_token, $candidate->id]), 'signUrl' => route('field-tests.sign', [$link->public_token, $candidate->id]), 'reviseUrl' => route('field-tests.revise', [$link->public_token, $candidate->id]), 'labels' => $labels], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('admin/js/field-test.js') }}" defer></script>
@endpush
