@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Riwayat Aktivitas</h1>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('workspace.activity-logs.index', ['workspace' => $workspace->slug]) }}" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Pengguna</label>
                    <select class="form-select form-select-sm" name="user_id" id="user_id">
                        <option value="">Semua Pengguna</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Model</label>
                    <select class="form-select form-select-sm" name="model_type" id="model_type">
                        <option value="">Semua Model</option>
                        @foreach($modelTypes as $type)
                        <option value="{{ $type['value'] }}" {{ request('model_type') == $type['value'] ? 'selected' : '' }}>
                            {{ $type['label'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Aksi</label>
                    <select class="form-select form-select-sm" name="action" id="action">
                        <option value="">Semua Aksi</option>
                        @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ ucfirst($action) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" id="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" id="date_to" value="{{ request('date_to') }}">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary btn-sm me-2">
                    <i class="oi oi-filter"></i> Terapkan Filter
                </button>
                <a href="{{ route('workspace.activity-logs.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="oi oi-reload"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Activity Logs Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Daftar Aktivitas</h5>
    </div>
    <div class="card-body">
        @if($activityLogs->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 10%;">Aksi</th>
                        <th style="width: 20%;">Model</th>
                        <th style="width: 30%;">Deskripsi</th>
                        <th style="width: 15%;">Pengguna</th>
                        <th style="width: 15%;">Waktu</th>
                        <th style="width: 5%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activityLogs as $index => $log)
                    <tr>
                        <td>{{ $activityLogs->firstItem() + $index }}</td>
                        <td>
                            <span class="badge bg-{{ $log->action_color }}">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ class_basename($log->model_type) }}</span>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 300px;" title="{{ $log->description }}">
                                {{ $log->description }}
                            </div>
                        </td>
                        <td>
                            <small>{{ $log->user->name ?? 'System' }}</small>
                        </td>
                        <td>
                            <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('workspace.activity-logs.show', ['workspace' => $workspace->slug, 'activityLog' => $log->id]) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                <i class="oi oi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $activityLogs->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-clock" style="font-size: 4rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Tidak ada aktivitas yang ditemukan.</p>
        </div>
        @endif
    </div>
</div>
@endsection

