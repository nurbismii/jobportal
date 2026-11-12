(() => {
    'use strict';
    function scoreComponents(answers, maximum) {
        const answered = answers.filter(answer => answer === 'Ya' || answer === 'Tidak').length;
        const yes = answers.filter(answer => answer === 'Ya').length;
        return {answered, yes, count: answers.length, score: answered && answers.length ? Math.round(yes * maximum / answers.length * 100) / 100 : null};
    }
    function translatedLabel(labels, key, replacements = {}) {
        let text = labels[key] ?? key;
        for (const [name, value] of Object.entries(replacements)) text = text.replaceAll(':' + name, () => String(value));
        return text;
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {scoreComponents, translatedLabel};
    if (typeof document === 'undefined') return;
    const config = JSON.parse(document.getElementById('field-test-data').textContent);
    const t = (key, replacements) => translatedLabel(config.labels ?? {}, key, replacements);
    const form = document.getElementById('field-test-form');
    const steps = [...form.querySelectorAll('[data-step]')];
    const message = document.getElementById('form-message');
    const state = document.getElementById('save-state');
    const sections = [...form.querySelectorAll('[data-checklist-section]')];
    const note = document.getElementById('petugas-note');
    let step = 0, timer, dirty = false, pending = null, changed = 0, conflict = false;
    const values = () => {
        const result = Object.fromEntries([...form.querySelectorAll('[data-value]')].filter(input => input.type !== 'radio' || input.checked).map(input => [input.dataset.value, input.value === '' ? null : input.value]));
        result.components = Object.fromEntries(sections.map(section => [section.dataset.checklistSection, Object.fromEntries([...section.querySelectorAll('[data-component]:checked')].map(input => [input.dataset.index, input.value]))]));
        return result;
    };
    function showMessage(text) {
        message.textContent = t(text);
        message.hidden = false;
        message.focus();
    }
    function summary() {
        let total = 0, answered = 0, count = 0, hasScore = false;
        sections.forEach(section => {
            const id = section.dataset.checklistSection;
            const names = [...new Set([...section.querySelectorAll('[data-component]')].map(input => input.name))];
            const answers = names.map(name => section.querySelector('input[name="' + name + '"]:checked')?.value ?? null);
            const result = scoreComponents(answers, Number(section.dataset.max));
            const score = config.legacy ? (section.dataset.legacyScore === '' ? null : Number(section.dataset.legacyScore)) : result.score;
            const formatted = score === null ? '—' : score.toLocaleString('id-ID', {maximumFractionDigits: 2});
            const progress = config.legacy ? t('Nilai manual tersimpan') : t(':answered/:count dijawab · :yes Ya', {answered: result.answered, count: result.count, yes: result.yes});
            section.querySelector('[data-section-score]').textContent = formatted;
            section.querySelector('[data-component-progress]').textContent = progress;
            document.querySelector('[data-summary="' + id + '"]').textContent = formatted;
            document.querySelector('[data-summary-progress="' + id + '"]').textContent = progress;
            answered += result.answered;
            count += result.count;
            if (score !== null) { total += score; hasScore = true; }
        });
        document.querySelectorAll('[data-total]').forEach(el => el.textContent = hasScore ? total.toLocaleString('id-ID', {maximumFractionDigits: 2}) : '—');
        document.getElementById('score-progress').textContent = config.legacy ? t('Nilai manual tersimpan') : t(':answered dari :count komponen sudah dijawab', {answered, count}) + ' · ' + t(answered < count ? 'Belum lengkap' : 'Lengkap');
        document.getElementById('decision-summary').textContent = t(values().decision || 'Belum dipilih');
        document.getElementById('note-summary').textContent = note.value || t('Tidak ada catatan.');
        note.required = values().decision === 'Tidak Lulus';
    }
    function showStep(index, focus = true) {
        step = Math.max(0, Math.min(2, index));
        steps.forEach((section, i) => section.hidden = i !== step);
        document.querySelectorAll('[data-step-label]').forEach((item, i) => i === step ? item.setAttribute('aria-current', 'step') : item.removeAttribute('aria-current'));
        document.getElementById('previous-step').hidden = step === 0;
        document.getElementById('next-step').hidden = step === 2;
        document.getElementById('next-step').textContent = t(step === 1 ? 'Tanda tangan →' : 'Cek komponen →');
        if (focus) steps[step].querySelector('h2').focus({preventScroll: true});
        if (focus) window.scrollTo({top: 0, behavior: 'smooth'});
        summary();
    }
    async function request(url, payload) {
        const response = await fetch(url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify(payload)})
            .catch(() => { throw new Error(t('Gagal menghubungi server. Isian tetap di layar. Periksa jaringan lalu coba lagi.')); });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 409) conflict = true;
            const details = result.errors ? t('Periksa isian dan coba kembali.') + '\n' + Object.values(result.errors).flat().map(text => t(text)).join('\n') : result.message ? t(result.message) : null;
            throw new Error(response.status === 419 || response.status === 403 || response.status === 404 ? t('Akses tes sudah berakhir. Salin catatan yang belum tersimpan, lalu buka kembali link dari HR.') : response.status === 429 ? t('Terlalu banyak percobaan. Tunggu satu menit, lalu coba kembali.') : details || t('Gagal menghubungi server. Isian tetap di layar. Periksa jaringan lalu coba lagi.'));
        }
        config.version = result.version;
        return result;
    }
    async function saveDraft() {
        clearTimeout(timer);
        if (config.locked || !dirty) return;
        if (conflict) throw new Error(t('Formulir berubah di perangkat lain. Salin isian yang diperlukan, lalu muat ulang halaman.'));
        if (pending) {
            await pending;
            if (dirty) return saveDraft();
            return;
        }
        const snapshot = changed;
        state.textContent = t('Menyimpan draf…');
        state.classList.remove('error');
        pending = request(config.saveUrl, {version: config.version, values: values(), petugas_note: note.value})
            .then(result => {
                dirty = changed !== snapshot;
                state.textContent = dirty ? t('Ada perubahan baru…') : t('✓ Draf tersimpan pukul :time', {time: result.saved_at});
            }).catch(error => {
                state.textContent = t('Belum tersimpan · Tekan Simpan draf untuk mencoba lagi');
                state.classList.add('error');
                throw error;
            }).finally(() => pending = null);
        await pending;
        if (dirty) return saveDraft();
    }
    form.addEventListener('submit', event => event.preventDefault());
    if (!config.locked) form.addEventListener('input', event => {
        if (!event.target.matches('[data-value], [data-component], #petugas-note')) return;
        dirty = true;
        changed++;
        summary();
        state.textContent = t('Perubahan belum tersimpan…');
        clearTimeout(timer);
        timer = setTimeout(() => saveDraft().catch(() => {}), 1000);
    });
    function validateSections() {
        for (let i = 0; i < 2; i++) {
            for (const input of steps[i].querySelectorAll('input, textarea')) {
                if (!input.disabled && !input.checkValidity()) {
                    showStep(i);
                    input.reportValidity();
                    return false;
                }
            }
        }
        return true;
    }
    document.getElementById('next-step').addEventListener('click', () => {
        if (step === 1 && !config.locked && !validateSections()) return;
        showStep(step + 1);
    });
    document.getElementById('previous-step').addEventListener('click', () => showStep(step - 1));
    document.getElementById('save-draft')?.addEventListener('click', async event => {
        event.target.disabled = true;
        message.hidden = true;
        try { await saveDraft(); } catch (error) { showMessage(error.message); }
        finally { event.target.disabled = false; }
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });

    const canvas = document.getElementById('signature-pad');
    let hasInk = false;
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let drawing = false;
        const point = event => {
            const bounds = canvas.getBoundingClientRect();
            return [(event.clientX - bounds.left) * canvas.width / bounds.width, (event.clientY - bounds.top) * canvas.height / bounds.height];
        };
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#172c40';
        canvas.addEventListener('pointerdown', event => {
            event.preventDefault();
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            ctx.beginPath();
            ctx.moveTo(...point(event));
        });
        canvas.addEventListener('pointermove', event => {
            if (!drawing) return;
            event.preventDefault();
            ctx.lineTo(...point(event));
            ctx.stroke();
            hasInk = true;
        });
        ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(name => canvas.addEventListener(name, () => drawing = false));
        const clear = () => { ctx.clearRect(0, 0, canvas.width, canvas.height); hasInk = false; };
        document.getElementById('clear-signature').addEventListener('click', clear);
        document.getElementById('name-signature').addEventListener('click', () => {
            clear();
            ctx.font = 'italic 36px Georgia, serif';
            ctx.fillStyle = '#172c40';
            ctx.fillText(config.signerName, 30, 125, 580);
            hasInk = true;
        });
        document.getElementById('sign-button').addEventListener('click', async event => {
            if (!validateSections()) return;
            if (!hasInk) { showMessage('Tanda tangan terlebih dahulu, atau gunakan tombol nama sebagai tanda tangan.'); return; }
            if (!document.getElementById('signing-consent').checked) { showMessage('Centang persetujuan setelah memeriksa ringkasan nilai.'); document.getElementById('signing-consent').focus(); return; }
            const button = event.target;
            button.disabled = true;
            button.textContent = t('Menyimpan tanda tangan…');
            message.hidden = true;
            // Freeze score inputs while saving and signing the exact reviewed values.
            const editable = [...form.querySelectorAll('[data-value], [data-component], #petugas-note')].filter(input => !input.disabled);
            editable.forEach(input => input.disabled = true);
            try {
                await saveDraft();
                await request(config.signUrl, {version: config.version, revision: config.revision, role: config.nextRole, signature_data: canvas.toDataURL('image/png'), consent: true});
                dirty = false;
                window.location.assign(window.location.pathname + '?step=3');
            } catch (error) {
                showMessage(error.message);
                button.disabled = false;
                button.textContent = t('Coba simpan tanda tangan lagi');
                editable.forEach(input => input.disabled = false);
            }
        });
    }
    const dialog = document.getElementById('revision-dialog');
    if (dialog) {
        document.getElementById('open-revision').addEventListener('click', () => dialog.showModal());
        document.getElementById('close-revision').addEventListener('click', () => dialog.close());
        document.getElementById('revision-form').addEventListener('submit', async event => {
            event.preventDefault();
            const button = event.target.querySelector('[type=submit]');
            const errorBox = document.getElementById('revision-error');
            button.disabled = true;
            errorBox.hidden = true;
            try {
                await request(config.reviseUrl, {version: config.version, role: document.getElementById('revision-role').value, reason: document.getElementById('revision-reason').value});
                window.location.assign(window.location.pathname + '?step=2');
            } catch (error) { errorBox.textContent = t(error.message); errorBox.hidden = false; button.disabled = false; }
        });
    }
    showStep(config.locked || new URLSearchParams(window.location.search).get('step') === '3' ? 2 : new URLSearchParams(window.location.search).get('step') === '2' ? 1 : 0, false);
})();
