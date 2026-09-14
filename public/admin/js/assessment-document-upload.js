(function () {
    'use strict';

    async function processQueue(items, send, update) {
        for (const item of items) {
            if (item.state === 'saved' || item.state === 'invalid') continue;
            item.state = 'uploading';
            item.message = 'Mengirim…';
            update();
            try {
                await send(item);
                item.state = 'saved';
                item.message = 'Tersimpan';
            } catch (error) {
                item.state = 'failed';
                item.message = error.message;
                if (error.stopQueue) {
                    items.filter(entry => entry.state === 'pending' || entry.state === 'failed').forEach(entry => {
                        entry.state = 'failed';
                        entry.message = error.message;
                    });
                    update();
                    break;
                }
            }
            update();
        }
    }

    if (typeof module !== 'undefined' && module.exports) module.exports = { processQueue };
    if (typeof document === 'undefined') return;

    document.querySelectorAll('[data-drop-zone]').forEach(zone => {
        const input = zone.querySelector('input[type=file]');
        const selection = zone.parentElement.querySelector('[data-file-selection]');
        let dragDepth = 0;

        function showSelection() {
            selection.classList.remove('drop-error');
            selection.replaceChildren();
            const files = Array.from(input.files);
            if (!files.length) {
                selection.textContent = 'Belum ada file dipilih.';
                return;
            }
            const title = document.createElement('strong');
            title.textContent = files.length + ' file dipilih. Klik Kirim file untuk mulai.';
            const list = document.createElement('ul');
            files.forEach((file, index) => {
                const item = document.createElement('li');
                const name = document.createElement('span');
                name.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'remove-selected-file';
                remove.textContent = 'Hapus';
                remove.setAttribute('aria-label', 'Hapus pilihan ' + file.name);
                remove.disabled = input.disabled;
                remove.addEventListener('click', () => {
                    if (input.disabled || input.form.dataset.submitting === 'true') return;
                    const remaining = Array.from(input.files).filter((_, position) => position !== index);
                    try {
                        if (!remaining.length) {
                            input.value = '';
                        } else {
                            const transfer = new DataTransfer();
                            remaining.forEach(file => transfer.items.add(file));
                            input.files = transfer.files;
                        }
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        const buttons = selection.querySelectorAll('.remove-selected-file');
                        if (buttons.length) buttons[Math.min(index, buttons.length - 1)].focus();
                        else input.focus();
                    } catch (_) {
                        input.form.querySelector('[data-status]').textContent = 'Browser tidak mendukung penghapusan sebagian pilihan. Klik area unggah untuk memilih ulang file.';
                    }
                });
                item.append(name, remove);
                list.appendChild(item);
            });
            selection.append(title, list);
        }
        function syncDisabled() {
            zone.classList.toggle('is-disabled', input.disabled);
            zone.setAttribute('aria-disabled', String(input.disabled));
            selection.querySelectorAll('.remove-selected-file').forEach(button => { button.disabled = input.disabled; });
            if (input.disabled) zone.classList.remove('is-dragging');
        }
        new MutationObserver(syncDisabled).observe(input, { attributes: true, attributeFilter: ['disabled'] });
        syncDisabled();
        input.addEventListener('change', showSelection);
        zone.addEventListener('dragenter', event => {
            event.preventDefault();
            dragDepth++;
            if (!input.disabled) zone.classList.add('is-dragging');
        });
        zone.addEventListener('dragover', event => {
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = input.disabled ? 'none' : 'copy';
        });
        zone.addEventListener('dragleave', event => {
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (!dragDepth) zone.classList.remove('is-dragging');
        });
        zone.addEventListener('drop', event => {
            event.preventDefault();
            dragDepth = 0;
            zone.classList.remove('is-dragging');
            if (input.disabled) return;
            const files = Array.from(event.dataTransfer?.files || []);
            const extensions = input.multiple ? /\.pdf$/i : /\.(xls|xlsx)$/i;
            if (!files.length || (!input.multiple && files.length !== 1)
                || files.some(file => !extensions.test(file.name) || file.size === 0 || file.size > 10 * 1024 * 1024)) {
                selection.classList.add('drop-error');
                selection.textContent = (input.multiple ? 'Gunakan file PDF' : 'Pilih tepat satu file Excel (.xls/.xlsx)')
                    + ' tidak kosong, maksimal 10 MB/file. Pilihan sebelumnya tidak berubah.';
                return;
            }
            try {
                const transfer = new DataTransfer();
                files.forEach(file => transfer.items.add(file));
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (_) {
                selection.classList.add('drop-error');
                selection.textContent = 'Drag & drop tidak didukung browser ini. Klik area unggah untuk memilih file.';
            }
        });
    });

    // Do not navigate away to a local PDF when files are dropped outside an upload area.
    ['dragover', 'drop'].forEach(type => document.addEventListener(type, event => {
        if (Array.from(event.dataTransfer?.types || []).includes('Files')) event.preventDefault();
    }));

    document.querySelectorAll('[data-pdf-queue]').forEach(form => {
        const input = form.querySelector('input[type=file]');
        const submit = form.querySelector('button[type=submit]');
        const status = form.querySelector('[data-status]');
        const panel = form.querySelector('[data-queue-panel]');
        const list = form.querySelector('[data-queue-list]');
        const total = form.querySelector('[data-total-progress]');
        const retry = form.querySelector('[data-retry]');
        const fresh = form.querySelector('[data-new-selection]');
        const refresh = form.querySelector('[data-refresh]');
        let items = [], running = false, folderId = '', otherControls = [];

        function update() {
            const saved = items.filter(item => item.state === 'saved').length;
            const failed = items.filter(item => item.state === 'failed' || item.state === 'invalid').length;
            items.forEach(item => { item.label.textContent = item.file.name + ' — ' + item.message; });
            total.max = items.length || 1;
            total.value = saved;
            status.textContent = saved + '/' + items.length + ' file tersimpan, ' + failed + ' gagal.' + (running ? ' Pengiriman sedang berjalan…' : '');
            retry.hidden = running || !items.some(item => item.state === 'failed');
            fresh.hidden = running;
            refresh.hidden = running || saved === 0;
        }

        function request(item) {
            return new Promise((resolve, reject) => {
                const body = new FormData();
                body.append('_token', form.querySelector('[name=_token]').value);
                body.append('category', 'mcu_detail');
                body.append('folder_id', folderId);
                body.append('upload_id', item.id);
                body.append('files[]', item.file);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.dataset.queueUrl);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.timeout = 180000;
                xhr.upload.onprogress = event => {
                    if (!event.lengthComputable) return;
                    const percent = Math.round(event.loaded / event.total * 100);
                    item.message = percent === 100 ? 'Menunggu penyimpanan server…' : 'Mengirim ' + percent + '%';
                    item.label.textContent = item.file.name + ' — ' + item.message;
                };
                xhr.onerror = () => reject(new Error('Koneksi terputus. Coba ulang file ini.'));
                xhr.ontimeout = () => reject(new Error('Waktu pengiriman habis. Coba ulang file ini.'));
                xhr.onload = () => {
                    let data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (_) { /* Proxy errors can be HTML. */ }
                    if (xhr.status >= 200 && xhr.status < 300 && data.saved === true) return resolve();
                    if (xhr.status === 429) {
                        const error = new Error('Menunggu batas pengiriman server.');
                        error.retryAfter = Math.min(120, Math.max(1, Number(xhr.getResponseHeader('Retry-After')) || 60));
                        return reject(error);
                    }
                    let message = 'Server gagal memproses file. Silakan coba ulang.';
                    if (xhr.status === 422) message = Object.values(data.errors || {}).flat().join(' ') || 'File tidak valid.';
                    if (xhr.status === 413) message = 'Ukuran ditolak server. Periksa batas unggahan PHP/web server.';
                    if ([401, 403, 404, 419].includes(xhr.status)) message = 'Akses atau sesi berakhir. Periksa masa berlaku link dan buka kembali melalui PIN.';
                    const error = new Error(message);
                    error.stopQueue = [401, 403, 404, 419].includes(xhr.status);
                    reject(error);
                };
                xhr.send(body);
            });
        }

        async function send(item) {
            try {
                await request(item);
            } catch (error) {
                if (!error.retryAfter) throw error;
                for (let seconds = error.retryAfter; seconds > 0; seconds--) {
                    item.message = 'Melanjutkan otomatis dalam ' + seconds + ' detik…';
                    update();
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
                await request(item);
            }
        }

        async function run() {
            if (running) return;
            running = true;
            otherControls = Array.from(document.querySelectorAll('form input, form select, form button')).filter(control => !form.contains(control) && !control.disabled);
            otherControls.forEach(control => { control.disabled = true; });
            update();
            try {
                await processQueue(items, send, update);
            } finally {
                running = false;
                otherControls.forEach(control => { control.disabled = false; });
                if (items.some(item => item.state === 'saved')) {
                    form.closest('[data-category]').querySelector('[data-stage-status]').textContent = 'Sudah dikirim';
                    const recap = document.querySelector('[data-category=mcu_recap]');
                    if (recap) {
                        recap.querySelectorAll('input[type=file], button[type=submit]').forEach(control => { control.disabled = false; });
                        const hint = recap.querySelector('[data-stage-hint]');
                        if (hint) hint.hidden = true;
                        if (recap.querySelector('[data-stage-status]').textContent === 'Terkunci') recap.querySelector('[data-stage-status]').textContent = 'Siap diunggah';
                    }
                }
                update();
            }
        }

        form.addEventListener('submit', event => {
            event.preventDefault();
            if (running || items.length) return;
            folderId = form.querySelector('[name=folder_id]').value;
            if (!folderId || !input.files.length) return;
            try {
                items = Array.from(input.files).map(file => {
                    const bytes = crypto.getRandomValues(new Uint8Array(16));
                    bytes[6] = (bytes[6] & 15) | 64;
                    bytes[8] = (bytes[8] & 63) | 128;
                    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
                    const id = [hex.slice(0, 8), hex.slice(8, 12), hex.slice(12, 16), hex.slice(16, 20), hex.slice(20)].join('-');
                    const invalid = !/\.pdf$/i.test(file.name) || file.size === 0 || file.size > 10 * 1024 * 1024;
                    const label = document.createElement('li');
                    list.appendChild(label);
                    return { file, id, label, state: invalid ? 'invalid' : 'pending', message: invalid ? 'Pilih PDF tidak kosong, maksimal 10 MB.' : 'Menunggu' };
                });
            } catch (_) {
                status.textContent = 'Browser tidak mendukung unggahan ini. Gunakan browser terbaru.';
                items = [];
                list.replaceChildren();
                return;
            }
            input.disabled = true;
            submit.disabled = true;
            form.querySelector('[name=folder_id]').disabled = true;
            panel.hidden = false;
            run();
        });
        retry.addEventListener('click', run);
        fresh.addEventListener('click', () => {
            items = [];
            list.replaceChildren();
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.disabled = false;
            submit.disabled = false;
            form.querySelector('[name=folder_id]').disabled = false;
            panel.hidden = true;
            status.textContent = 'Pilih file untuk kiriman berikutnya. File yang sudah tersimpan tetap tersedia pada riwayat.';
        });
        refresh.addEventListener('click', () => window.location.reload());
        document.addEventListener('click', event => {
            if (running && event.target.closest('a')) {
                event.preventDefault();
                status.textContent = 'Tunggu pengiriman selesai sebelum berpindah halaman.';
            }
        });
    });
})();
