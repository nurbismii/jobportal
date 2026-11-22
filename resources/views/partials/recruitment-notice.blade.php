<div class="modal fade" id="recruitmentNotice" lang="id" tabindex="-1"
    aria-labelledby="recruitmentNoticeTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 px-3 px-sm-4 pt-4 pb-0">
                <h2 class="modal-title fs-5 fw-bold text-danger" id="recruitmentNoticeTitle">
                    <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>
                    Waspada Penipuan Rekrutmen
                </h2>
            </div>
            <div class="modal-body px-3 px-sm-4 pb-4">
                <div class="alert-danger text-dark rounded-3 p-3 p-sm-4">
                    <p>
                        PT Virtue Dragon Nickel Industry (PT VDNI) <strong>tidak pernah memungut biaya apa pun</strong>
                        dalam proses rekrutmen. Waspadai permintaan pembayaran atau pemesanan melalui agen perjalanan
                        yang mengatasnamakan PT VDNI.
                    </p>
                    <p>
                        Pelamar diharapkan siap mengikuti seluruh tahapan seleksi PT VDNI.
                    </p>
                    <p>
                        Portal resmi rekrutmen PT VDNI adalah <strong class="text-break">recruitment.vdnisite.com</strong>.
                        Cek hasil seleksi melalui menu <strong>Riwayat Proses Lamaran</strong> setelah masuk.
                    </p>
                    <p>
                        Hubungi Tim Rekrutmen PT VDNI di
                        <a class="fw-bold text-decoration-underline text-break" href="mailto:recruitment@vdnisite.com">recruitment@vdnisite.com</a>
                        untuk mengonfirmasi hasil seleksi, memeriksa keaslian undangan, atau melaporkan dugaan penipuan.
                    </p>
                    <div class="text-center">
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2" data-bs-dismiss="modal">
                            Ya, saya mengerti
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        if (!window.bootstrap?.Modal) return;

        const notice = document.getElementById('recruitmentNotice');
        const storageKey = 'vhire.recruitmentNoticeDismissed';

        try {
            if (sessionStorage.getItem(storageKey)) return;
        } catch {
            // Tampilkan peringatan meski browser memblokir penyimpanan.
        }

        notice.addEventListener('hidden.bs.modal', () => {
            try {
                sessionStorage.setItem(storageKey, '1');
            } catch {
                // Peringatan tetap bisa ditutup saat penyimpanan diblokir.
            }
        }, { once: true });

        new bootstrap.Modal(notice).show();
    })();
</script>
@endpush
