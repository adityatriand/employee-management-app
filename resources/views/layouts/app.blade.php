<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>WorkforceHub by Arphidh - Manajemen Pegawai</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/searchable-select.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('open-iconic/font/css/open-iconic-bootstrap.css') }}" rel="stylesheet">
</head>

<body class="h-100">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('home') }}" class="sidebar-brand">
                <img src="{{ asset('images/logo.png') }}" alt="WorkforceHub" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <i class="oi oi-briefcase" style="display: none;"></i>
                <span>WorkforceHub</span>
            </a>
        </div>
        <div class="sidebar-nav">
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                    <i class="oi oi-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <i class="oi oi-people"></i>
                    <span>Data Pegawai</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('positions.*') ? 'active' : '' }}" href="{{ route('positions.index') }}">
                    <i class="oi oi-briefcase"></i>
                    <span>Data Jabatan</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('files.*') ? 'active' : '' }}" href="{{ route('files.index') }}">
                    <i class="oi oi-folder"></i>
                    <span>Manajemen File</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('assets.*') ? 'active' : '' }}" href="{{ route('assets.index') }}">
                    <i class="oi oi-box"></i>
                    <span>Manajemen Aset</span>
                </a>
            </div>
            @if(auth()->user()->level == 1)
            <div class="nav-item">
                <div class="nav-divider"></div>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
                    <i class="oi oi-clock"></i>
                    <span>Riwayat Aktivitas</span>
                </a>
            </div>
            <div class="nav-item">
                <div class="nav-divider"></div>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('employees.create') }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Pegawai</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('positions.create') }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Jabatan</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('assets.create') }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Aset</span>
                </a>
            </div>
            @endif
            <div class="nav-item">
                <a class="nav-link" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="oi oi-account-logout"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        <div class="sidebar-footer">
            <div class="footer-logo">
                <img src="{{ asset('images/logo-arphidh.png') }}" alt="Arphidh" class="footer-logo-img" onerror="this.style.display='none';">
            </div>
            <p>by Arphidh</p>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="oi oi-check"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="oi oi-warning"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>

</html>
