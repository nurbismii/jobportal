<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Admin <sup>2</sup></div>
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

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>