@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Profil Saya</h1>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Form Edit Profil</h5>
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

        <form action="{{ route('workspace.profile.update', ['workspace' => $workspace->slug]) }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Foto</label>
                        <div class="file-upload-box @error('photo') is-invalid @enderror" id="fileUploadBox">
                            <input type="file" 
                                   class="file-input" 
                                   id="photo" 
                                   name="photo" 
                                   accept="image/*">
                            <div class="file-upload-content" id="uploadContent">
                                @if($employee->photo)
                                <div class="current-photo mb-3">
                                    <p class="text-muted small mb-2">Foto saat ini:</p>
                                    <img src="{{ $employee->photo_url }}" 
                                         alt="{{ $employee->name }}" 
                                         class="current-photo-img">
                                </div>
                                @endif
                                <i class="oi oi-cloud-upload"></i>
                                <p class="file-upload-text">Klik atau seret foto baru ke sini</p>
                                <p class="file-upload-hint">Kosongkan jika tidak ingin mengubah. Format: JPG, PNG, GIF. Maksimal 2MB</p>
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
                        <label for="name" class="form-label fw-bold">Nama <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $employee->name) }}"
                               placeholder="Masukkan nama"
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
                                       {{ old('gender', $employee->gender) == 'L' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="jk_l">Laki-Laki</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="jk_p" value="P" 
                                       {{ old('gender', $employee->gender) == 'P' ? 'checked' : '' }} required>
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
                               value="{{ old('birth_date', $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '') }}"
                               required>
                        @error('birth_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="oi oi-check"></i> Update Profil
                </button>
                <a href="{{ route('workspace.dashboard', ['workspace' => $workspace->slug]) }}" class="btn btn-secondary">
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
    const uploadContent = document.getElementById('uploadContent');
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

