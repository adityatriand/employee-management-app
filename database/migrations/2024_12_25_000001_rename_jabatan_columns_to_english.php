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
        $columns = DB::select("SHOW COLUMNS FROM `jabatan`");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('nama_jabatan', $columnNames) && !in_array('name', $columnNames)) {
            DB::statement('ALTER TABLE `jabatan` CHANGE `nama_jabatan` `name` VARCHAR(255) NOT NULL');
        }
        
        if (in_array('keterangan', $columnNames) && !in_array('description', $columnNames)) {
            DB::statement('ALTER TABLE `jabatan` CHANGE `keterangan` `description` TEXT');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $columns = DB::select("SHOW COLUMNS FROM `jabatan`");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('name', $columnNames) && !in_array('nama_jabatan', $columnNames)) {
            DB::statement('ALTER TABLE `jabatan` CHANGE `name` `nama_jabatan` VARCHAR(255) NOT NULL');
        }
        
        if (in_array('description', $columnNames) && !in_array('keterangan', $columnNames)) {
            DB::statement('ALTER TABLE `jabatan` CHANGE `description` `keterangan` TEXT');
        }
    }
};
