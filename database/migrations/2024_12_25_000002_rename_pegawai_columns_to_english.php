<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if columns exist before renaming
        $columns = DB::select("SHOW COLUMNS FROM `pegawai`");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('nama_pegawai', $columnNames) && !in_array('name', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `nama_pegawai` `name` VARCHAR(30) NOT NULL');
        }
        
        if (in_array('jenis_kelamin', $columnNames) && !in_array('gender', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `jenis_kelamin` `gender` VARCHAR(1) NOT NULL');
        }
        
        if (in_array('tgl_lahir', $columnNames) && !in_array('birth_date', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `tgl_lahir` `birth_date` DATE NOT NULL');
        }
        
        if (in_array('foto', $columnNames) && !in_array('photo', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `foto` `photo` VARCHAR(50) NOT NULL');
        }
        
        if (in_array('keterangan', $columnNames) && !in_array('description', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `keterangan` `description` TEXT NOT NULL');
        }
        
        if (in_array('id_jabatan', $columnNames) && !in_array('position_id', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `id_jabatan` `position_id` INT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $columns = DB::select("SHOW COLUMNS FROM `pegawai`");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('name', $columnNames) && !in_array('nama_pegawai', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `name` `nama_pegawai` VARCHAR(30) NOT NULL');
        }
        
        if (in_array('gender', $columnNames) && !in_array('jenis_kelamin', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `gender` `jenis_kelamin` VARCHAR(1) NOT NULL');
        }
        
        if (in_array('birth_date', $columnNames) && !in_array('tgl_lahir', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `birth_date` `tgl_lahir` DATE NOT NULL');
        }
        
        if (in_array('photo', $columnNames) && !in_array('foto', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `photo` `foto` VARCHAR(50) NOT NULL');
        }
        
        if (in_array('description', $columnNames) && !in_array('keterangan', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `description` `keterangan` TEXT NOT NULL');
        }
        
        if (in_array('position_id', $columnNames) && !in_array('id_jabatan', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `position_id` `id_jabatan` INT UNSIGNED NOT NULL');
        }
    }
};
