@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tambah Jabatan</h1>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Form Tambah Jabatan</h5>
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

        <form action="{{ route('workspace.positions.store', ['workspace' => $workspace->slug]) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="name" class="form-label fw-bold">Nama Jabatan <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('name') is-invalid @enderror" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}"
                       placeholder="Masukkan nama jabatan"
                       required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="oi oi-check"></i> Simpan
                </button>
                <a href="{{ route('workspace.positions.index', ['workspace' => $workspace->slug]) }}" class="btn btn-secondary">
                    <i class="oi oi-x"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

