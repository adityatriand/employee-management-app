@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title">Data Jabatan</h1>
        @if(auth()->user()->level == 1)
        <a href="{{ route('workspace.positions.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
            <i class="oi oi-plus"></i> Tambah Jabatan
        </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($positions->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Jabatan</th>
                        <th style="width: 100px;">Jumlah Pegawai</th>
                        <th style="width: 180px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($positions as $position)
                    <tr>
                        <td>{{ $positions->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $position->name }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $position->employees_count }} Pegawai</span>
                        </td>
                        <td class="text-center">
                            @if(auth()->user()->level == 1)
                            <div class="action-buttons">
                                <a href="{{ route('workspace.positions.edit', ['workspace' => $workspace->slug, 'position' => $position->id]) }}" 
                                   class="btn-action btn-edit" title="Edit">
                                    <i class="oi oi-pencil"></i>
                                    <span>Edit</span>
                                </a>
                                <form action="{{ route('workspace.positions.destroy', ['workspace' => $workspace->slug, 'position' => $position->id]) }}" 
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus jabatan ini?');">
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
                Menampilkan {{ $positions->firstItem() }} sampai {{ $positions->lastItem() }} dari {{ $positions->total() }} data
            </div>
            <div>
                {{ $positions->links() }}
            </div>
        </div>
        @else
        <div class="text-center py-5">
            <i class="oi oi-briefcase" style="font-size: 4rem; color: #cbd5e1;"></i>
            <p class="mt-3 text-muted">Belum ada data jabatan</p>
            <a href="{{ route('workspace.positions.create', ['workspace' => $workspace->slug]) }}" class="btn btn-success">
                <i class="oi oi-plus"></i> Tambah Jabatan Pertama
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

