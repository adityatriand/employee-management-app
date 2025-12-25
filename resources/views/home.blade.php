@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <i class="oi oi-people text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Pegawai</h6>
                        <h3 class="mb-0">{{ \App\Models\Employee::count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded p-3">
                            <i class="oi oi-briefcase text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Jabatan</h6>
                        <h3 class="mb-0">{{ \App\Models\Position::count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 rounded p-3">
                            <i class="oi oi-person text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Pegawai Laki-Laki</h6>
                        <h3 class="mb-0">{{ \App\Models\Employee::where('gender', 'L')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Selamat Datang</h5>
    </div>
    <div class="card-body">
        <h3>Selamat Datang di Aplikasi Manajemen Pegawai</h3>
        <p class="text-muted">Anda login sebagai Administrator</p>
        <div class="mt-4">
            <a href="{{ route('workspace.employees.index', ['workspace' => $workspace->slug]) }}" class="btn btn-primary me-2">
                <i class="oi oi-people"></i> Kelola Pegawai
            </a>
            <a href="{{ route('workspace.positions.index', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
                <i class="oi oi-briefcase"></i> Kelola Jabatan
            </a>
        </div>
    </div>
</div>
@endsection
