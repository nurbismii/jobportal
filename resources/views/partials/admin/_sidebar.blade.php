<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('img/logo-title.png') }}" class="w-100" height="50" alt="">
        </div>
        <div class="sidebar-brand-text mx-3">V-Hire<sup></sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">
    <li class="nav-item"><a class="nav-link" href="{{ route('internal-accounts.landing') }}"><i class="fas fa-fw fa-th-large"></i><span>Beranda Internal</span></a></li>
    @if(auth()->user()->role === 'admin')
    <li class="nav-item"><a class="nav-link" href="{{ route('internal-accounts.index') }}"><i class="fas fa-fw fa-user-shield"></i><span>Akun Internal</span></a></li>
    @endif

    <!-- Nav Item - Dashboard -->
    @if(auth()->user()->hasModulePermission('dashboard'))
    <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('home') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    @endif

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Master
    </div>

    @if(auth()->user()->hasModulePermission('lamaran'))
    <li class="nav-item"><a class="nav-link" href="{{ route('lamarans.index') }}"><i class="fas fa-fw fa-file-alt"></i><span>Lamaran</span></a></li>
    @endif

    @if(auth()->user()->hasModulePermission('pengguna'))
    <li class="nav-item {{ request()->routeIs('pengguna.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('pengguna.index') }}">
            <i class="fas fa-fw fa-users"></i>
            <span>Pengguna</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('lowongan') || auth()->user()->hasModulePermission('peralihan'))
    <li class="nav-item {{ request()->routeIs('lowongan.**', 'peralihan.**') ? 'active' : '' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLowongan"
            aria-expanded="true" aria-controls="collapseLowongan">
            <i class="fas fa-fw fa-street-view"></i>
            <span>Lowongan</span>
        </a>
        <div id="collapseLowongan" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Setting Lowongan:</h6>
                @if(auth()->user()->hasModulePermission('lowongan'))<a class="collapse-item {{ request()->routeIs('lowongan.**') ? 'active' : '' }}" href="{{ route('lowongan.index') }}">Lowongan</a>@endif
                @if(auth()->user()->hasModulePermission('peralihan'))<a class="collapse-item {{ request()->routeIs('peralihan.**') ? 'active' : '' }}" href="{{ route('peralihan.index') }}">Peralihan Pelamar</a>@endif
            </div>
        </div>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('ptk'))
    <li class="nav-item {{ request()->routeIs('permintaan-tenaga-kerja.**') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('permintaan-tenaga-kerja.index') }}">
            <i class="fas fa-fw fa-user-plus"></i>
            <span>Permintaan Tenaga Kerja</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('kandidat'))
    <li class="nav-item {{ request()->routeIs('kandidat-potensial.**') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('kandidat-potensial.index') }}">
            <i class="fas fa-fw fa-user-check"></i>
            <span>Kandidat Potensial</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('assessment'))
    <li class="nav-item {{ request()->routeIs('assessment-links.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('assessment-links.index') }}">
            <i class="fas fa-fw fa-link"></i>
            <span>Link Asesmen</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('pkwt'))
    <li class="nav-item {{ request()->routeIs('pkwt-contracts.*', 'pkwt-contract-settings.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('pkwt-contracts.index') }}">
            <i class="fas fa-fw fa-file-signature"></i>
            <span>PKWT 1</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('pkwt_settings'))
    <li class="nav-item"><a class="nav-link" href="{{ route('pkwt-contract-settings.edit') }}"><i class="fas fa-fw fa-cog"></i><span>Pengaturan PKWT</span></a></li>
    @endif

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Addons
    </div>

    @if(auth()->user()->hasModulePermission('personal'))
    <li class="nav-item {{ request()->routeIs('personal-file.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('personal-file.index') }}">
            <i class="fas fa-fw fa-folder-open"></i>
            <span>Personal File</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('pengumuman'))
    <li class="nav-item {{ request()->routeIs('pengumumans.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('pengumumans.index') }}">
            <i class="fas fa-fw fa-pen"></i>
            <span>Pengumuman</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('email'))
    <li class="nav-item {{ request()->routeIs('email-blast-log.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('email-blast-log.index') }}">
            <i class="fas fa-fw fa-list"></i>
            <span>Email log</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('email', 'create'))
    <li class="nav-item {{ request()->routeIs('email-blast-log.create') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('email-blast-log.create') }}">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Blast Email HR</span>
        </a>
    </li>
    @endif

    @if(auth()->user()->hasModulePermission('recovery'))
    <li class="nav-item {{ request()->routeIs('account-recovery-requests.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('account-recovery-requests.index') }}">
            <i class="fas fa-fw fa-unlock-alt"></i>
            <span>
                Request Lupa Akun
                @if(($pendingAccountRecoveryRequests ?? 0) > 0)
                <span class="badge badge-danger badge-counter ml-1">{{ $pendingAccountRecoveryRequests }}</span>
                @endif
            </span>
        </a>
    </li>
    @endif

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
