<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('img/logo-title.png') }}" class="w-100" height="50" alt="">
        </div>
        <div class="sidebar-brand-text mx-3">V-Hire<sup></sup></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('home') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Master
    </div>

    <li class="nav-item {{ request()->routeIs('pengguna.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('pengguna.index') }}">
            <i class="fas fa-fw fa-users"></i>
            <span>Pengguna</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('lowongan.**') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('lowongan.index') }}">
            <i class="fas fa-fw fa-street-view"></i>
            <span>Lowongan</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('permintaan-tenaga-kerja.**') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('permintaan-tenaga-kerja.index') }}">
            <i class="fas fa-fw fa-user-plus"></i>
            <span>Permintaan Tenaga Kerja</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('kandidat-potensial.**') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('kandidat-potensial.index') }}">
            <i class="fas fa-fw fa-user-check"></i>
            <span>Kandidat Potensial</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Addons
    </div>

    <li class="nav-item {{ request()->routeIs('personal-file.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('personal-file.index') }}">
            <i class="fas fa-fw fa-folder-open"></i>
            <span>Personal File</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('pengumumans.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('pengumumans.index') }}">
            <i class="fas fa-fw fa-pen"></i>
            <span>Pengumuman</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('email-blast-log.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('email-blast-log.index') }}">
            <i class="fas fa-fw fa-list"></i>
            <span>Email log</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>