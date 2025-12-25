@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted">Selamat datang, {{ auth()->user()->name }}!</p>
        </div>
    </div>

    @if(!isset($employee))
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                <i class="oi oi-warning"></i> Profil pegawai tidak ditemukan. Silakan hubungi administrator untuk membuat profil Anda.
            </div>
        </div>
    </div>
    @else
    <!-- Employee Profile Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Profil Saya</h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('workspace.profile.edit', ['workspace' => $workspace->slug]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="oi oi-pencil"></i> Edit Profil
                        </a>
                        <a href="{{ route('workspace.employees.show', ['workspace' => $workspace->slug, 'employee' => $employee->id]) }}" class="btn btn-sm btn-primary">
                            <i class="oi oi-eye"></i> Lihat Detail
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <h4>{{ $employee->name }}</h4>
                            <p class="text-muted mb-2">
                                <i class="oi oi-briefcase"></i> {{ $employee->position->name ?? 'Tidak ada jabatan' }}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="oi oi-person"></i> {{ $employee->gender == 'L' ? 'Laki-Laki' : 'Perempuan' }}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="oi oi-calendar"></i> {{ $employee->birth_date ? $employee->birth_date->format('d F Y') : '-' }}
                            </p>
                            @if($employee->description)
                            <p class="mt-3">{{ $employee->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dokumen Saya</h5>
                </div>
                <div class="card-body">
                    @if($files->count() > 0)
                    <div class="list-group">
                        @foreach($files as $file)
                        <a href="{{ route('workspace.files.show', ['workspace' => $workspace->slug, 'file' => $file->id]) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="oi oi-file"></i> {{ $file->name }}
                                    <small class="text-muted d-block">{{ $file->file_type }} - {{ $file->created_at->format('d M Y') }}</small>
                                </div>
                                <i class="oi oi-chevron-right"></i>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    <a href="{{ route('workspace.files.index', ['workspace' => $workspace->slug]) }}" class="btn btn-sm btn-link mt-2">
                        Lihat semua dokumen <i class="oi oi-chevron-right"></i>
                    </a>
                    @else
                    <p class="text-muted mb-0">Belum ada dokumen</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aset yang Ditugaskan</h5>
                </div>
                <div class="card-body">
                    @if($assets->count() > 0)
                    <div class="list-group">
                        @foreach($assets as $asset)
                        <a href="{{ route('workspace.assets.show', ['workspace' => $workspace->slug, 'asset' => $asset->id]) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="oi oi-box"></i> {{ $asset->name }}
                                    <small class="text-muted d-block">{{ $asset->asset_tag }} - {{ $asset->assigned_date ? \Carbon\Carbon::parse($asset->assigned_date)->format('d M Y') : '-' }}</small>
                                </div>
                                <i class="oi oi-chevron-right"></i>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    <a href="{{ route('workspace.assets.index', ['workspace' => $workspace->slug]) }}" class="btn btn-sm btn-link mt-2">
                        Lihat semua aset <i class="oi oi-chevron-right"></i>
                    </a>
                    @else
                    <p class="text-muted mb-0">Tidak ada aset yang ditugaskan</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection
