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
            $table->increments('id');
            $table->string('name', 255);
            $table->string('gender', 1);
            $table->date('birth_date');
            $table->string('photo', 50);
            $table->text('description');
            $table->unsignedInteger('position_id');
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
