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
        // Determine which employee table name to use
        $employeeTable = Schema::hasTable('employees') ? 'employees' : 'pegawai';

        Schema::create('assets', function (Blueprint $table) use ($employeeTable) {
            $table->increments('id');
            $table->string('name');
            $table->string('asset_tag')->unique()->nullable(); // Unique asset tag/ID
            $table->text('description')->nullable();
            $table->string('asset_type'); // laptop, phone, equipment, vehicle, furniture, etc.
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->enum('status', ['available', 'assigned', 'maintenance', 'retired', 'lost'])->default('available');
            $table->string('current_location')->nullable();
            $table->string('department')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->string('image')->nullable(); // Path to asset photo in MinIO
            $table->text('notes')->nullable();
            $table->unsignedInteger('assigned_to')->nullable(); // Current assignment
            $table->date('assigned_date')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('asset_type');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('asset_tag');
        });

        // Add foreign keys after table creation
        if (Schema::hasTable($employeeTable)) {
            DB::statement("ALTER TABLE `assets` ADD CONSTRAINT `assets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `{$employeeTable}` (`id`) ON DELETE SET NULL");
        }
        if (Schema::hasTable('users')) {
            DB::statement('ALTER TABLE `assets` ADD CONSTRAINT `assets_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};

