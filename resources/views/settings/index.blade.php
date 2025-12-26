@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Pengaturan Sistem</h1>
</div>

<div class="row">
    <div class="col-md-12">
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pengaturan Password</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Konfigurasi persyaratan password untuk pengguna di workspace ini.</p>

                <form method="POST" action="{{ route('workspace.settings.password.update', ['workspace' => $workspace->slug]) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password_min_length" class="form-label fw-bold">Panjang Password Minimum</label>
                        <input type="number"
                               class="form-control @error('password_min_length') is-invalid @enderror"
                               id="password_min_length"
                               name="password_min_length"
                               value="{{ old('password_min_length', $passwordRequirements['min_length']) }}"
                               min="6"
                               max="32"
                               required>
                        @error('password_min_length')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 6 karakter, maksimum 32 karakter</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="password_require_uppercase"
                                   name="password_require_uppercase"
                                   value="1"
                                   {{ old('password_require_uppercase', $passwordRequirements['require_uppercase']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="password_require_uppercase">
                                Wajib Huruf Besar
                            </label>
                        </div>
                        <small class="form-text text-muted">Password harus mengandung minimal satu huruf besar (A-Z)</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="password_require_lowercase"
                                   name="password_require_lowercase"
                                   value="1"
                                   {{ old('password_require_lowercase', $passwordRequirements['require_lowercase']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="password_require_lowercase">
                                Wajib Huruf Kecil
                            </label>
                        </div>
                        <small class="form-text text-muted">Password harus mengandung minimal satu huruf kecil (a-z)</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="password_require_numbers"
                                   name="password_require_numbers"
                                   value="1"
                                   {{ old('password_require_numbers', $passwordRequirements['require_numbers']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="password_require_numbers">
                                Wajib Angka
                            </label>
                        </div>
                        <small class="form-text text-muted">Password harus mengandung minimal satu angka (0-9)</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="password_require_symbols"
                                   name="password_require_symbols"
                                   value="1"
                                   {{ old('password_require_symbols', $passwordRequirements['require_symbols']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="password_require_symbols">
                                Wajib Simbol
                            </label>
                        </div>
                        <small class="form-text text-muted">Password harus mengandung minimal satu simbol (!@#$%^&*()_+-=[]{}|;:,.<>?)</small>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3">Password Default untuk Pegawai Baru</h6>
                    <div class="mb-3">
                        <label for="employee_default_password" class="form-label fw-bold">Password Default (Opsional)</label>
                        <input type="text"
                               class="form-control @error('employee_default_password') is-invalid @enderror"
                               id="employee_default_password"
                               name="employee_default_password"
                               value="{{ old('employee_default_password', $defaultPassword) }}"
                               placeholder="Kosongkan untuk auto-generate">
                        @error('employee_default_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Jika dikosongkan, sistem akan generate password otomatis yang memenuhi persyaratan di atas.
                            Jika diisi, password ini akan digunakan untuk semua pegawai baru (harus memenuhi persyaratan).
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <strong>Catatan:</strong> Pengaturan password requirements akan diterapkan untuk:
                        <ul class="mb-0 mt-2">
                            <li>Registrasi pengguna baru</li>
                            <li>Perubahan password</li>
                            <li>API registration</li>
                            <li>Password untuk pegawai baru (jika admin set custom password)</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i class="oi oi-check"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

