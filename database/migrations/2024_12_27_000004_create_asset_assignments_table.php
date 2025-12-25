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

        Schema::create('asset_assignments', function (Blueprint $table) use ($employeeTable) {
            $table->increments('id');
            $table->unsignedInteger('asset_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('assigned_by');
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('asset_id');
            $table->index('employee_id');
            $table->index('assigned_at');
        });

        // Add foreign keys
        DB::statement("ALTER TABLE `asset_assignments` ADD CONSTRAINT `asset_assignments_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE");
        if (Schema::hasTable($employeeTable)) {
            DB::statement("ALTER TABLE `asset_assignments` ADD CONSTRAINT `asset_assignments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `{$employeeTable}` (`id`) ON DELETE CASCADE");
        }
        if (Schema::hasTable('users')) {
            DB::statement('ALTER TABLE `asset_assignments` ADD CONSTRAINT `asset_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE');
            DB::statement('ALTER TABLE `asset_assignments` ADD CONSTRAINT `asset_assignments_returned_by_foreign` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_assignments');
    }
};

