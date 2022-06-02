<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('pegawai')->insert(array(
            ['nama_pegawai'=>'Aditya','tgl_lahir'=>'2022-05-01','id_jabatan'=>1,'foto'=>'','keterangan'=>''],
            ['nama_pegawai'=>'Tri','tgl_lahir'=>'2022-05-02','id_jabatan'=>2,'foto'=>'','keterangan'=>''],
            ['nama_pegawai'=>'Ananda','tgl_lahir'=>'2022-05-03','id_jabatan'=>2,'foto'=>'','keterangan'=>''],
        ));
    }
}
