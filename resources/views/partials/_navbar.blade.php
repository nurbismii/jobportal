<div class="container-fluid nav-bar px-0 px-lg-4 py-lg-0">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <a href="#" class="navbar-brand p-0">
                <h4 class="text-primary mb-0"><img src="{{ asset('img/logo-vdni.png') }}" alt="VDNI Logo" style="height: 50px; width: 120px"></i></h4>
                <!-- <img src="img/logo.png" alt="Logo"> -->
            </a>
            @if(!Auth::check())
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav mx-0 mx-lg-auto">
                    <a href="/" class="nav-item nav-link {{ request()->is('/') ? 'active' : '' }}">Beranda</a>
                    <a href="{{ route('lowongan-kerja.index') }}" class="nav-item nav-link {{ request()->routeIs('lowongan-kerja.*') ? 'active' : '' }}">Lowongan Kerja</a>
                    <a href="{{ route('pengumuman.index') }}" class="nav-item nav-link {{ request()->routeIs('pengumuman.*') ? 'active' : '' }}">Pengumuman</a>
                    <a href="{{ route('bantuan.index') }}" class="nav-item nav-link {{ request()->routeIs('bantuan.*') ? 'active' : '' }}">Bantuan</a>

                    <!-- Tombol login dan daftar untuk mobile -->
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-md-5 my-2 d-xl-none">
                        Masuk
                    </a>
                    <a href="{{ route('pendaftaran.index') }}" class="btn btn-outline-primary rounded-pill px-md-5 my-2 d-xl-none">
                        Daftar
                    </a>
                </div>
            </div>

            <div class="d-none d-xl-flex flex-shrink-0 ps-4">
                <a href="{{ route('pendaftaran.index') }}" class="btn btn-outline-primary rounded-pill px-md-5">
                    Daftar
                </a>
                <div class="d-flex flex-column ms-3">
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-md-5">Masuk</a>
                </div>
            </div>
            @else
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>

            @php
            $step = calcutaionStep(auth()->user()->biodata ?? null);

            function disableIf($requiredStep, $currentStep) {
            // Jika admin, tidak pernah disable
            if (auth()->check() && auth()->user()->role === 'admin') {
            return '';
            }

            // Default rule
            return $currentStep < $requiredStep ? 'disabled opacity-50 pointer-events-none' : '' ;
                }
            @endphp

                <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav mx-0 mx-lg-auto">

                    <a href="/" class="nav-item nav-link {{ request()->is('/') ? 'active' : '' }}">Beranda</a>

                    <a href="{{ route('biodata.index') }}"
                        class="nav-item nav-link {{ request()->routeIs('biodata.*') ? 'active' : '' }}">
                        Formulir Biodata
                    </a>

                    <a href="{{ route('lowongan-kerja.index') }}"
                        class="nav-item nav-link {{ request()->routeIs('lowongan-kerja.*') ? 'active' : '' }} {{ disableIf(6, $step) }}">
                        Lowongan Kerja
                    </a>

                    <a href="{{ route('lamaran.index') }}"
                        class="nav-item nav-link {{ request()->routeIs('lamaran.*') ? 'active' : '' }} {{ disableIf(6, $step) }}">
                        Lamaran
                    </a>

                    <a href="{{ route('pengumuman.index') }}"
                        class="nav-item nav-link {{ request()->routeIs('pengumuman.*') ? 'active' : '' }}">
                        Pengumuman
                    </a>

                    <a href="{{ route('pengumuman.index') }}"
                        class="nav-item nav-link {{ request()->routeIs('pengumuman.*') ? 'active' : '' }}">
                        Panduan Melamar
                    </a>
                </div>
    </div>


    <div class="d-none d-xl-flex align-items-center ps-4 dropdown">
        <button class="btn btn-light btn-lg-square rounded-circle position-relative shadow-sm" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user fa-2x text-primary"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 mt-2" aria-labelledby="dropdownMenuButton1">
            @if(Auth::user()->role == 'user')
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('profil.index') }}">
                    <i class="fa fa-id-card me-2 text-primary"></i> Profil
                </a>
            </li>
            @else
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('home') }}">
                    <i class="fa fa-briefcase me-2 text-primary"></i> Kelola Job Portal
                </a>
            </li>
            @endif
            <li>
                <hr class="dropdown-divider">
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-sign-out-alt me-2 text-danger"></i> Keluar
                </a>
            </li>
        </ul>
        <div class="d-flex flex-column ms-3">
            <span class="small text-muted">{{ Auth::user()->no_ktp }}</span>
            <a href="#" class="fw-bold text-dark text-decoration-none">{{ Auth::user()->name }}</a>
        </div>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        {{ csrf_field() }}
    </form>

    @endif
    </nav>
</div>
</div>