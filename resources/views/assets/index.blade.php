@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title">Manajemen Aset</h1>
        @if(auth()->user()->level == 1)
        <a href="{{ route('workspace.assets.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
            <i class="oi oi-plus"></i> Tambah Aset
        </a>
        @endif
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('workspace.assets.index', ['workspace' => $workspace->slug]) }}" id="filterForm">
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
                               placeholder="Cari nama, tag, serial number, brand, model...">
                        @if(request('search'))
                        <button type="button" class="search-clear" onclick="clearSearch()">
                            <i class="oi oi-x"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @if(auth()->user()->level == 1)
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleFilters()">
                            <i class="oi oi-failter"></i> Filter
                            @if($hasFilters)
                            <span class="badge bg-primary ms-2" id="filterCount">{{ $filterCount }}</span>
                            @endif
                        </button>
                        @if($hasFilters)
                        <a href="{{ route('workspace.assets.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-danger ms-2">
                            <i class="oi oi-reload"></i> Reset
                        </a>
                        @endif
                    </div>
                @endif
            </div>

            @if(auth()->user()->level == 1)
                <!-- Filter Panel -->
                <div class="filter-panel" id="filterPanel" style="display: {{ $hasFilters ? 'block' : 'none' }};">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Tipe Aset</label>
                            <select class="form-select form-select-sm" name="asset_type" id="asset_type">
                                <option value="">Semua Tipe</option>
                                @foreach($assetTypes as $type)
                                <option value="{{ $type }}" {{ request('asset_type') == $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Status</label>
                            <select class="form-select form-select-sm" name="status" id="status">
                                <option value="">Semua Status</option>
                                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Tersedia</option>
                                <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Ditetapkan</option>
                                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Perawatan</option>
                                <option value="retired" {{ request('status') == 'retired' ? 'selected' : '' }}>Pensiun</option>
                                <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Hilang</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Ditugaskan ke</label>
                            <select class="form-select form-select-sm searchable-select" name="assigned_to" id="assigned_to">
                                <option value="">Semua</option>
                                <option value="0" {{ request('assigned_to') == '0' ? 'selected' : '' }}>Tidak Ditugaskan</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('assigned_to') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Departemen</label>
                            <input type="text" class="form-control form-control-sm" name="department" id="department" value="{{ request('department') }}" placeholder="Departemen...">
                        </div>
                    </div>
                </div>

                <!-- Active Filters -->
                @if($hasFilters)
                <div class="mt-3">
                    <div class="d-flex flex-wrap gap-2">
                        @if(request('search'))
                        <span class="badge bg-primary">
                            Pencarian: {{ request('search') }}
                            <a href="{{ route('workspace.assets.index', array_merge(['workspace' => $workspace->slug], request()->except('search'), ['page' => 1])) }}" class="text-white ms-2" style="text-decoration: none;">×</a>
                        </span>
                        @endif
                        @if(request('asset_type'))
                        <span class="badge bg-info">
                            Tipe: {{ ucfirst(request('asset_type')) }}
                            <a href="{{ route('workspace.assets.index', array_merge(['workspace' => $workspace->slug], request()->except('asset_type'), ['page' => 1])) }}" class="text-white ms-2" style="text-decoration: none;">×</a>
                        </span>
                        @endif
                        @if(request('status'))
                        <span class="badge bg-warning">
                            Status: {{ ucfirst(request('status')) }}
                            <a href="{{ route('workspace.assets.index', array_merge(['workspace' => $workspace->slug], request()->except('status'), ['page' => 1])) }}" class="text-white ms-2" style="text-decoration: none;">×</a>
                        </span>
                        @endif
                        @if(request('assigned_to'))
                        <span class="badge bg-success">
                            Ditugaskan: {{ request('assigned_to') == '0' ? 'Tidak Ditugaskan' : ($employees->find(request('assigned_to'))->name ?? 'N/A') }}
                            <a href="{{ route('workspace.assets.index', array_merge(['workspace' => $workspace->slug], request()->except('assigned_to'), ['page' => 1])) }}" class="text-white ms-2" style="text-decoration: none;">×</a>
                        </span>
                        @endif
                        @if(request('department'))
                        <span class="badge bg-secondary">
                            Departemen: {{ request('department') }}
                            <a href="{{ route('workspace.assets.index', array_merge(['workspace' => $workspace->slug], request()->except('department'), ['page' => 1])) }}" class="text-white ms-2" style="text-decoration: none;">×</a>
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            @endif
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="card-body">
        @if($assets->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Aset</th>
                        <th>Tag Aset</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Ditugaskan ke</th>
                        <th>Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assets as $asset)
                    <tr>
                        <td>
                            <a href="{{ route('workspace.assets.show', ['workspace' => $workspace->slug, 'asset' => $asset->id]) }}" class="d-inline-block">
                                <img src="{{ $asset->image_url }}"
                                     alt="{{ $asset->name }}"
                                     class="table-thumbnail"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCIgdmlld0JveD0iMCAwIDUwIDUwIj48cmVjdCB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIGZpbGw9IiNmMWY1ZjkiIHN0cm9rZT0iI2UyZThmMCIgc3Ryb2tlLXdpZHRoPSIxIi8+PHJlY3QgeD0iMTAiIHk9IjEwIiB3aWR0aD0iMzAiIGhlaWdodD0iMjQiIGZpbGw9IiNjYmQ1ZTEiIHJ4PSIyIi8+PGNpcmNsZSBjeD0iMjUiIGN5PSIyMiIgcj0iNiIgZmlsbD0iIzk0YTNiOCIvPjxwYXRoIGQ9Ik0xMCAzMCBRMTAgMjUgMjUgMjUgUTQwIDI1IDQwIDMwIEw0MCAzNSBMMTAgMzUgWiIgZmlsbD0iIzk0YTNiOCIvPjwvc3ZnPg==';">
                            </a>
                        </td>
                        <td>
                            <strong>{{ $asset->name }}</strong>
                            @if($asset->brand || $asset->model)
                            <br><small class="text-muted">{{ $asset->brand }} {{ $asset->model }}</small>
                            @endif
                        </td>
                        <td>
                            <code>{{ $asset->asset_tag ?? '-' }}</code>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ ucfirst($asset->asset_type) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $asset->status_color }}">{{ $asset->status_label }}</span>
                        </td>
                        <td>
                            @if($asset->assignedTo)
                            <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $asset->assignedTo->id]) }}" class="text-decoration-none">
                                {{ $asset->assignedTo->name }}
                            </a>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $asset->current_location ?? '-' }}</td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('workspace.assets.show', ['workspace' => $workspace->slug, 'asset' => $asset->id]) }}"
                                   class="btn-action btn-view"
                                   title="Detail">
                                    <i class="oi oi-eye"></i>
                                    <span>Detail</span>
                                </a>
                                @if(auth()->user()->level == 1)
                                <a href="{{ route('workspace.assets.edit', ['workspace' => $workspace->slug, 'asset' => $asset->id]) }}"
                                   class="btn-action btn-edit" title="Edit">
                                    <i class="oi oi-pencil"></i>
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('workspace.assets.destroy', ['workspace' => $workspace->slug, 'asset' => $asset->id]) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <i class="oi oi-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $assets->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-box" style="font-size: 3rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Tidak ada aset ditemukan.</p>
            @if(auth()->user()->level == 1)
            <a href="{{ route('workspace.assets.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
                <i class="oi oi-plus"></i> Tambah Aset Pertama
            </a>
            @endif
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleFilters() {
    const panel = document.getElementById('filterPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function clearSearch() {
    document.getElementById('search').value = '';
    document.getElementById('filterForm').submit();
}
</script>
@endpush
@endsection

