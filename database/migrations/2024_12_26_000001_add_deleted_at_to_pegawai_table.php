<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = Schema::hasTable('employees') ? 'employees' : 'pegawai';
        Schema::table($tableName, function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = Schema::hasTable('employees') ? 'employees' : 'pegawai';
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

