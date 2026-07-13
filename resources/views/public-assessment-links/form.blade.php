<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Penilaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-md-5">
        <h1 class="h3 mb-4">Form Penilaian</h1>
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($link->candidates->isEmpty())
            <div class="alert alert-secondary">Belum ada kandidat pada link penilaian ini.</div>
        @else
            @foreach ($link->candidates as $candidate)
                <section class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Kandidat #{{ $candidate->id }}</h2>
                        <form method="POST" action="{{ route('assessment-links.public.results.store', [$link->public_token, $candidate]) }}">
                            @csrf
                            @foreach ($link->form_schema as $field)
                                @php($fieldId = $field['id'])
                                @php($value = old('values.'.$fieldId, data_get($candidate->result_values, $fieldId)))
                                <div class="mb-3">
                                    <label for="candidate-{{ $candidate->id }}-{{ $fieldId }}" class="form-label">{{ $field['label'] }}@if ($field['required'] ?? false) <span class="text-danger">*</span>@endif</label>
                                    @if ($field['type'] === 'select')
                                        <select id="candidate-{{ $candidate->id }}-{{ $fieldId }}" name="values[{{ $fieldId }}]" class="form-select @error('values.'.$fieldId) is-invalid @enderror">
                                            <option value="">Pilih hasil</option>
                                            @foreach ($field['options'] as $option)<option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>@endforeach
                                        </select>
                                    @else
                                        <input id="candidate-{{ $candidate->id }}-{{ $fieldId }}" name="values[{{ $fieldId }}]" type="{{ $field['type'] }}" value="{{ $value }}" @if ($field['type'] === 'number') step="any" @endif class="form-control @error('values.'.$fieldId) is-invalid @enderror">
                                    @endif
                                    @error('values.'.$fieldId)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                            <div class="mb-3">
                                <label for="candidate-{{ $candidate->id }}-note" class="form-label">Catatan petugas</label>
                                <textarea id="candidate-{{ $candidate->id }}-note" name="petugas_note" rows="3" maxlength="2000" class="form-control @error('petugas_note') is-invalid @enderror">{{ old('petugas_note', $candidate->petugas_note) }}</textarea>
                                @error('petugas_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-primary" type="submit">Simpan Hasil</button>
                        </form>
                        @if ($candidate->last_submitted_at)
                            <p class="small text-muted mt-3 mb-0">Hasil terakhir disimpan {{ $candidate->last_submitted_at->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA.</p>
                        @endif
                    </div>
                </section>
            @endforeach
        @endif
    </main>
</body>
</html>
