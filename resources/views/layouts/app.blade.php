<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href={{ assets('open-iconic/font/css/open-iconic-bootstrap.css') }} rel="stylesheet">
</head>

<body class="h-100">
    <nav class="navbar navbar-expand-sm navbar-dark sticky-top bg-info">
        <a class="navbar-brand" href="#">Manajemen Pegawai</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar"
            aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <nav class="collapse navbar-collapse" id="sidebar">
            <ul class="navbar-nav d-sm-none">
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('home') }}"><i class="oi oi-dashboard"></i>
                        Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('pegawai.index') }}"><i class="oi oi-person"></i> Data
                        Pegawai</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('jabatan.index') }}"><i
                            class="oi oi-sort-descending"></i> Data
                        Jabatan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                            class="oi oi-account-logout"></i> Logout</a>
                </li>
            </ul>
        </nav>
    </nav>
    <div class="container-fluid h-100">
        <div class="row h-100">
            <nav class="col-md-2 col-sm-3 bg-dark h-100 p-0 position-fixed d-none d-sm-block">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item bg-dark">
                        <a class="nav-link text-white" href="{{ route('home') }}"><i class="oi oi-dashboard"></i>
                            Dashboard</a>
                    </li>
                    <li class="list-group-item bg-dark">
                        <a class="nav-link text-white" href="{{ route('pegawai.index') }}"><i class="oi oi-person"></i>
                            Data
                            Pegawai</a>
                    </li>
                    <li class="list-group-item bg-dark">
                        <a class="nav-link text-white" href="{{ route('jabatan.index') }}"><i
                                class="oi oi-sort-descending"></i> Data
                            Jabatan</a>
                    </li>
                    <li class="list-group-item bg-dark">
                        <a class="nav-link text-white" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                                class="oi oi-account-logout"></i> Logout</a>
                    </li>
                </ul>
            </nav>
            <div class="col-md-10 col-sm-9 offset-md-2 offset-sm-3 mb-3">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <section>
                    @yield('content')
                </section>
            </div>
        </div>
    </div>
</body>

</html>
