@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Detail Pegawai</h1>
        </div>
        <div>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column - Photo and Basic Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="profile-photo-container mb-4">
                    <img src="{{ $employee->photo_url }}"
                         alt="{{ $employee->name }}"
                         class="profile-photo">
                </div>
                <h3 class="mb-2">{{ $employee->name }}</h3>
                <p class="text-muted mb-3">
                    <span class="badge bg-info fs-6">{{ $employee->position->name ?? '-' }}</span>
                </p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="oi oi-person"></i>
                        <div>
                            <div class="stat-label">Jenis Kelamin</div>
                            <div class="stat-value">
                                @if($employee->gender == 'L')
                                    <span class="badge bg-primary">Laki-Laki</span>
                                @else
                                    <span class="badge bg-danger">Perempuan</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="oi oi-calendar"></i>
                        <div>
                            <div class="stat-label">Tanggal Lahir</div>
                            <div class="stat-value">{{ $employee->birth_date->format('d F Y') }}</div>
                            <div class="stat-subvalue">({{ $employee->birth_date->age }} tahun)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Detailed Information -->
    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-person"></i> Informasi Pribadi
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $employee->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value">
                            @if($employee->gender == 'L')
                                <span class="badge bg-primary">Laki-Laki</span>
                            @else
                                <span class="badge bg-danger">Perempuan</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Lahir</div>
                        <div class="info-value">{{ $employee->birth_date->format('d F Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Usia</div>
                        <div class="info-value">{{ $employee->birth_date->age }} tahun</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Position Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-briefcase"></i> Informasi Jabatan
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Jabatan</div>
                        <div class="info-value">
                            <span class="badge bg-info fs-6">{{ $employee->position->name ?? '-' }}</span>
                        </div>
                    </div>
                    @if($employee->position && $employee->position->description)
                    <div class="info-item full-width">
                        <div class="info-label">Deskripsi Jabatan</div>
                        <div class="info-value">{{ $employee->position->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-document"></i> Keterangan
                </h5>
            </div>
            <div class="card-body">
                <div class="info-item full-width">
                    <div class="info-value">
                        <p class="mb-0">{{ $employee->description }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi oi-info"></i> Informasi Sistem
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Ditambahkan</div>
                        <div class="info-value">{{ $employee->created_at->format('d F Y, H:i') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Terakhir Diupdate</div>
                        <div class="info-value">{{ $employee->updated_at->format('d F Y, H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->level == 1)
<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">
                <i class="oi oi-pencil"></i> Edit Pegawai
            </a>
            <form action="{{ route('employees.destroy', $employee->id) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="oi oi-trash"></i> Hapus Pegawai
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Files Section -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="oi oi-folder"></i> Dokumen & File
        </h5>
        @if(auth()->user()->level == 1)
        <a href="{{ route('files.create', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-success">
            <i class="oi oi-plus"></i> Upload File
        </a>
        @endif
    </div>
    <div class="card-body">
        @if($files->count() > 0)
        <div class="row g-3">
            @foreach($files as $file)
            <div class="col-md-4">
                <div class="file-card">
                    <div class="file-icon">
                        <i class="oi {{ $file->icon }}"></i>
                    </div>
                    <div class="file-info">
                        <h6 class="file-name" title="{{ $file->name }}">{{ Str::limit($file->name, 30) }}</h6>
                        <div class="file-meta">
                            <small class="text-muted">
                                <i class="oi oi-file"></i> {{ $file->formatted_size }}
                            </small>
                            @if($file->category)
                            <span class="badge bg-info ms-2">{{ $file->category }}</span>
                            @endif
                        </div>
                        @if($file->description)
                        <p class="file-description">{{ Str::limit($file->description, 50) }}</p>
                        @endif
                        <div class="file-actions mt-2">
                            <a href="{{ route('files.download', $file->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="oi oi-data-transfer-download"></i> Download
                            </a>
                            @if(auth()->user()->level == 1)
                            <form action="{{ route('files.destroy', $file->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="oi oi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4">
            <i class="oi oi-folder" style="font-size: 3rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Belum ada file yang diupload.</p>
            @if(auth()->user()->level == 1)
            <a href="{{ route('files.create', ['employee_id' => $employee->id]) }}" class="btn btn-success mt-2">
                <i class="oi oi-plus"></i> Upload File Pertama
            </a>
            @endif
        </div>
        @endif
    </div>
</div>

<!-- Activity Log Section -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="oi oi-clock"></i> Riwayat Aktivitas
        </h5>
        @if(auth()->user()->level == 1)
        <a href="{{ route('activity-logs.index', ['model_type' => get_class($employee), 'model_id' => $employee->id]) }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="oi oi-chevron-right"></i>
        </a>
        @endif
    </div>
    <div class="card-body">
        @php
            $activityLogs = \App\Models\ActivityLog::where('model_type', get_class($employee))
                ->where('model_id', $employee->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        @endphp

        @if($activityLogs->count() > 0)
        <div class="activity-log-list">
            @foreach($activityLogs as $log)
            <div class="activity-log-item">
                <div class="d-flex align-items-start">
                    <div class="activity-log-icon">
                        <span class="badge bg-{{ $log->action_color }}">
                            <i class="oi oi-{{ $log->action == 'created' ? 'plus' : ($log->action == 'updated' ? 'pencil' : ($log->action == 'deleted' ? 'trash' : 'reload')) }}"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="activity-log-description">
                            <strong>{{ $log->description }}</strong>
                        </div>
                        <div class="activity-log-meta">
                            <span class="text-muted">
                                <i class="oi oi-person"></i> {{ $log->user->name ?? 'System' }}
                            </span>
                            <span class="text-muted ms-3">
                                <i class="oi oi-clock"></i> {{ $log->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @if($log->action == 'updated' && !empty($log->old_values))
                        <div class="activity-log-changes mt-2">
                            <small class="text-muted">Perubahan:</small>
                            <ul class="list-unstyled mb-0 mt-1">
                                @foreach($log->old_values as $key => $oldValue)
                                <li>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                    <span class="text-danger">{{ $oldValue ?? '-' }}</span>
                                    <i class="oi oi-arrow-right"></i>
                                    <span class="text-success">{{ $log->new_values[$key] ?? '-' }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @if(!$loop->last)
            <hr class="my-3">
            @endif
            @endforeach
        </div>
        @else
        <div class="text-center py-4">
            <i class="oi oi-clock" style="font-size: 3rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Belum ada aktivitas yang tercatat.</p>
        </div>
        @endif
    </div>
</div>
@endsection

