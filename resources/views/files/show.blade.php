@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Detail File</h1>
        </div>
        <div>
            <a href="{{ route('files.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="oi {{ $file->icon }}"></i> Informasi File
                </h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama File</div>
                        <div class="info-value">{{ $file->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tipe File</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $file->file_type == 'photo' ? 'success' : 'primary' }}">
                                {{ $file->file_type == 'photo' ? 'Foto' : 'Dokumen' }}
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ukuran</div>
                        <div class="info-value">{{ $file->formatted_size }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">MIME Type</div>
                        <div class="info-value"><code>{{ $file->mime_type }}</code></div>
                    </div>
                    @if($file->category)
                    <div class="info-item">
                        <div class="info-label">Kategori</div>
                        <div class="info-value">
                            <span class="badge bg-info">{{ $file->category }}</span>
                        </div>
                    </div>
                    @endif
                    @if($file->employee)
                    <div class="info-item">
                        <div class="info-label">Pegawai</div>
                        <div class="info-value">
                            <a href="{{ route('employees.show', $file->employee->id) }}" class="text-decoration-none">
                                {{ $file->employee->name }}
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="info-item">
                        <div class="info-label">Tipe</div>
                        <div class="info-value">
                            <span class="badge bg-secondary">File Standalone</span>
                        </div>
                    </div>
                    @endif
                    <div class="info-item">
                        <div class="info-label">Diupload Oleh</div>
                        <div class="info-value">{{ $file->uploader->name ?? 'System' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Upload</div>
                        <div class="info-value">{{ $file->created_at->format('d F Y, H:i') }}</div>
                    </div>
                    @if($file->description)
                    <div class="info-item full-width">
                        <div class="info-label">Deskripsi</div>
                        <div class="info-value">{{ $file->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('files.download', $file->id) }}" class="btn btn-primary">
                        <i class="oi oi-data-transfer-download"></i> Download File
                    </a>
                    @if($file->employee)
                    <a href="{{ route('employees.show', $file->employee->id) }}" class="btn btn-outline-info">
                        <i class="oi oi-person"></i> Lihat Pegawai
                    </a>
                    @endif
                    <a href="{{ route('files.index') }}" class="btn btn-outline-secondary">
                        <i class="oi oi-list"></i> Kembali ke Daftar
                    </a>
                    @if(auth()->user()->level == 1)
                    <form action="{{ route('files.destroy', $file->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="oi oi-trash"></i> Hapus File
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log Section -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="oi oi-clock"></i> Riwayat Aktivitas
        </h5>
        @if(auth()->user()->level == 1)
        <a href="{{ route('activity-logs.index', ['model_type' => get_class($file), 'model_id' => $file->id]) }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="oi oi-chevron-right"></i>
        </a>
        @endif
    </div>
    <div class="card-body">
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

