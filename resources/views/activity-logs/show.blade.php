@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('workspace.activity-logs.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
            <h1 class="page-title mb-0">Detail Aktivitas</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Activity Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-info"></i> Informasi Aktivitas
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Aksi</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $activityLog->action_color }} fs-6">
                                {{ $activityLog->action_label }}
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Model</div>
                        <div class="info-value">
                            <span class="badge bg-info fs-6">{{ class_basename($activityLog->model_type) }}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pengguna</div>
                        <div class="info-value">{{ $activityLog->user->name ?? 'System' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Waktu</div>
                        <div class="info-value">{{ $activityLog->created_at->format('d F Y, H:i:s') }}</div>
                    </div>
                    <div class="info-item full-width">
                        <div class="info-label">Deskripsi</div>
                        <div class="info-value">{{ $activityLog->description }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Changes Details -->
        @if($activityLog->action == 'updated' && !empty($activityLog->old_values))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-pencil"></i> Detail Perubahan
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Field</th>
                                <th style="width: 35%;">Nilai Lama</th>
                                <th style="width: 35%;">Nilai Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLog->old_values as $key => $oldValue)
                            <tr>
                                <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong></td>
                                <td>
                                    <span class="badge bg-danger">{{ $oldValue ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">{{ $activityLog->new_values[$key] ?? '-' }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($activityLog->action == 'created' && !empty($activityLog->new_values))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-plus"></i> Data yang Dibuat
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    @foreach($activityLog->new_values as $key => $value)
                    <div class="info-item">
                        <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                        <div class="info-value">{{ $value ?? '-' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($activityLog->action == 'deleted' && !empty($activityLog->old_values))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-trash"></i> Data yang Dihapus
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    @foreach($activityLog->old_values as $key => $value)
                    <div class="info-item">
                        <div class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                        <div class="info-value">{{ $value ?? '-' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('workspace.activity-logs.index', ['workspace' => $workspace->slug]) }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-list"></i> Kembali ke Daftar
                    </a>
                    @if($activityLog->model_type == 'App\Models\Employee')
                    <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $activityLog->model_id]) }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-person"></i> Lihat Pegawai
                    </a>
                    @endif
                    @if($activityLog->model_type == 'App\Models\Position')
                    <a href="{{ route('workspace.positions.show', ['workspace' => $workspace->slug, 'position' => $activityLog->model_id]) }}" class="list-group-item list-group-item-action">
                        <i class="oi oi-briefcase"></i> Lihat Jabatan
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

