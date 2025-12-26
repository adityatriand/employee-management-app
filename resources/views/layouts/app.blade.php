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
            <a href="{{ isset($workspace) ? route('workspace.dashboard', ['workspace' => $workspace->slug]) : route('home') }}" class="sidebar-brand">
                @if(isset($workspace) && $workspace->logo)
                <img src="{{ $workspace->logo_url }}"
                     alt="{{ $workspace->name }}"
                     style="max-height: 40px; max-width: 100%; object-fit: contain;"
                     onerror="console.error('Logo failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='inline';">
                @else
                <img src="{{ asset('images/logo.png') }}" alt="WorkforceHub" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                @endif
                <i class="oi oi-briefcase" style="display: none;"></i>
                <span>{{ isset($workspace) ? $workspace->name : 'WorkforceHub' }}</span>
            </a>
        </div>
        <div class="sidebar-nav">
            @php
                $workspace = $workspace ?? null;
                $workspaceSlug = $workspace ? $workspace->slug : null;
                $routeParams = $workspaceSlug ? ['workspace' => $workspaceSlug] : [];
            @endphp

            @if($workspace)
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.dashboard') || request()->routeIs('workspace.home') ? 'active' : '' }}" href="{{ route('workspace.dashboard', $routeParams) }}">
                    <i class="oi oi-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            @if(auth()->user()->level == 1)
            {{-- Admin menu --}}
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.employees.*') ? 'active' : '' }}" href="{{ route('workspace.employees.index', $routeParams) }}">
                    <i class="oi oi-people"></i>
                    <span>Data Pegawai</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.positions.*') ? 'active' : '' }}" href="{{ route('workspace.positions.index', $routeParams) }}">
                    <i class="oi oi-briefcase"></i>
                    <span>Data Jabatan</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.files.*') ? 'active' : '' }}" href="{{ route('workspace.files.index', $routeParams) }}">
                    <i class="oi oi-folder"></i>
                    <span>Manajemen File</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.assets.*') ? 'active' : '' }}" href="{{ route('workspace.assets.index', $routeParams) }}">
                    <i class="oi oi-box"></i>
                    <span>Manajemen Aset</span>
                </a>
            </div>
            <div class="nav-item">
                <div class="nav-divider"></div>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.edit') ? 'active' : '' }}" href="{{ route('workspace.edit', $routeParams) }}">
                    <i class="oi oi-cog"></i>
                    <span>Pengaturan Workspace</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.settings.index') ? 'active' : '' }}" href="{{ route('workspace.settings.index', $routeParams) }}">
                    <i class="oi oi-cog"></i>
                    <span>Pengaturan Password</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.activity-logs.*') ? 'active' : '' }}" href="{{ route('workspace.activity-logs.index', $routeParams) }}">
                    <i class="oi oi-clock"></i>
                    <span>Riwayat Aktivitas</span>
                </a>
            </div>
            <div class="nav-item">
                <div class="nav-divider"></div>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('workspace.employees.create', $routeParams) }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Pegawai</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('workspace.positions.create', $routeParams) }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Jabatan</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('workspace.assets.create', $routeParams) }}">
                    <i class="oi oi-plus"></i>
                    <span>Tambah Aset</span>
                </a>
            </div>
            @endif

            <div class="nav-item">
                <div class="nav-divider"></div>
            </div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('workspace.password.*') ? 'active' : '' }}" href="{{ route('workspace.password.change', $routeParams) }}">
                    <i class="oi oi-lock-locked"></i>
                    <span>Ubah Password</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" href="{{ route('workspace.logout', $routeParams) }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="oi oi-account-logout"></i>
                    <span>Logout</span>
                </a>
            </div>
            @endif
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
        <form id="logout-form" action="{{ isset($workspace) ? route('workspace.logout', ['workspace' => $workspace->slug]) : route('logout') }}" method="POST" style="display: none;">
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
