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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'key']);
        });

        // Insert default password settings
        DB::table('settings')->insert([
            [
                'workspace_id' => null, // Global setting
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'description' => 'Minimum password length',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workspace_id' => null,
                'key' => 'password_require_uppercase',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require uppercase letters in password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workspace_id' => null,
                'key' => 'password_require_lowercase',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require lowercase letters in password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workspace_id' => null,
                'key' => 'password_require_numbers',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require numbers in password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workspace_id' => null,
                'key' => 'password_require_symbols',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Require symbols in password',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'workspace_id' => null,
                'key' => 'employee_default_password',
                'value' => '',
                'type' => 'string',
                'description' => 'Default password for new employees (leave empty to auto-generate)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
};

