@extends('layouts.auth')

@section('content')
<h1 class="auth-title">Setup Workspace</h1>
<p class="auth-subtitle">Lengkapi informasi workspace Anda untuk melanjutkan</p>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<form method="POST" action="{{ route('workspace.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label fw-bold">Nama Workspace</label>
        <input id="name"
               type="text"
               class="form-control @error('name') is-invalid @enderror"
               name="name"
               value="{{ old('name') }}"
               required
               autofocus
               placeholder="Contoh: Perusahaan ABC">
        @error('name')
        <div class="invalid-feedback">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
        <small class="text-muted">Nama ini akan digunakan sebagai URL workspace Anda</small>
    </div>

    <div class="mb-4">
        <label for="logo" class="form-label fw-bold">Logo Workspace (Opsional)</label>
        <div class="file-upload-box @error('logo') is-invalid @enderror" id="logoUploadBox">
            <input type="file"
                   class="file-input"
                   id="logo"
                   name="logo"
                   accept="image/*"
                   onchange="previewLogo(this)">
            <div class="file-upload-content">
                <i class="oi oi-image"></i>
                <div class="file-upload-text">Klik atau drag logo di sini</div>
                <div class="file-upload-hint">Format: JPG, PNG, GIF (Max: 2MB)</div>
            </div>
            <div class="file-preview" id="logoPreview" style="display: none; position: relative; width: 100%; text-align: center;">
                <img id="previewImage" src="" alt="Logo Preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <button type="button" class="file-remove" onclick="removeLogo()">
                    <i class="oi oi-x"></i>
                </button>
            </div>
        </div>
        @error('logo')
        <div class="invalid-feedback d-block">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="oi oi-check"></i> Buat Workspace
        </button>
    </div>
</form>

@push('scripts')
<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    const previewImage = document.getElementById('previewImage');
    const uploadContent = document.querySelector('#logoUploadBox .file-upload-content');

    if (input.files && input.files[0]) {
        // Validate file type
        const file = input.files[0];
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            input.value = '';
            return;
        }

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.style.display = 'block';
            uploadContent.style.display = 'none';
        };
        reader.onerror = function() {
            alert('Error reading file');
            input.value = '';
        };
        reader.readAsDataURL(file);
    } else {
        // No file selected, hide preview
        preview.style.display = 'none';
        uploadContent.style.display = 'block';
    }
}

function removeLogo() {
    const input = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');
    const previewImage = document.getElementById('previewImage');
    const uploadContent = document.querySelector('#logoUploadBox .file-upload-content');

    input.value = '';
    previewImage.src = '';
    preview.style.display = 'none';
    uploadContent.style.display = 'block';
}
</script>
@endpush
@endsection

