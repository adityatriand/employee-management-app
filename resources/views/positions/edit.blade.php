@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Jabatan</h1>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Form Edit Jabatan</h5>
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

        <form action="{{ route('workspace.positions.update', ['workspace' => $workspace->slug, 'position' => $position->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="name" class="form-label fw-bold">Nama Jabatan <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control @error('name') is-invalid @enderror" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $position->name) }}"
                       placeholder="Masukkan nama jabatan"
                       required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="oi oi-check"></i> Update
                </button>
                <a href="{{ route('workspace.positions.index', ['workspace' => $workspace->slug]) }}" class="btn btn-secondary">
                    <i class="oi oi-x"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

