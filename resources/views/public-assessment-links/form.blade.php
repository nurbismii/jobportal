<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Form Penilaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container-fluid py-4 py-md-5 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Form Penilaian</h1>
                <a class="btn btn-outline-primary my-2" href="{{ route('assessment-documents.index', $link->public_token) }}">Kirim daftar hadir{{ $link->assessment_type === 'kesehatan' ? ' & hasil MCU' : '' }}</a>
                <p class="text-muted mb-0">Hasil disimpan otomatis setelah Anda selesai mengisi satu baris.</p>
            </div>
        </div>

        <section class="card shadow-sm border-0">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="candidate-search" class="form-label">Cari kandidat</label>
                        <input id="candidate-search" type="search" class="form-control" autocomplete="off" placeholder="Cari nama atau nomor KTP" aria-label="Cari nama atau nomor KTP">
                    </div>
                    <div class="col-md-4">
                        <label for="candidate-status" class="form-label">Status hasil</label>
                        <select id="candidate-status" class="form-select">
                            <option value="all">Semua kandidat</option>
                            <option value="pending">Belum diisi</option>
                            <option value="completed">Sudah diisi</option>
                        </select>
                    </div>
                </div>

                <div id="candidate-loading" class="text-center text-muted py-4">Memuat kandidat…</div>
                <div id="candidate-empty" class="alert alert-secondary d-none mb-0">Tidak ada kandidat yang sesuai.</div>
                <div id="candidate-error" class="alert alert-danger d-none" role="alert">
                    Kandidat tidak dapat dimuat. <button id="retry-load" type="button" class="btn btn-sm btn-outline-danger ms-2">Coba lagi</button>
                </div>
                <div class="table-responsive d-none" id="candidate-table-wrapper">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">No KTP</th>
                                <th scope="col">Nama</th>
                                @foreach ($link->form_schema as $field)
                                    <th scope="col">{{ $field['label'] }}@if ($field['required'] ?? false) <span class="text-danger">*</span>@endif</th>
                                @endforeach
                                <th scope="col">Catatan petugas</th>
                                <th scope="col">Status simpan</th>
                            </tr>
                        </thead>
                        <tbody id="candidate-table-body"></tbody>
                    </table>
                </div>
                <nav id="candidate-pagination" class="mt-3 d-none" aria-label="Navigasi kandidat"></nav>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const schema = @json($link->form_schema);
            const candidatesUrl = @json(route('assessment-links.public.candidates', $link->public_token));
            const autosaveTemplate = @json(route('assessment-links.public.autosave', [$link->public_token, '__CANDIDATE__']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const search = document.getElementById('candidate-search');
            const status = document.getElementById('candidate-status');
            const loading = document.getElementById('candidate-loading');
            const empty = document.getElementById('candidate-empty');
            const error = document.getElementById('candidate-error');
            const tableWrapper = document.getElementById('candidate-table-wrapper');
            const body = document.getElementById('candidate-table-body');
            const pagination = document.getElementById('candidate-pagination');
            const saveTimers = new Map();
            let currentPage = 1;
            let searchTimer;

            const setVisible = (element, visible) => element.classList.toggle('d-none', !visible);
            const buildElement = (tag, className = '') => {
                const element = document.createElement(tag);
                element.className = className;
                return element;
            };

            function inputForField(candidate, field) {
                const value = candidate.result_values && candidate.result_values[field.id] !== undefined
                    ? candidate.result_values[field.id] : '';
                let input;
                if (field.type === 'select') {
                    input = buildElement('select', 'form-select form-select-sm');
                    const placeholder = new Option('Pilih hasil', '');
                    input.add(placeholder);
                    (field.options || []).forEach((option) => input.add(new Option(option, option, false, String(value) === String(option))));
                } else {
                    input = buildElement('input', 'form-control form-control-sm');
                    input.type = field.type === 'number' ? 'number' : 'text';
                    input.value = value;
                    if (field.type === 'number') input.step = 'any';
                }
                input.dataset.field = field.id;
                input.required = Boolean(field.required);
                input.addEventListener(field.type === 'select' ? 'change' : 'input', () => queueAutosave(candidate.id));
                return input;
            }

            function renderCandidates(payload) {
                body.replaceChildren();
                const candidates = payload.data || [];
                setVisible(loading, false);
                setVisible(error, false);
                setVisible(empty, candidates.length === 0);
                setVisible(tableWrapper, candidates.length > 0);
                setVisible(pagination, payload.last_page > 1);

                candidates.forEach((candidate, index) => {
                    const row = document.createElement('tr');
                    row.dataset.candidateId = candidate.id;
                    const number = buildElement('td');
                    number.textContent = String((payload.from || 1) + index);
                    row.append(number);
                    const ktp = buildElement('td');
                    ktp.textContent = candidate.no_ktp || '-';
                    row.append(ktp);
                    const name = buildElement('td');
                    name.textContent = candidate.name || '-';
                    row.append(name);
                    schema.forEach((field) => {
                        const cell = buildElement('td');
                        cell.append(inputForField(candidate, field));
                        row.append(cell);
                    });
                    const noteCell = buildElement('td');
                    const note = buildElement('textarea', 'form-control form-control-sm');
                    note.rows = 2;
                    note.maxLength = 2000;
                    note.dataset.note = 'true';
                    note.value = candidate.petugas_note || '';
                    note.addEventListener('input', () => queueAutosave(candidate.id));
                    noteCell.append(note);
                    row.append(noteCell);
                    const saveCell = buildElement('td');
                    saveCell.dataset.saveStatus = 'true';
                    saveCell.textContent = candidate.last_submitted_at ? 'Tersimpan' : 'Belum diisi';
                    row.append(saveCell);
                    body.append(row);
                });
                renderPagination(payload);
            }

            function renderPagination(payload) {
                pagination.replaceChildren();
                if (payload.last_page <= 1) return;
                const list = buildElement('ul', 'pagination justify-content-end mb-0');
                for (let page = 1; page <= payload.last_page; page++) {
                    const item = buildElement('li', `page-item${page === payload.current_page ? ' active' : ''}`);
                    const button = buildElement('button', 'page-link');
                    button.type = 'button';
                    button.textContent = page;
                    button.disabled = page === payload.current_page;
                    button.addEventListener('click', () => loadCandidates(search.value, page, status.value));
                    item.append(button);
                    list.append(item);
                }
                pagination.append(list);
            }

            async function loadCandidates(query = '', page = 1, selectedStatus = 'all') {
                currentPage = page;
                setVisible(loading, true);
                setVisible(error, false);
                const url = new URL(candidatesUrl, window.location.origin);
                url.searchParams.set('q', query);
                url.searchParams.set('page', page);
                url.searchParams.set('status', selectedStatus);
                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Gagal memuat kandidat');
                    renderCandidates(await response.json());
                } catch (_) {
                    setVisible(loading, false);
                    setVisible(tableWrapper, false);
                    setVisible(pagination, false);
                    setVisible(error, true);
                }
            }

            function queueAutosave(candidateId) {
                const existing = saveTimers.get(candidateId);
                if (existing) clearTimeout(existing);
                const row = body.querySelector(`tr[data-candidate-id="${candidateId}"]`);
                row.querySelector('[data-save-status]').textContent = 'Menyimpan…';
                saveTimers.set(candidateId, setTimeout(() => autosave(row), 700));
            }

            async function autosave(row) {
                const candidateId = row.dataset.candidateId;
                const values = {};
                row.querySelectorAll('[data-field]').forEach((input) => { values[input.dataset.field] = input.value; });
                const petugasNote = row.querySelector('[data-note]').value;
                const statusCell = row.querySelector('[data-save-status]');
                statusCell.textContent = 'Menyimpan…';
                try {
                    const response = await fetch(autosaveTemplate.replace('__CANDIDATE__', candidateId), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        credentials: 'same-origin',
                        body: JSON.stringify({ values, petugas_note: petugasNote }),
                    });
                    if (!response.ok) throw new Error('Gagal menyimpan hasil');
                    statusCell.textContent = 'Tersimpan';
                } catch (_) {
                    statusCell.replaceChildren(document.createTextNode('Gagal disimpan '));
                    const retry = buildElement('button', 'btn btn-sm btn-outline-danger mt-1');
                    retry.type = 'button';
                    retry.textContent = 'Coba lagi';
                    retry.addEventListener('click', () => autosave(row));
                    statusCell.append(retry);
                }
            }

            search.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => loadCandidates(search.value, 1, status.value), 300);
            });
            status.addEventListener('change', () => loadCandidates(search.value, 1, status.value));
            document.getElementById('retry-load').addEventListener('click', () => loadCandidates(search.value, currentPage, status.value));
            loadCandidates();
        })();
    </script>
</body>
</html>
