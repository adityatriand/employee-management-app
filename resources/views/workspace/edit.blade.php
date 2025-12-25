@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Workspace</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Edit Workspace</h5>
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

                <form method="POST" action="{{ route('workspace.update', ['workspace' => $workspace->slug]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Nama Workspace</label>
                        <input id="name"
                               type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               name="name"
                               value="{{ old('name', $workspace->name) }}"
                               required
                               autofocus
                               placeholder="Contoh: Perusahaan ABC">
                        @error('name')
                        <div class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label fw-bold">Slug Workspace</label>
                        <input type="text"
                               id="slug"
                               class="form-control"
                               value="{{ $workspace->slug }}"
                               disabled
                               readonly>
                        <small class="text-muted">Slug tidak dapat diubah karena digunakan dalam URL workspace</small>
                    </div>

                    <div class="mb-4">
                        <label for="logo" class="form-label fw-bold">Logo Workspace</label>
                        <div class="file-upload-box @error('logo') is-invalid @enderror" id="logoUploadBox">
                            <input type="file"
                                   class="file-input"
                                   id="logo"
                                   name="logo"
                                   accept="image/*"
                                   onchange="previewLogo(this)">
                            <div class="file-upload-content">
                                @if($workspace->logo)
                                <div style="text-align: center; margin-bottom: 0.5rem;">
                                    <img src="{{ $workspace->logo_url }}"
                                         alt="{{ $workspace->name }}"
                                         style="max-height: 100px; max-width: 100%; object-fit: contain; display: inline-block;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="oi oi-image" style="display: none;"></i>
                                </div>
                                @else
                                <i class="oi oi-image"></i>
                                @endif
                                <div class="file-upload-text">Klik atau drag logo di sini untuk mengubah</div>
                                <div class="file-upload-hint">Format: JPG, PNG, GIF (Max: 2MB)</div>
                            </div>
                            <div class="file-preview" id="logoPreview" style="display: none;">
                                <img id="previewImage" src="" alt="Logo Preview">
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
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="oi oi-check"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('workspace.dashboard', ['workspace' => $workspace->slug]) }}" class="btn btn-secondary">
                            <i class="oi oi-x"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    const previewImage = document.getElementById('previewImage');
    const uploadContent = document.querySelector('#logoUploadBox .file-upload-content');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.style.display = 'block';
            uploadContent.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeLogo() {
    const input = document.getElementById('logo');
    const preview = document.getElementById('logoPreview');
    const uploadContent = document.querySelector('#logoUploadBox .file-upload-content');

    input.value = '';
    preview.style.display = 'none';
    uploadContent.style.display = 'block';
}
</script>
@endpush
@endsection

