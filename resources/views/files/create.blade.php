@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Upload File</h1>
        </div>
        <div>
             <a href="{{ route('workspace.files.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Upload File</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('workspace.files.store', ['workspace' => $workspace->slug]) }}" method="POST" enctype="multipart/form-data" id="fileUploadForm">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-bold">File <span class="text-danger">*</span></label>
                        <div class="file-upload-box" id="fileUploadBox">
                            <input type="file"
                                   name="file"
                                   id="file"
                                   class="file-input"
                                   accept="*/*"
                                   required>
                            <div class="file-upload-content">
                                <i class="oi oi-cloud-upload"></i>
                                <div class="file-upload-text">Klik atau drag file ke sini</div>
                                <div class="file-upload-hint">Maksimal 10MB</div>
                            </div>
                            <div class="file-preview" id="filePreview" style="display: none;">
                                <i class="oi oi-file"></i>
                                <div class="file-preview-name" id="filePreviewName"></div>
                                <div class="file-preview-size" id="filePreviewSize"></div>
                                <button type="button" class="file-remove" onclick="removeFile()">
                                    <i class="oi oi-x"></i>
                                </button>
                            </div>
                        </div>
                        @error('file')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nama File</label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Nama file (opsional, akan menggunakan nama asli jika kosong)">
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="file_type" class="form-label fw-bold">Tipe File <span class="text-danger">*</span></label>
                            <select class="form-select @error('file_type') is-invalid @enderror" id="file_type" name="file_type" required>
                                <option value="">Pilih Tipe</option>
                                <option value="document" {{ old('file_type') == 'document' ? 'selected' : '' }}>Dokumen</option>
                                <option value="photo" {{ old('file_type') == 'photo' ? 'selected' : '' }}>Foto</option>
                            </select>
                            @error('file_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-bold">Kategori</label>
                            <input type="text"
                                   class="form-control @error('category') is-invalid @enderror"
                                   id="category"
                                   name="category"
                                   value="{{ old('category') }}"
                                   placeholder="Contoh: Kontrak, Sertifikat, KTP">
                            @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="employee_id" class="form-label fw-bold">Pegawai (Opsional)</label>
                        <select class="form-select searchable-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id">
                            <option value="">File Standalone (Tanpa Pegawai)</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $selectedEmployee) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Kosongkan jika file tidak terkait dengan pegawai tertentu</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Deskripsi file (opsional)">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="oi oi-cloud-upload"></i> Upload File
                        </button>
                        <a href="{{ route('workspace.files.index', ['workspace' => $workspace->slug]) }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informasi</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="oi oi-info"></i> <strong>Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>File maksimal 10MB</li>
                        <li>Semua format file didukung</li>
                        <li>File akan disimpan di MinIO</li>
                        <li>File dapat dikaitkan dengan pegawai atau standalone</li>
                        <li>Foto pegawai akan otomatis menggantikan foto lama</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('filePreview');
    const filePreviewName = document.getElementById('filePreviewName');
    const filePreviewSize = document.getElementById('filePreviewSize');
    const fileUploadBox = document.getElementById('fileUploadBox');
    const fileUploadContent = fileUploadBox.querySelector('.file-upload-content');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            filePreviewName.textContent = file.name;
            filePreviewSize.textContent = formatFileSize(file.size);
            fileUploadContent.style.display = 'none';
            filePreview.style.display = 'flex';
        }
    });

    fileUploadBox.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadBox.classList.add('dragover');
    });

    fileUploadBox.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadBox.classList.remove('dragover');
    });

    fileUploadBox.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadBox.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

    function removeFile() {
        fileInput.value = '';
        filePreview.style.display = 'none';
        fileUploadContent.style.display = 'block';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
</script>
@endpush
@endsection

