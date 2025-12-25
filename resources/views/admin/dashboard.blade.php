@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard Admin</h1>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
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
                        @if($stats['employees_this_month'] > 0)
                        <small class="text-success">
                            <i class="oi oi-arrow-top"></i> +{{ $stats['employees_this_month'] }} bulan ini
                        </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
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
    <div class="col-md-3">
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
    <div class="col-md-3">
        <div class="card border-0 shadow-sm stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded p-3">
                            <i class="oi oi-arrow-circle-top text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">Pertumbuhan</h6>
                        <h3 class="mb-0">
                            @if($stats['trend'] > 0)
                                <span class="text-success">+{{ $stats['trend'] }}%</span>
                            @elseif($stats['trend'] < 0)
                                <span class="text-danger">{{ $stats['trend'] }}%</span>
                            @else
                                <span class="text-muted">0%</span>
                            @endif
                        </h3>
                        <small class="text-muted">vs bulan lalu</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Employee Growth Chart -->
    <div class="col-md-8">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-graph"></i> Pertumbuhan Pegawai (12 Bulan Terakhir)
                </h5>
            </div>
            <div class="card-body">
                <div class="growth-chart-container">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gender Distribution Chart -->
    <div class="col-md-4">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-pie-chart"></i> Distribusi Jenis Kelamin
                </h5>
            </div>
            <div class="card-body chart-container">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Charts Row -->
<div class="row mb-4">
    <!-- Position Distribution Chart -->
    <div class="col-md-6">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-briefcase"></i> Distribusi Jabatan
                </h5>
            </div>
            <div class="card-body chart-container">
                <canvas id="positionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Age Distribution Chart -->
    <div class="col-md-6">
        <div class="card dashboard-chart-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-bar-chart"></i> Distribusi Usia
                </h5>
            </div>
            <div class="card-body chart-container">
                <canvas id="ageChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Welcome and Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
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
                        <th>Ditambahkan</th>
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
                        <td>
                            <small class="text-muted">{{ $employee->created_at->diffForHumans() }}</small>
                        </td>
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

    // Growth Chart (Line)
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: @json($chartData['growth']['labels']),
            datasets: [{
                label: 'Pegawai Ditambahkan',
                data: @json($chartData['growth']['data']),
                borderColor: primaryColor,
                backgroundColor: primaryColor + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                            size: 11
                        }
                    }
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });

    // Age Distribution Chart (Bar)
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: @json($chartData['age']['labels']),
            datasets: [{
                label: 'Jumlah Pegawai',
                data: @json($chartData['age']['data']),
                backgroundColor: infoColor + '80',
                borderColor: infoColor,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
