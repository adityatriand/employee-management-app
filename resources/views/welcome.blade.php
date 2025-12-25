<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WorkforceHub by Arphidh - Sistem Manajemen Pegawai</title>

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
                <img src="{{ asset('images/logo.png') }}" alt="WorkforceHub" class="landing-logo-img" onerror="this.style.display='none'">
                <h1 class="landing-title">WorkforceHub</h1>
            </div>
            <p class="landing-subtitle mb-2">
                Sistem Terpusat untuk Manajemen Sumber Daya Organisasi
            </p>
            <p class="landing-description">
                Solusi terpadu untuk mengelola tenaga kerja, administrasi, dan sumber daya organisasi dengan efisien dan terpusat
            </p>
            <div class="landing-actions">
                <a href="{{ route('login') }}" class="btn btn-light btn-lg">
                    <i class="oi oi-account-login"></i> Masuk
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">
                    <i class="oi oi-person"></i> Daftar
                </a>
            </div>
            <div class="landing-attribution">
                <img src="{{ asset('images/logo-arphidh.png') }}" alt="Arphidh" class="landing-attribution-logo" onerror="this.style.display='none';">
                <p class="landing-description">by Arphidh</p>
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
                        <h4>Manajemen Tenaga Kerja</h4>
                        <p class="text-muted">Kelola data tenaga kerja secara terpusat dengan informasi lengkap, termasuk profil, jabatan, dan riwayat aktivitas.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="oi oi-briefcase"></i>
                        </div>
                        <h4>Administrasi Terpusat</h4>
                        <p class="text-muted">Sistem administrasi terintegrasi untuk mengelola struktur organisasi, jabatan, dan sumber daya dengan efisien.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="oi oi-graph"></i>
                        </div>
                        <h4>Sumber Daya Organisasi</h4>
                        <p class="text-muted">Pantau dan kelola seluruh sumber daya organisasi melalui dashboard analitik dan laporan terpusat.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

