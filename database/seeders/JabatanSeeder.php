<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('jabatan')->insert(array(
            ['nama_jabatan'=>'Direktur','keterangan'=>''],
            ['nama_jabatan'=>'Manajer','keterangan'=>''],
            ['nama_jabatan'=>'Sekretaris','keterangan'=>'']
        ));
    }
}
