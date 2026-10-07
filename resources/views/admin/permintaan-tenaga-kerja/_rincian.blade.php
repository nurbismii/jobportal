@php
    $rincianRows = array_values(old('rincian', isset($permintaanTenagaKerja) ? $permintaanTenagaKerja->rincian_permintaan : [[]]));
@endphp
<div class="mb-4" id="rincian-ptk">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="font-weight-bold text-primary mb-0">Rincian Kebutuhan Tenaga Kerja</h6>
        <button type="button" class="btn btn-outline-primary btn-sm" id="tambah-rincian"><i class="fas fa-plus"></i> Tambah Rincian</button>
    </div>
    <div class="alert alert-danger d-none" id="divisi-error" role="alert">Divisi gagal dimuat. <button type="button" class="btn btn-link btn-sm" id="ulang-divisi">Coba lagi</button></div>
    <div id="rincian-rows">
        @foreach ($rincianRows ?: [[]] as $index => $rincian)
        <fieldset class="border rounded p-3 mb-3 rincian-row">
            <legend class="h6 w-auto px-2">Rincian <span class="nomor-rincian">{{ $index + 1 }}</span></legend>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="rincian-{{ $index }}-divisi">Divisi</label>
                    <select id="rincian-{{ $index }}-divisi" name="rincian[{{ $index }}][divisi]" class="form-control rincian-divisi" data-selected="{{ $rincian['divisi'] ?? '' }}">
                        <option value="">-- Pilih divisi --</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="rincian-{{ $index }}-posisi">Posisi <span class="text-danger">*</span></label>
                    <input id="rincian-{{ $index }}-posisi" name="rincian[{{ $index }}][posisi]" class="form-control" value="{{ $rincian['posisi'] ?? '' }}" maxlength="255" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rincian-{{ $index }}-jumlah_ptk">Jumlah Permintaan Tenaga Kerja <span class="text-danger">*</span></label>
                    <input type="number" id="rincian-{{ $index }}-jumlah_ptk" name="rincian[{{ $index }}][jumlah_ptk]" class="form-control rincian-jumlah" value="{{ $rincian['jumlah_ptk'] ?? '' }}" min="1" max="1000000" step="1" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rincian-{{ $index }}-jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select id="rincian-{{ $index }}-jenis_kelamin" name="rincian[{{ $index }}][jenis_kelamin]" class="form-control" required>
                        <option value="">-- Pilih jenis kelamin --</option>
                        @foreach (['Laki-laki', 'Perempuan', 'Laki-laki dan Perempuan'] as $jenisKelamin)
                        <option value="{{ $jenisKelamin }}" {{ ($rincian['jenis_kelamin'] ?? '') === $jenisKelamin ? 'selected' : '' }}>{{ $jenisKelamin }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rincian-{{ $index }}-rentang_usia">Usia <span class="text-danger">*</span></label>
                    <input id="rincian-{{ $index }}-rentang_usia" name="rincian[{{ $index }}][rentang_usia]" class="form-control" value="{{ $rincian['rentang_usia'] ?? '' }}" placeholder="Contoh: 18–35 tahun" maxlength="255" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="rincian-{{ $index }}-background_pendidikan">Background Pendidikan <span class="text-danger">*</span></label>
                    <select id="rincian-{{ $index }}-background_pendidikan" name="rincian[{{ $index }}][background_pendidikan]" class="form-control" required>
                        <option value="">-- Pilih pendidikan --</option>
                        @foreach (['SMA/SMK', 'D3', 'S1', 'S2', 'S3'] as $pendidikan)
                        <option value="{{ $pendidikan }}" {{ ($rincian['background_pendidikan'] ?? '') === $pendidikan ? 'selected' : '' }}>{{ $pendidikan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="rincian-{{ $index }}-kualifikasi_ptk" class="kualifikasi-label">Kualifikasi Permintaan Tenaga Kerja <span class="text-danger">*</span></label>
                <textarea id="rincian-{{ $index }}-kualifikasi_ptk" name="rincian[{{ $index }}][kualifikasi_ptk]" class="d-none rincian-kualifikasi">{{ \App\Mail\HrBlastEmail::sanitizeMessage($rincian['kualifikasi_ptk'] ?? '') }}</textarea>
                <div class="rincian-editor" style="min-height: 160px;"></div>
                <small class="text-danger d-none kualifikasi-error" role="alert">Kualifikasi rincian ini wajib diisi.</small>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm hapus-rincian">Hapus Rincian</button>
        </fieldset>
        @endforeach
    </div>
    <p class="font-weight-bold mb-0" aria-live="polite">Total Permintaan: <span id="total-rincian">0</span> orang</p>
    <small class="text-muted">Minimal satu rincian. Divisi yang sama dapat dipilih untuk posisi berbeda. Departemen, tanggal, dan status berlaku untuk seluruh rincian.</small>
</div>

@push('scripts')
<script>
$(function () {
    const container = document.getElementById('rincian-rows');
    const add = document.getElementById('tambah-rincian');
    const error = document.getElementById('divisi-error');
    const form = container.closest('form');
    if (form.dataset.permissionAllowed === '0') return;
    let loading = false;
    let failed = false;
    let xhr;
    const rows = () => Array.from(container.querySelectorAll('.rincian-row'));
    const template = rows()[0].cloneNode(true);
    const editors = new Map();
    function initEditor(row) {
        const input = row.querySelector('.rincian-kualifikasi');
        const editor = new Quill(row.querySelector('.rincian-editor'), {theme: 'snow'});
        editor.clipboard.dangerouslyPasteHTML(input.value);
        editor.root.setAttribute('role', 'textbox');
        editor.root.setAttribute('aria-required', 'true');
        editor.root.setAttribute('aria-labelledby', row.querySelector('.kualifikasi-label').id);
        editor.on('text-change', () => {
            input.value = editor.root.innerHTML;
            row.querySelector('.kualifikasi-error').classList.add('d-none');
            editor.root.removeAttribute('aria-invalid');
        });
        editors.set(row, editor);
    }
    function refresh() {
        rows().forEach((row, index) => {
            row.querySelector('.nomor-rincian').textContent = index + 1;
            row.querySelector('.hapus-rincian').disabled = rows().length === 1;
            row.querySelectorAll('[name]').forEach(input => {
                const field = input.name.match(/\[([^\]]+)\]$/)[1];
                const label = row.querySelector(`label[for="${input.id}"]`);
                input.name = `rincian[${index}][${field}]`;
                input.id = `rincian-${index}-${field}`;
                if (label) {
                    label.htmlFor = input.id;
                    label.id = input.id + '-label';
                }
            });
            if (editors.has(row)) editors.get(row).root.setAttribute('aria-labelledby', row.querySelector('.kualifikasi-label').id);
        });
        add.disabled = loading || failed || rows().length >= 100;
        form.querySelector('[type="submit"]').disabled = loading || failed;
        document.getElementById('total-rincian').textContent = rows().reduce((sum, row) => sum + (Number(row.querySelector('.rincian-jumlah').value) || 0), 0);
    }
    add.addEventListener('click', () => {
        if (loading || failed || rows().length >= 100) return;
        const row = template.cloneNode(true);
        row.querySelector('.rincian-divisi').replaceWith(rows()[0].querySelector('.rincian-divisi').cloneNode(true));
        row.querySelectorAll('input, select, textarea').forEach(input => {
            input.value = '';
            if (input.matches('.rincian-divisi')) input.dataset.selected = '';
        });
        container.appendChild(row);
        refresh();
        initEditor(row);
        row.querySelector('input').focus();
    });
    container.addEventListener('click', event => {
        if (event.target.closest('.hapus-rincian') && rows().length > 1) {
            const row = event.target.closest('.rincian-row');
            editors.delete(row);
            row.remove();
            refresh();
        }
    });
    container.addEventListener('input', refresh);
    container.addEventListener('change', event => {
        if (event.target.matches('.rincian-divisi')) {
            event.target.dataset.selected = event.target.value;
            refresh();
        }
    });
    function loadDivisi(preserve) {
        if (xhr) xhr.abort();
        const department = $('#departemen').val();
        loading = Boolean(department);
        failed = false;
        error.classList.add('d-none');
        rows().forEach(row => {
            const select = row.querySelector('.rincian-divisi');
            if (!preserve) select.dataset.selected = '';
            select.replaceChildren(new Option(loading ? 'Memuat divisi...' : '-- Pilih divisi --', ''));
            select.disabled = loading;
        });
        refresh();
        if (!department) return;
        xhr = $.ajax({
            url: @json(url('/api/get-divisi')) + '/' + encodeURIComponent(department),
            dataType: 'json'
        }).done(data => {
            rows().forEach(row => {
                const select = row.querySelector('.rincian-divisi');
                select.replaceChildren(new Option('-- Pilih divisi --', ''));
                data.forEach(divisi => select.add(new Option(divisi.nama_divisi, divisi.id)));
                select.value = select.dataset.selected;
            });
        }).fail((response, status) => {
            if (status === 'abort') return;
            failed = true;
            error.classList.remove('d-none');
        }).always(() => {
            loading = false;
            rows().forEach(row => row.querySelector('.rincian-divisi').disabled = failed);
            refresh();
        });
    }
    $('#departemen').on('change', () => loadDivisi(false));
    document.getElementById('ulang-divisi').addEventListener('click', () => loadDivisi(true));
    form.addEventListener('submit', event => {
        if (loading || failed) {
            event.preventDefault();
            return;
        }
        let firstInvalid;
        rows().forEach(row => {
            const editor = editors.get(row);
            row.querySelector('.rincian-kualifikasi').value = editor.root.innerHTML;
            if (!editor.getText().replace(/[\s\u00a0\u200b]/g, '')) {
                row.querySelector('.kualifikasi-error').classList.remove('d-none');
                editor.root.setAttribute('aria-invalid', 'true');
                firstInvalid = firstInvalid || editor;
            }
        });
        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
            return;
        }
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Menyimpan...';
    });
    refresh();
    rows().forEach(initEditor);
    loadDivisi(true);
});
</script>
@endpush
