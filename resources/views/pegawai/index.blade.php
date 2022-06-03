@extends('layouts.app')

@section('content')
<h4 class="mt-2">Data Pegawai</h4>
<hr>
<a href="{{ route('pegawai.create') }}" class="btn btn-success"><i class="oi oi-plus"></i> Tambah</a>

@if ($message = Session::get('success'))
<div class="alert alert-success mt-3 pb-0">
    <p>{{ $message }}</p>
</div>
@endif

<div class="table-resonsive mt-3">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Foto</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Tanggal Lahir</th>
                <th>Jabatan</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pegawai as $data)
            <tr>
                <td>{{ ++$no }}</td>
                <td><img src="{{ asset('images/'.$data['foto']) }}" alt="{{ $data['nama_pegawai'].' Foto' }}"
                        width="100"></td>
                <td>{{ $data['nama_pegawai'] }}</td>
                <td>{{ $data['jenis_kelamin'] }}</td>
                <td>{{ $data['tgl_lahir'] }}</td>
                <td>{{ $data['nama_jabatan'] }}</td>
                <td>{{ $data['keterangan'] }}</td>
                <td>
                    <a class="btn btn-sm btn-info" href="{{ route('jabatan.edit', $data['id_jabatan']) }}"><i
                            class="oi oi-pencil"></i> Edit </a>
                    <form action="{{ route('jabatan.destroy', $data['id_jabatan']) }}" class="d-inline" method="POST">
                        @method('DELETE')
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger"><i class="oi oi-trash"></i> Hapus </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
