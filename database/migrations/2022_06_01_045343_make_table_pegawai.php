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
        Schema::create('pegawai',function(Blueprint $table){
            $table->increments('id_pegawai');
            $table->string('nama_pegawai', 30);
            $table->date('tgl_lahir');
            $table->string('foto',50);
            $table->text('keterangan');
            $table->integer('id_jabatan');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pegawai');
    }
};
