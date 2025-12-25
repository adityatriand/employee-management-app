@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title">Manajemen File</h1>
        @if(auth()->user()->level == 1)
        <a href="{{ route('files.create') }}" class="btn btn-success">
            <i class="oi oi-plus"></i> Upload File
        </a>
        @endif
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('files.index') }}" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Pegawai</label>
                    <select class="form-select form-select-sm searchable-select" name="employee_id" id="employee_id">
                        <option value="">Semua File</option>
                        <option value="0" {{ request('standalone') == '1' ? 'selected' : '' }}>File Standalone (Tanpa Pegawai)</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Tipe File</label>
                    <select class="form-select form-select-sm" name="file_type" id="file_type">
                        <option value="">Semua Tipe</option>
                        <option value="document" {{ request('file_type') == 'document' ? 'selected' : '' }}>Dokumen</option>
                        <option value="photo" {{ request('file_type') == 'photo' ? 'selected' : '' }}>Foto</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small">Kategori</label>
                    <input type="text" class="form-control form-control-sm" name="category" id="category" value="{{ request('category') }}" placeholder="Kategori...">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm me-2">
                        <i class="oi oi-filter"></i> Filter
                    </button>
                    <a href="{{ route('files.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="oi oi-reload"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Files Grid -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Daftar File</h5>
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
                        <h6 class="file-name" title="{{ $file->name }}">{{ Str::limit($file->name, 40) }}</h6>
                        <div class="file-meta">
                            <small class="text-muted">
                                <i class="oi oi-file"></i> {{ $file->formatted_size }}
                            </small>
                            @if($file->category)
                            <span class="badge bg-info ms-2">{{ $file->category }}</span>
                            @endif
                        </div>
                        @if($file->employee)
                        <div class="file-employee mt-1">
                            <small class="text-muted">
                                <i class="oi oi-person"></i> {{ $file->employee->name }}
                            </small>
                        </div>
                        @else
                        <div class="file-employee mt-1">
                            <small class="text-muted">
                                <i class="oi oi-briefcase"></i> File Standalone
                            </small>
                        </div>
                        @endif
                        @if($file->description)
                        <p class="file-description mt-2">{{ Str::limit($file->description, 60) }}</p>
                        @endif
                        <div class="file-actions mt-3">
                            <a href="{{ route('files.download', $file->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="oi oi-data-transfer-download"></i> Download
                            </a>
                            <a href="{{ route('files.show', $file->id) }}" class="btn btn-sm btn-outline-info">
                                <i class="oi oi-eye"></i> Detail
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
        
        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $files->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-folder" style="font-size: 4rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Tidak ada file yang ditemukan.</p>
            @if(auth()->user()->level == 1)
            <a href="{{ route('files.create') }}" class="btn btn-success mt-2">
                <i class="oi oi-plus"></i> Upload File Pertama
            </a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection

