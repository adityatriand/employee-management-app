@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard Admin</h1>
</div>

<div class="row mb-4">
    <div class="col-md-3">
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
    <div class="col-md-3">
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
    <div class="col-md-3">
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
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-danger bg-opacity-10 rounded p-3">
                            <i class="oi oi-person text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Pegawai Perempuan</h6>
                        <h3 class="mb-0">{{ \App\Models\Employee::where('gender', 'P')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Selamat Datang, Administrator!</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Anda memiliki akses penuh untuk mengelola data pegawai dan jabatan dalam sistem.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('employees.index') }}" class="btn btn-primary">
                        <i class="oi oi-people"></i> Kelola Pegawai
                    </a>
                    <a href="{{ route('positions.index') }}" class="btn btn-success">
                        <i class="oi oi-briefcase"></i> Kelola Jabatan
                    </a>
                    <a href="{{ route('employees.create') }}" class="btn btn-outline-primary">
                        <i class="oi oi-plus"></i> Tambah Pegawai
                    </a>
                    <a href="{{ route('positions.create') }}" class="btn btn-outline-success">
                        <i class="oi oi-plus"></i> Tambah Jabatan
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('employees.index') }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-list"></i> Lihat Semua Pegawai
                    </a>
                    <a href="{{ route('positions.index') }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-list"></i> Lihat Semua Jabatan
                    </a>
                    <a href="{{ route('employees.create') }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-plus"></i> Tambah Pegawai Baru
                    </a>
                    <a href="{{ route('positions.create') }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-plus"></i> Tambah Jabatan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(\App\Models\Employee::count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Pegawai Terbaru</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\Employee::with('position')->latest()->take(5)->get() as $index => $employee)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <img src="{{ $employee->photo_url }}" 
                                 alt="{{ $employee->name }}" 
                                 class="employee-photo">
                        </td>
                        <td><strong>{{ $employee->name }}</strong></td>
                        <td>
                            <span class="badge bg-info">{{ $employee->position->name ?? '-' }}</span>
                        </td>
                        <td>
                            @if($employee->gender == 'L')
                                <span class="badge bg-primary">Laki-Laki</span>
                            @else
                                <span class="badge bg-danger">Perempuan</span>
                            @endif
                        </td>
                        <td>{{ $employee->birth_date->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3 text-center">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-primary">
                Lihat Semua Pegawai <i class="oi oi-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
@endif
@endsection
