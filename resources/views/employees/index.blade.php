@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title">Data Pegawai</h1>
        @if(auth()->user()->level == 1)
        <a href="{{ route('employees.create') }}" class="btn btn-success">
            <i class="oi oi-plus"></i> Tambah Pegawai
        </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($employees->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>Jabatan</th>
                        <th style="width: 180px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $employee)
                    <tr>
                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                        <td>
                            <img src="{{ $employee->photo_url }}" 
                                 alt="{{ $employee->name }}" 
                                 class="employee-photo">
                        </td>
                        <td>
                            <strong>{{ $employee->name }}</strong>
                        </td>
                        <td>
                            @if($employee->gender == 'L')
                                <span class="badge bg-primary">Laki-Laki</span>
                            @else
                                <span class="badge bg-danger">Perempuan</span>
                            @endif
                        </td>
                        <td>{{ $employee->birth_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-info">{{ $employee->position->name ?? '-' }}</span>
                        </td>
                        <td class="text-center">
                            @if(auth()->user()->level == 1)
                            <div class="action-buttons">
                                <a href="{{ route('employees.edit', $employee->id) }}" 
                                   class="btn-action btn-edit" title="Edit">
                                    <i class="oi oi-pencil"></i>
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('employees.destroy', $employee->id) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        <i class="oi oi-trash"></i>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Menampilkan {{ $employees->firstItem() }} sampai {{ $employees->lastItem() }} dari {{ $employees->total() }} data
            </div>
            <div>
                {{ $employees->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-people" style="font-size: 4rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Belum ada data pegawai</p>
            <a href="{{ route('employees.create') }}" class="btn btn-success">
                <i class="oi oi-plus"></i> Tambah Pegawai Pertama
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

