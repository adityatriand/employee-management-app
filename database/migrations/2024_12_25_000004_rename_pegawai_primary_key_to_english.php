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
        // Check if primary key column needs renaming
        $columns = DB::select("SHOW COLUMNS FROM `pegawai`");
        $columnNames = array_column($columns, 'Field');
        
        if (in_array('id_pegawai', $columnNames) && !in_array('id', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `id_pegawai` `id` INT UNSIGNED AUTO_INCREMENT');
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
        
        if (in_array('id', $columnNames) && !in_array('id_pegawai', $columnNames)) {
            DB::statement('ALTER TABLE `pegawai` CHANGE `id` `id_pegawai` INT UNSIGNED AUTO_INCREMENT');
        }
    }
};
