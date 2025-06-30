@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
@endpush
<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-primary"></h6>
    <a href="{{ route('pengumumans.index') }}" class="btn btn-sm btn-danger">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Form Pengumuman</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('pengumumans.update', $pengumuman->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{ method_field('patch') }}
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label for="pengumuman">Pengumuman
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="pengumuman" id="pengumuman" class="form-control" value="{{ $pengumuman->pengumuman }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="thumbnail">Thumbnail</label>
                    <input type="file" name="thumbnail" class="form-control-file" accept=".png, .jpeg, .jpg" value="{{ $pengumuman->thumbnail }}">
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-12 mb-3">
                    <label class="form-label" for="inputEmail">Keterangan
                        <span class="text-danger">*</span>
                    </label>
                    <div id="quill-editor" class="mb-3" style="height: 300px;"></div>
                    <textarea rows="3" class="mb-3 d-none" name="keterangan" id="quill-editor-area" required>{{ $pengumuman->keterangan }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary float-right">Simpan</button>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<!-- Initialize Quill editor -->
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('quill-editor-area')) {
            var editor = new Quill('#quill-editor', {
                theme: 'snow'
            });

            var quillEditor = document.getElementById('quill-editor-area');

            // Masukkan konten awal ke editor Quill
            editor.root.innerHTML = quillEditor.value;

            // Sync Quill ke textarea saat teks berubah
            editor.on('text-change', function() {
                quillEditor.value = editor.root.innerHTML;
            });
        }
    });
</script>
@endpush

@endsection