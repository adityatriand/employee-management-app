<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pegawai = Pegawai::leftJoin('jabatan', 'jabatan.id_jabatan', '=', 'pegawai.id_jabatan')->get();
        return view('pegawai/index', ['pegawai'=>$pegawai, 'no'=>0]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $jabatan = Jabatan::all();
        return view('pegawai/create', ['jabatan'=>$jabatan]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nama'=> 'required|max:255',
            'jk'=> 'required',
            'tanggal'=> 'required',
            'jabatan'=> 'required',
            'keterangan'=>'required',
            'foto'=>'required',
        ],[
            'nama.required'=>'Nama pegawai tidak boleh kosong',
            'jk.required'=>'Jenis kelamin tidak boleh kosong',
            'tanggal.required'=>'Tanggal tidak boleh kosong',
            'jabatan.required'=>'Jabatan harus diisi',
            'keterangan.required'=>'Keterangan tidak boleh kosong',
            'foto.required'=>'Foto tidak boleh kosong'
        ]);

        if($request->hasFile('foto')){
            $file = $request->file('foto');
            $namaFile = time().$file->getClientOriginalName();
            $file->move(url('/').'images/', $namaFile);
        }

        $pegawai = new Pegawai();
        $pegawai->nama_pegawai = $request['nama'];
        $pegawai->jenis_kelamin = $request['jk'];
        $pegawai->tgl_lahir = $request['tanggal'];
        $pegawai->id_jabatan = $request['jabatan'];
        $pegawai->keterangan = $request['keterangan'];
        $pegawai->foto = $namaFile;
        $pegawai->save();

        return redirect()->route('pegawai.index')->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pegawai = Pegawai::find($id);
        $jabatan = Jabatan::all();
        $l = ($pegawai['jenis_kelamin']== "L")? " checked" : "";
        $p = ($pegawai['jenis_kelamin']== "P")? " checked" : "";
        return view('pegawai/edit', [
            'pegawai'=>$pegawai, 
            'jabatan'=>$jabatan,
            'l' => $l,
            'p' => $p,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama'=> 'required|max:255',
            'jk'=> 'required',
            'tanggal'=> 'required',
            'jabatan'=> 'required',
            'keterangan'=>'required'
        ],[
            'nama.required'=>'Nama pegawai tidak boleh kosong',
            'jk.required'=>'Jenis kelamin tidak boleh kosong',
            'tanggal.required'=>'Tanggal tidak boleh kosong',
            'jabatan.required'=>'Jabatan harus diisi',
            'keterangan.required'=>'Keterangan tidak boleh kosong'
        ]);

        $ubahfile = false;
        if($request->hasFile('foto')){
            $file = $request->file('foto');
            $namaFile = time().$file->getClientOriginalName();
            $file->move(url('/').'images/', $namaFile);
            $ubahfile = true;
        }

        $pegawai = Pegawai::find($id);
        $pegawai->nama_pegawai = $request['nama'];
        $pegawai->jenis_kelamin = $request['jk'];
        $pegawai->tgl_lahir = $request['tanggal'];
        $pegawai->id_jabatan = $request['jabatan'];
        $pegawai->keterangan = $request['keterangan'];
        if($ubahfile){
            unlink(url('/').'images/'.$pegawai->foto);
            $pegawai->foto = $namaFile;
        }
        $pegawai->update();
        return redirect()
            ->route('pegawai.index')
            ->with('success','Datta berhasil diedit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pegawai = Pegawai::find($id);
        $pegawai->delete();
        unlink(url('/').'images/'.$pegawai->foto);

        return redirect()
            ->route('pegawai.index')
            ->with('success','Data berhasil dihapus');
    }
}
