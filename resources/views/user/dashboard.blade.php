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
        <h5 class="mb-0">Selamat Datang, {{ auth()->user()->name }}!</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Selamat datang di Sistem Manajemen Pegawai. Anda dapat melihat data pegawai dan jabatan yang tersedia.</p>
        <div class="alert alert-info">
            <i class="oi oi-info"></i> <strong>Informasi:</strong> Sebagai pengguna reguler, Anda memiliki akses untuk melihat data pegawai dan jabatan. Untuk mengelola data, silakan hubungi administrator.
        </div>
        <div class="d-flex gap-2 flex-wrap mt-4">
            <a href="{{ route('employees.index') }}" class="btn btn-primary">
                <i class="oi oi-people"></i> Lihat Data Pegawai
            </a>
            <a href="{{ route('positions.index') }}" class="btn btn-success">
                <i class="oi oi-briefcase"></i> Lihat Data Jabatan
            </a>
        </div>
    </div>
</div>

@if(\App\Models\Employee::count() > 0)
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Daftar Pegawai</h5>
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
                    @foreach(\App\Models\Employee::with('position')->orderBy('created_at', 'desc')->orderBy('id', 'desc')->take(10)->get() as $index => $employee)
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

