@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title">Data Pegawai</h1>
        <div class="d-flex gap-2">
            <!-- Export Buttons -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="oi oi-data-transfer-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('workspace.employees.export.pdf', array_merge(['workspace' => $workspace->slug], request()->query())) }}">
                            <i class="oi oi-file"></i> Export ke PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('workspace.employees.export.excel', array_merge(['workspace' => $workspace->slug], request()->query())) }}">
                            <i class="oi oi-spreadsheet"></i> Export ke Excel
                        </a>
                    </li>
                </ul>
            </div>
            @if(auth()->user()->level == 1)
            <a href="{{ route('workspace.employees.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
                <i class="oi oi-plus"></i> Tambah Pegawai
            </a>
            @endif
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('workspace.employees.index', ['workspace' => $workspace->slug]) }}" id="filterForm">
            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="oi oi-magnifying-glass"></i>
                        <input type="text" 
                               class="form-control search-input" 
                               name="search" 
                               id="search"
                               value="{{ request('search') }}"
                               placeholder="Cari nama pegawai...">
                        @if(request('search'))
                        <button type="button" class="search-clear" onclick="clearSearch()">
                            <i class="oi oi-x"></i>
                        </button>
                        @endif
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleFilters()">
                        <i class="oi oi-filter"></i> Filter
                        @if($hasFilters)
                        <span class="badge bg-primary ms-2" id="filterCount">{{ $filterCount }}</span>
                        @endif
                    </button>
                    @if($hasFilters)
                    <a href="{{ route('workspace.employees.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-danger ms-2">
                        <i class="oi oi-reload"></i> Reset
                    </a>
                    @endif
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel" id="filterPanel" style="display: {{ $hasFilters ? 'block' : 'none' }};">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Jabatan</label>
                        <select class="form-select form-select-sm searchable-select" name="position_id" id="position_id">
                            <option value="">Semua Jabatan</option>
                            @foreach($positions as $position)
                            <option value="{{ $position->id }}" {{ request('position_id') == $position->id ? 'selected' : '' }}>
                                {{ $position->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Jenis Kelamin</label>
                        <select class="form-select form-select-sm" name="gender" id="gender">
                            <option value="">Semua</option>
                            <option value="L" {{ request('gender') == 'L' ? 'selected' : '' }}>Laki-Laki</option>
                            <option value="P" {{ request('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tanggal Lahir Dari</label>
                        <input type="date" 
                               class="form-control form-control-sm" 
                               name="birth_date_from" 
                               id="birth_date_from"
                               value="{{ request('birth_date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tanggal Lahir Sampai</label>
                        <input type="date" 
                               class="form-control form-control-sm" 
                               name="birth_date_to" 
                               id="birth_date_to"
                               value="{{ request('birth_date_to') }}">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Ditambahkan Dari</label>
                        <input type="date" 
                               class="form-control form-control-sm" 
                               name="created_from" 
                               id="created_from"
                               value="{{ request('created_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Ditambahkan Sampai</label>
                        <input type="date" 
                               class="form-control form-control-sm" 
                               name="created_to" 
                               id="created_to"
                               value="{{ request('created_to') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm me-2">
                            <i class="oi oi-magnifying-glass"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('workspace.employees.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="oi oi-reload"></i> Reset
                        </a>
                    </div>
                </div>
            </div>

            <!-- Active Filters Badges -->
            @php
                $hasFilters = request('search') || request('position_id') || request('gender') || 
                              request('birth_date_from') || request('birth_date_to') || 
                              request('created_from') || request('created_to');
                $filterCount = 0;
                if (request('search')) $filterCount++;
                if (request('position_id')) $filterCount++;
                if (request('gender')) $filterCount++;
                if (request('birth_date_from') || request('birth_date_to')) $filterCount++;
                if (request('created_from') || request('created_to')) $filterCount++;
            @endphp
            @if($hasFilters)
            <div class="active-filters mt-3">
                <small class="text-muted me-2">Filter Aktif:</small>
                @if(request('search'))
                @php
                    $query = request()->query();
                    unset($query['search']);
                    $removeSearchUrl = route('workspace.employees.index', array_merge(['workspace' => $workspace->slug], $query));
                @endphp
                <span class="badge bg-primary filter-badge">
                    Pencarian: "{{ request('search') }}"
                    <a href="{{ $removeSearchUrl }}" class="text-white ms-2" style="text-decoration: none;">
                        <i class="oi oi-x"></i>
                    </a>
                </span>
                @endif
                @if(request('position_id'))
                @php
                    $query = request()->query();
                    unset($query['position_id']);
                    $removePositionUrl = route('workspace.employees.index', array_merge(['workspace' => $workspace->slug], $query));
                    $positionName = $positions->firstWhere('id', request('position_id'))->name ?? '';
                @endphp
                <span class="badge bg-primary filter-badge">
                    Jabatan: {{ $positionName }}
                    <a href="{{ $removePositionUrl }}" class="text-white ms-2" style="text-decoration: none;">
                        <i class="oi oi-x"></i>
                    </a>
                </span>
                @endif
                @if(request('gender'))
                @php
                    $query = request()->query();
                    unset($query['gender']);
                    $removeGenderUrl = route('workspace.employees.index', array_merge(['workspace' => $workspace->slug], $query));
                @endphp
                <span class="badge bg-primary filter-badge">
                    Gender: {{ request('gender') == 'L' ? 'Laki-Laki' : 'Perempuan' }}
                    <a href="{{ $removeGenderUrl }}" class="text-white ms-2" style="text-decoration: none;">
                        <i class="oi oi-x"></i>
                    </a>
                </span>
                @endif
                @if(request('birth_date_from') || request('birth_date_to'))
                @php
                    $query = request()->query();
                    unset($query['birth_date_from']);
                    unset($query['birth_date_to']);
                    $removeBirthDateUrl = route('workspace.employees.index', array_merge(['workspace' => $workspace->slug], $query));
                @endphp
                <span class="badge bg-primary filter-badge">
                    Tanggal Lahir: {{ request('birth_date_from') ?? 'Awal' }} - {{ request('birth_date_to') ?? 'Akhir' }}
                    <a href="{{ $removeBirthDateUrl }}" class="text-white ms-2" style="text-decoration: none;">
                        <i class="oi oi-x"></i>
                    </a>
                </span>
                @endif
                @if(request('created_from') || request('created_to'))
                @php
                    $query = request()->query();
                    unset($query['created_from']);
                    unset($query['created_to']);
                    $removeCreatedUrl = route('workspace.employees.index', array_merge(['workspace' => $workspace->slug], $query));
                @endphp
                <span class="badge bg-primary filter-badge">
                    Ditambahkan: {{ request('created_from') ?? 'Awal' }} - {{ request('created_to') ?? 'Akhir' }}
                    <a href="{{ $removeCreatedUrl }}" class="text-white ms-2" style="text-decoration: none;">
                        <i class="oi oi-x"></i>
                    </a>
                </span>
                @endif
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($employees->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>Jabatan</th>
                        <th style="width: 180px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                    <tr>
                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" class="d-inline-block">
                                <img src="{{ $employee->photo_url }}" 
                                     alt="{{ $employee->name }}" 
                                     class="employee-photo">
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" class="employee-name-link">
                                {{ $employee->name }}
                            </a>
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
                            <span class="badge bg-info">{{ $employee->position->name ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" 
                                   class="btn-action btn-view" 
                                   title="Lihat Detail">
                                    <i class="oi oi-eye"></i>
                                    <span>Lihat</span>
                                </a>
                                @if(auth()->user()->level == 1)
                                <a href="{{ route('workspace.employees.edit', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" 
                                   class="btn-action btn-edit" title="Edit">
                                    <i class="oi oi-pencil"></i>
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('workspace.employees.destroy', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <i class="oi oi-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Menampilkan {{ $employees->firstItem() }} sampai {{ $employees->lastItem() }} dari {{ $employees->total() }} data
            </div>
            <div>
                {{ $employees->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-people" style="font-size: 4rem; color: #cbd5e1;"></i>
            @if($hasFilters)
            <p class="mt-3 text-muted">Tidak ada data pegawai yang sesuai dengan filter</p>
            <a href="{{ route('workspace.employees.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-primary">
                <i class="oi oi-reload"></i> Reset Filter
            </a>
            @else
            <p class="mt-3 text-muted">Belum ada data pegawai</p>
            @if(auth()->user()->level == 1)
            <a href="{{ route('workspace.employees.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
                <i class="oi oi-plus"></i> Tambah Pegawai Pertama
            </a>
            @endif
            @endif
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleFilters() {
    const panel = document.getElementById('filterPanel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

function clearSearch() {
    document.getElementById('search').value = '';
    document.getElementById('filterForm').submit();
}

// Auto-submit on Enter in search
document.getElementById('search')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('filterForm').submit();
    }
});

// Show filter panel if there are active filters on page load
document.addEventListener('DOMContentLoaded', function() {
    const hasFilters = {{ $hasFilters ? 'true' : 'false' }};
    if (hasFilters) {
        document.getElementById('filterPanel').style.display = 'block';
    }
});
</script>
@endpush
@endsection

