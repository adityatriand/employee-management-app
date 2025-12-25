@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded p-3">
                            <i class="oi oi-people text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Pegawai</h6>
                        <h3 class="mb-0">{{ $stats['total_employees'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded p-3">
                            <i class="oi oi-briefcase text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Total Jabatan</h6>
                        <h3 class="mb-0">{{ $stats['total_positions'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 rounded p-3">
                            <i class="oi oi-graph text-info" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Rata-rata Usia</h6>
                        <h3 class="mb-0">{{ $stats['average_age'] }} <small class="text-muted">tahun</small></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Gender Distribution Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-pie-chart"></i> Distribusi Jenis Kelamin
                </h5>
            </div>
            <div class="card-body">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Position Distribution Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-briefcase"></i> Distribusi Jabatan
                </h5>
            </div>
            <div class="card-body">
                <canvas id="positionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="card mb-4">
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

<!-- Recent Employees -->
@if($recentEmployees->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Pegawai Terbaru</h5>
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="oi oi-chevron-right"></i>
        </a>
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
                    @foreach($recentEmployees as $index => $employee)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ route('employees.show', $employee->id) }}">
                                <img src="{{ $employee->photo_url }}" 
                                     alt="{{ $employee->name }}" 
                                     class="employee-photo">
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('employees.show', $employee->id) }}" class="employee-name-link">
                                <strong>{{ $employee->name }}</strong>
                            </a>
                        </td>
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
    </div>
</div>
@endif

@push('scripts')
<script>
    // Chart colors
    const primaryColor = '#4988C4';
    const successColor = '#10b981';
    const dangerColor = '#ef4444';
    const infoColor = '#06b6d4';
    const warningColor = '#f59e0b';
    
    // Gender Distribution Chart (Pie)
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    new Chart(genderCtx, {
        type: 'pie',
        data: {
            labels: @json($chartData['gender']['labels']),
            datasets: [{
                data: @json($chartData['gender']['data']),
                backgroundColor: [primaryColor, dangerColor],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Position Distribution Chart (Doughnut)
    const positionCtx = document.getElementById('positionChart').getContext('2d');
    new Chart(positionCtx, {
        type: 'doughnut',
        data: {
            labels: @json($chartData['position']['labels']),
            datasets: [{
                data: @json($chartData['position']['data']),
                backgroundColor: [
                    primaryColor,
                    successColor,
                    infoColor,
                    warningColor,
                    dangerColor,
                    '#8b5cf6',
                    '#ec4899',
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
</script>
@endpush
@endsection

