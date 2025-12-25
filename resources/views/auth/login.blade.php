@extends('layouts.auth')

@section('content')
@if(isset($workspace))
<div class="text-center mb-4">
    <h2 class="text-black mb-2">{{ $workspace->name }}</h2>
</div>
@endif

<h1 class="auth-title">Masuk</h1>
<p class="auth-subtitle">Silakan masuk ke akun Anda</p>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('workspace.login.post', ['workspace' => $workspace->slug]) }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label fw-bold">Alamat Email</label>
        <input id="email"
               type="email"
               class="form-control @error('email') is-invalid @enderror"
               name="email"
               value="{{ old('email') }}"
               required
               autocomplete="email"
               autofocus
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
               autocomplete="current-password"
               placeholder="Masukkan kata sandi">
        @error('password')
        <div class="invalid-feedback">
            <strong>{{ $message }}</strong>
        </div>
        @enderror
    </div>

    <div class="mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">
                Ingat Saya
            </label>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="oi oi-account-login"></i> Masuk
        </button>

        @if (Route::has('password.request'))
        <a class="btn btn-link text-center" href="{{ route('password.request') }}">
            Lupa Kata Sandi?
        </a>
        @endif
    </div>
</form>
@endsection
