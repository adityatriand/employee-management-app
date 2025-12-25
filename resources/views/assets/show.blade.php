@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Detail Aset</h1>
        </div>
        <div>
            <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-4">
                    <img src="{{ $asset->image_url }}" alt="{{ $asset->name }}" class="img-fluid rounded" style="max-height: 300px; width: auto;">
                </div>
                <h4 class="mb-1">{{ $asset->name }}</h4>
                <p class="text-muted mb-3">
                    <code>{{ $asset->asset_tag ?? 'N/A' }}</code>
                </p>
                <div class="mb-3">
                    <span class="badge bg-{{ $asset->status_color }} fs-6">{{ $asset->status_label }}</span>
                </div>
            </div>
        </div>

        @if(auth()->user()->level == 1)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($asset->status == 'available')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                        <i class="oi oi-person"></i> Tugaskan ke Pegawai
                    </button>
                    @elseif($asset->status == 'assigned' && $asset->assigned_to)
                    <form action="{{ route('assets.unassign', $asset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan aset ini?');">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="oi oi-reload"></i> Kembalikan Aset
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-primary">
                        <i class="oi oi-pencil"></i> Edit Aset
                    </a>
                    <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="oi oi-trash"></i> Hapus Aset
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="oi oi-info"></i> Informasi Dasar</h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Aset</div>
                        <div class="info-value">{{ $asset->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tag Aset</div>
                        <div class="info-value"><code>{{ $asset->asset_tag ?? '-' }}</code></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tipe Aset</div>
                        <div class="info-value">
                            <span class="badge bg-secondary">{{ ucfirst($asset->asset_type) }}</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $asset->status_color }}">{{ $asset->status_label }}</span>
                        </div>
                    </div>
                    @if($asset->brand)
                    <div class="info-item">
                        <div class="info-label">Merek</div>
                        <div class="info-value">{{ $asset->brand }}</div>
                    </div>
                    @endif
                    @if($asset->model)
                    <div class="info-item">
                        <div class="info-label">Model</div>
                        <div class="info-value">{{ $asset->model }}</div>
                    </div>
                    @endif
                    @if($asset->serial_number)
                    <div class="info-item">
                        <div class="info-label">Nomor Seri</div>
                        <div class="info-value"><code>{{ $asset->serial_number }}</code></div>
                    </div>
                    @endif
                    @if($asset->description)
                    <div class="info-item full-width">
                        <div class="info-label">Deskripsi</div>
                        <div class="info-value">{{ $asset->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Financial Information -->
        @if($asset->purchase_date || $asset->purchase_price || $asset->current_value)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="oi oi-dollar"></i> Informasi Keuangan</h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    @if($asset->purchase_date)
                    <div class="info-item">
                        <div class="info-label">Tanggal Pembelian</div>
                        <div class="info-value">{{ $asset->purchase_date->format('d F Y') }}</div>
                    </div>
                    @endif
                    @if($asset->purchase_price)
                    <div class="info-item">
                        <div class="info-label">Harga Pembelian</div>
                        <div class="info-value">Rp {{ number_format($asset->purchase_price, 2, ',', '.') }}</div>
                    </div>
                    @endif
                    @if($asset->current_value)
                    <div class="info-item">
                        <div class="info-label">Nilai Saat Ini</div>
                        <div class="info-value">Rp {{ number_format($asset->current_value, 2, ',', '.') }}</div>
                    </div>
                    @endif
                    @if($asset->warranty_expiry)
                    <div class="info-item">
                        <div class="info-label">Berakhir Garansi</div>
                        <div class="info-value">
                            {{ $asset->warranty_expiry->format('d F Y') }}
                            @if($asset->warranty_expiry->isPast())
                            <span class="badge bg-danger ms-2">Kedaluwarsa</span>
                            @elseif($asset->warranty_expiry->diffInDays() <= 30)
                            <span class="badge bg-warning ms-2">Akan Kedaluwarsa</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Location & Assignment -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="oi oi-map-marker"></i> Lokasi & Penugasan</h5>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    @if($asset->current_location)
                    <div class="info-item">
                        <div class="info-label">Lokasi Saat Ini</div>
                        <div class="info-value">{{ $asset->current_location }}</div>
                    </div>
                    @endif
                    @if($asset->department)
                    <div class="info-item">
                        <div class="info-label">Departemen</div>
                        <div class="info-value">{{ $asset->department }}</div>
                    </div>
                    @endif
                    @if($asset->assignedEmployee)
                    <div class="info-item">
                        <div class="info-label">Ditugaskan ke</div>
                        <div class="info-value">
                            <a href="{{ route('employees.show', $asset->assignedEmployee->id) }}" class="text-decoration-none">
                                {{ $asset->assignedEmployee->name }}
                            </a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Penugasan</div>
                        <div class="info-value">{{ $asset->assigned_date ? $asset->assigned_date->format('d F Y') : '-' }}</div>
                    </div>
                    @if($asset->assigner)
                    <div class="info-item">
                        <div class="info-label">Ditugaskan oleh</div>
                        <div class="info-value">{{ $asset->assigner->name }}</div>
                    </div>
                    @endif
                    @else
                    <div class="info-item">
                        <div class="info-label">Status Penugasan</div>
                        <div class="info-value">
                            <span class="badge bg-secondary">Tidak Ditugaskan</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Assignment History -->
        @if($asset->assignments->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="oi oi-clock"></i> Riwayat Penugasan</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($asset->assignments->sortByDesc('assigned_at') as $assignment)
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <a href="{{ route('employees.show', $assignment->employee->id) }}" class="text-decoration-none">
                                            {{ $assignment->employee->name }}
                                        </a>
                                    </h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="oi oi-calendar"></i> Ditugaskan: {{ $assignment->assigned_at->format('d F Y') }}
                                        @if($assignment->returned_at)
                                        <br><i class="oi oi-reload"></i> Dikembalikan: {{ $assignment->returned_at->format('d F Y') }}
                                        @else
                                        <span class="badge bg-success ms-2">Aktif</span>
                                        @endif
                                    </p>
                                    @if($assignment->notes)
                                    <p class="mb-0 small">{{ $assignment->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if($assignment->assigner)
                                    <small class="text-muted">Oleh: {{ $assignment->assigner->name }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Activity Log -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="oi oi-clock"></i> Riwayat Aktivitas
                </h5>
                @if(auth()->user()->level == 1)
                <a href="{{ route('activity-logs.index', ['model_type' => get_class($asset), 'model_id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary">
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
    </div>
</div>

<!-- Assign Modal -->
@if(auth()->user()->level == 1)
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.assign', $asset->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">Tugaskan Aset ke Pegawai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label fw-bold">Pegawai <span class="text-danger">*</span></label>
                        <select class="form-select searchable-select" id="employee_id" name="employee_id" required>
                            <option value="">Pilih Pegawai</option>
                            @foreach(\App\Models\Employee::orderBy('name')->get() as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_date" class="form-label fw-bold">Tanggal Penugasan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="assigned_date" name="assigned_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-bold">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan penugasan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tugaskan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

