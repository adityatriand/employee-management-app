@extends('layouts.auth')

@section('content')
<h1 class="auth-title">Daftar</h1>
<p class="auth-subtitle">Buat akun baru untuk mulai menggunakan sistem</p>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label fw-bold">Nama Lengkap</label>
        <input id="name" 
               type="text" 
               class="form-control @error('name') is-invalid @enderror" 
               name="name" 
               value="{{ old('name') }}" 
               required 
               autocomplete="name" 
               autofocus
               placeholder="Masukkan nama lengkap">
        @error('name')
        <div class="invalid-feedback">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-bold">Alamat Email</label>
        <input id="email" 
               type="email" 
               class="form-control @error('email') is-invalid @enderror" 
               name="email" 
               value="{{ old('email') }}" 
               required 
               autocomplete="email"
               placeholder="nama@email.com">
        @error('email')
        <div class="invalid-feedback">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-bold">Kata Sandi</label>
        <input id="password" 
               type="password" 
               class="form-control @error('password') is-invalid @enderror" 
               name="password" 
               required 
               autocomplete="new-password"
               placeholder="Minimal 8 karakter">
        @error('password')
        <div class="invalid-feedback">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password-confirm" class="form-label fw-bold">Konfirmasi Kata Sandi</label>
        <input id="password-confirm" 
               type="password" 
               class="form-control" 
               name="password_confirmation" 
               required 
               autocomplete="new-password"
               placeholder="Ulangi kata sandi">
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="oi oi-person"></i> Daftar
        </button>

        <div class="text-center mt-3">
            <span class="text-muted">Sudah punya akun? </span>
            <a href="{{ route('login') }}" class="text-primary">Masuk</a>
        </div>
    </div>
</form>
@endsection
