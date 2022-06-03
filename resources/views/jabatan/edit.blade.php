@extends('layouts.app')

@section('content')
<h4 class="mt-2">Edit Jabatan</h4>
<hr>

@if ($errors->any())
<div class="alert alert-danger pb-0">
    <u>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </u>
</div>
@endif

<form action="{{ route('jabatan.update', $jabatan['id_jabatan']) }}" method="POST">
    @method('PUT')
    @csrf
    <div class="form-group row">
        <label for="nama" class="col-sm-2 col-form-label">Nama Jabatan</label>
        <div class="col-sm-4">
            <input type="text" class="form-control" id="nama" name="nama" value="{{ $jabatan['nama_jabatan'] }}">
        </div>
    </div>
    <button type="submit" class="btn btn-info"><i class="oi oi-task"></i> Simpan</button>
    <button type="reset" class="btn btn-warning"><i class="oi oi-circle-x"></i> Batal</button>
</form>

@endsection
