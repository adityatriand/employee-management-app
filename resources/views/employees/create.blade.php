@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tambah Pegawai</h1>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Form Tambah Pegawai</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeForm">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Foto <span class="text-danger">*</span></label>
                        <div class="file-upload-box @error('photo') is-invalid @enderror" id="fileUploadBox">
                            <input type="file"
                                   class="file-input"
                                   id="photo"
                                   name="photo"
                                   accept="image/*"
                                   required>
                            <div class="file-upload-content">
                                <i class="oi oi-cloud-upload"></i>
                                <p class="file-upload-text">Klik atau seret foto ke sini</p>
                                <p class="file-upload-hint">Format: JPG, PNG, GIF. Maksimal 2MB</p>
                            </div>
                            <div class="file-preview" id="filePreview" style="display: none;">
                                <img id="previewImage" src="" alt="Preview">
                                <button type="button" class="file-remove" id="fileRemove">
                                    <i class="oi oi-x"></i>
                                </button>
                                <p class="file-name" id="fileName"></p>
                            </div>
                        </div>
                        @error('photo')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">Nama Pegawai <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Masukkan nama pegawai"
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="jk_l" value="L"
                                       {{ old('gender') == 'L' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="jk_l">Laki-Laki</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="jk_p" value="P"
                                       {{ old('gender') == 'P' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="jk_p">Perempuan</label>
                            </div>
                        </div>
                        @error('gender')
                        <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="birth_date" class="form-label fw-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date"
                               class="form-control @error('birth_date') is-invalid @enderror"
                               id="birth_date"
                               name="birth_date"
                               value="{{ old('birth_date') }}"
                               required>
                        @error('birth_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="position_id" class="form-label fw-bold">Jabatan <span class="text-danger">*</span></label>
                        <select class="form-select searchable-select @error('position_id') is-invalid @enderror"
                                id="position_id"
                                name="position_id"
                                required>
                            <option value="" {{ old('position_id') == '' || old('position_id') === null ? 'selected' : '' }}>-- Pilih Jabatan --</option>
                            @foreach($positions as $position)
                            <option value="{{ $position->id }}"
                                    {{ old('position_id') == $position->id ? 'selected' : '' }}>
                                {{ $position->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('position_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label fw-bold">Keterangan <span class="text-danger">*</span></label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description"
                          name="description"
                          rows="4"
                          placeholder="Masukkan keterangan"
                          required>{{ old('description') }}</textarea>
                @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="oi oi-check"></i> Simpan
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="oi oi-x"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('photo');
    const fileUploadBox = document.getElementById('fileUploadBox');
    const uploadContent = document.querySelector('.file-upload-content');
    const filePreview = document.getElementById('filePreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileRemove = document.getElementById('fileRemove');

    // Drag and drop
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
            handleFileSelect(files[0]);
        }
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // Handle file selection
    function handleFileSelect(file) {
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('File harus berupa gambar!');
            return;
        }

        // Validate file size (2MB)
        if (file.size > 2048 * 1024) {
            alert('Ukuran file maksimal 2MB!');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            fileName.textContent = file.name;
            uploadContent.style.display = 'none';
            filePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Remove file
    fileRemove.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fileInput.value = '';
        uploadContent.style.display = 'block';
        filePreview.style.display = 'none';
        previewImage.src = '';
        fileName.textContent = '';
    });
});
</script>
@endpush
@endsection

