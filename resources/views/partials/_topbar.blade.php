<!-- Topbar Peringatan Start -->
<div id="topbar-warning" class="container-fluid px-0 bg-warning bg-opacity-25" style="border-bottom: 1px solid #ccc; position: relative;">
    <div class="container py-2 position-relative">
        <button onclick="closeWarning()" 
                style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 20px; color: #000; cursor: pointer;">
            &times;
        </button>
        <div class="text-center text-lg-start">
            <h6 class="text-danger fw-bold mb-1">Hati-Hati Penipuan!</h6>
            <p class="mb-0 text-dark small">
                PT Virtue Dragon Nickel Industry (PT VDNI) menyampaikan bahwa perusahaan tidak pernah meminta biaya apapun dalam proses perekrutan karyawan. 
                Harap waspada terhadap oknum yang menggunakan nama PT VDNI untuk meminta biaya dari para pencari kerja.
            </p>
        </div>
    </div>
</div>
<!-- Topbar Peringatan End -->

<!-- Script -->
<script>
function closeWarning() {
    document.getElementById("topbar-warning").style.display = "none";
}
</script>

<!-- Topbar Start -->
<div class="container-fluid topbar px-0 px-lg-4 bg-light py-2 d-none d-lg-block">
    <div class="container">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-8 text-center text-lg-start mb-lg-0">
                <div class="d-flex flex-wrap">
                    <div class="ps-3">
                        <a href="mailto:vdnirekrutmen88@gmail.com" class="text-muted small"><i class="fas fa-envelope text-primary me-2"></i>vdnirekrutmen88@gmail.com</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-flex justify-content-end">
                    <div class="d-flex border-end border-primary pe-3">
                        <a class="btn p-0 text-primary me-3" href="https://www.instagram.com/hr_vdni" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a class="btn p-0 text-primary me-0" href="https://www.linkedin.com/company/pt-virtue-dragon-nickel-industry" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                    <div class="dropdown ms-3">
                        <a href="#" class="dropdown-toggle text-dark" data-bs-toggle="dropdown"><small><i class="fas fa-globe-europe text-primary me-2"></i> English</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item">English</a>
                            <a href="#" class="dropdown-item">Indonesia</a>
                            <a href="#" class="dropdown-item">Chinese</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Topbar End -->