@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title mb-0">Edit Aset</h1>
        </div>
        <div>
            <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="oi oi-chevron-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Form Edit Aset</h5>
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

                <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" id="assetForm">
                    @method('PUT')
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Nama Aset <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $asset->name) }}"
                                       placeholder="Contoh: Laptop Dell XPS 15"
                                       required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="asset_tag" class="form-label fw-bold">Tag Aset</label>
                                <input type="text"
                                       class="form-control @error('asset_tag') is-invalid @enderror"
                                       id="asset_tag"
                                       name="asset_tag"
                                       value="{{ old('asset_tag', $asset->asset_tag) }}"
                                       placeholder="Akan digenerate otomatis jika kosong">
                                @error('asset_tag')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Deskripsi aset...">{{ old('description', $asset->description) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="asset_type" class="form-label fw-bold">Tipe Aset <span class="text-danger">*</span></label>
                                <select class="form-select @error('asset_type') is-invalid @enderror" id="asset_type" name="asset_type" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="laptop" {{ old('asset_type', $asset->asset_type) == 'laptop' ? 'selected' : '' }}>Laptop</option>
                                    <option value="phone" {{ old('asset_type', $asset->asset_type) == 'phone' ? 'selected' : '' }}>Telepon</option>
                                    <option value="tablet" {{ old('asset_type', $asset->asset_type) == 'tablet' ? 'selected' : '' }}>Tablet</option>
                                    <option value="equipment" {{ old('asset_type', $asset->asset_type) == 'equipment' ? 'selected' : '' }}>Peralatan</option>
                                    <option value="vehicle" {{ old('asset_type', $asset->asset_type) == 'vehicle' ? 'selected' : '' }}>Kendaraan</option>
                                    <option value="furniture" {{ old('asset_type', $asset->asset_type) == 'furniture' ? 'selected' : '' }}>Furnitur</option>
                                    <option value="other" {{ old('asset_type', $asset->asset_type) == 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('asset_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="available" {{ old('status', $asset->status) == 'available' ? 'selected' : '' }}>Tersedia</option>
                                    <option value="assigned" {{ old('status', $asset->status) == 'assigned' ? 'selected' : '' }}>Ditetapkan</option>
                                    <option value="maintenance" {{ old('status', $asset->status) == 'maintenance' ? 'selected' : '' }}>Perawatan</option>
                                    <option value="retired" {{ old('status', $asset->status) == 'retired' ? 'selected' : '' }}>Pensiun</option>
                                    <option value="lost" {{ old('status', $asset->status) == 'lost' ? 'selected' : '' }}>Hilang</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="department" class="form-label fw-bold">Departemen</label>
                                <input type="text"
                                       class="form-control @error('department') is-invalid @enderror"
                                       id="department"
                                       name="department"
                                       value="{{ old('department', $asset->department) }}"
                                       placeholder="IT, HR, Finance, dll">
                                @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="brand" class="form-label fw-bold">Merek</label>
                                <input type="text"
                                       class="form-control @error('brand') is-invalid @enderror"
                                       id="brand"
                                       name="brand"
                                       value="{{ old('brand', $asset->brand) }}"
                                       placeholder="Dell, HP, Apple, dll">
                                @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="model" class="form-label fw-bold">Model</label>
                                <input type="text"
                                       class="form-control @error('model') is-invalid @enderror"
                                       id="model"
                                       name="model"
                                       value="{{ old('model', $asset->model) }}"
                                       placeholder="XPS 15, MacBook Pro, dll">
                                @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label fw-bold">Nomor Seri</label>
                                <input type="text"
                                       class="form-control @error('serial_number') is-invalid @enderror"
                                       id="serial_number"
                                       name="serial_number"
                                       value="{{ old('serial_number', $asset->serial_number) }}"
                                       placeholder="Serial number">
                                @error('serial_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label fw-bold">Tanggal Pembelian</label>
                                <input type="date"
                                       class="form-control @error('purchase_date') is-invalid @enderror"
                                       id="purchase_date"
                                       name="purchase_date"
                                       value="{{ old('purchase_date', $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '') }}">
                                @error('purchase_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="purchase_price" class="form-label fw-bold">Harga Pembelian</label>
                                <input type="number"
                                       class="form-control @error('purchase_price') is-invalid @enderror"
                                       id="purchase_price"
                                       name="purchase_price"
                                       value="{{ old('purchase_price', $asset->purchase_price) }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                @error('purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="current_value" class="form-label fw-bold">Nilai Saat Ini</label>
                                <input type="number"
                                       class="form-control @error('current_value') is-invalid @enderror"
                                       id="current_value"
                                       name="current_value"
                                       value="{{ old('current_value', $asset->current_value) }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                @error('current_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_location" class="form-label fw-bold">Lokasi Saat Ini</label>
                                <input type="text"
                                       class="form-control @error('current_location') is-invalid @enderror"
                                       id="current_location"
                                       name="current_location"
                                       value="{{ old('current_location', $asset->current_location) }}"
                                       placeholder="Gedung A, Lantai 2, Ruang 201">
                                @error('current_location')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warranty_expiry" class="form-label fw-bold">Tanggal Berakhir Garansi</label>
                                <input type="date"
                                       class="form-control @error('warranty_expiry') is-invalid @enderror"
                                       id="warranty_expiry"
                                       name="warranty_expiry"
                                       value="{{ old('warranty_expiry', $asset->warranty_expiry ? $asset->warranty_expiry->format('Y-m-d') : '') }}">
                                @error('warranty_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Foto Aset</label>
                        <div class="file-upload-box @error('image') is-invalid @enderror" id="fileUploadBox">
                            <input type="file"
                                   class="file-input"
                                   id="image"
                                   name="image"
                                   accept="image/*">
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
                        @error('image')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="assigned_to" class="form-label fw-bold">Ditugaskan ke (Opsional)</label>
                        <select class="form-select searchable-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                            <option value="">Tidak Ditugaskan</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('assigned_to', $selectedEmployeeId) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="assignedDateGroup" style="display: none;">
                        <label for="assigned_date" class="form-label fw-bold">Tanggal Penugasan</label>
                        <input type="date"
                               class="form-control @error('assigned_date') is-invalid @enderror"
                               id="assigned_date"
                               name="assigned_date"
                               value="{{ old('assigned_date', date('Y-m-d')) }}">
                        @error('assigned_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-bold">Catatan</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Catatan tambahan...">{{ old('notes', $asset->notes) }}</textarea>
                        @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="oi oi-check"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload preview
    const fileInput = document.getElementById('image');
    const filePreview = document.getElementById('filePreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileRemove = document.getElementById('fileRemove');

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = file.name;
                filePreview.style.display = 'block';
                document.querySelector('.file-upload-content').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    fileRemove.addEventListener('click', function() {
        fileInput.value = '';
        filePreview.style.display = 'none';
        document.querySelector('.file-upload-content').style.display = 'block';
    });

    // Show current image if exists
    @if($asset->image)
    const currentImage = '{{ $asset->image_url }}';
    if (currentImage) {
        previewImage.src = currentImage;
        fileName.textContent = 'Foto saat ini';
        filePreview.style.display = 'block';
        document.querySelector('.file-upload-content').style.display = 'none';
    }
    @endif
});
</script>
@endpush
@endsection

