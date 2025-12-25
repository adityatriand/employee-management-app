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
        // This migration is no longer needed as description is now in the original migration
        // Keeping it for backward compatibility but making it safe
        if (!Schema::hasColumn('jabatan', 'description')) {
            Schema::table('jabatan', function(Blueprint $table){
                $table->text('description')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
