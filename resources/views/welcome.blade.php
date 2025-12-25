<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRMS - Sistem Manajemen Pegawai</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('open-iconic/font/css/open-iconic-bootstrap.css') }}" rel="stylesheet">
</head>

<body>
    <div class="landing-hero">
        <div class="landing-content">
            <div class="landing-logo">
                <img src="{{ asset('images/logo.png') }}" alt="HRMS Logo" class="landing-logo-img" onerror="this.style.display='none'">
            </div>
            <h1 class="landing-title">Sistem Manajemen Pegawai</h1>
            <p class="landing-subtitle">
                Kelola data pegawai dan jabatan dengan mudah dan efisien
            </p>
            <div class="landing-actions">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">
                    <i class="oi oi-account-login"></i> Masuk
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                    <i class="oi oi-person"></i> Daftar
                </a>
            </div>
        </div>
    </div>

    <div class="landing-features">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="oi oi-people"></i>
                        </div>
                        <h4>Kelola Pegawai</h4>
                        <p class="text-muted">Kelola data pegawai dengan lengkap termasuk foto, informasi pribadi, dan jabatan.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="oi oi-briefcase"></i>
                        </div>
                        <h4>Manajemen Jabatan</h4>
                        <p class="text-muted">Atur struktur jabatan dalam organisasi dengan mudah dan terorganisir.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="oi oi-shield"></i>
                        </div>
                        <h4>Aman & Terpercaya</h4>
                        <p class="text-muted">Sistem keamanan yang handal untuk melindungi data penting perusahaan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

