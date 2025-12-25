@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Ubah Password</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ubah Password</h5>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('workspace.password.update', ['workspace' => $workspace->slug]) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-bold">Password Saat Ini</label>
                        <input id="current_password"
                               type="password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               name="current_password"
                               required
                               autocomplete="current-password"
                               autofocus>
                        @error('current_password')
                        <div class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">Password Baru</label>
                        <input id="password"
                               type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password"
                               required
                               autocomplete="new-password">
                        <small class="form-text text-muted">Minimal 8 karakter</small>
                        @error('password')
                        <div class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password-confirm" class="form-label fw-bold">Konfirmasi Password Baru</label>
                        <input id="password-confirm"
                               type="password"
                               class="form-control"
                               name="password_confirmation"
                               required
                               autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="oi oi-lock-locked"></i> Ubah Password
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
@endsection

