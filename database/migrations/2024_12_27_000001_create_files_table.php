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
        if (Schema::hasTable('files')) {
            // Table already exists, update foreign key if needed
            try {
                // Check if foreign key references old table name (pegawai) or needs to be updated
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'employee_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (!empty($foreignKeys)) {
                    $refTable = $foreignKeys[0]->REFERENCED_TABLE_NAME;
                    // If referencing old table name or if employees table exists and we're not referencing it
                    if ($refTable === 'pegawai' || (Schema::hasTable('employees') && $refTable !== 'employees')) {
                        // Drop old foreign key
                        $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
                        DB::statement("ALTER TABLE `files` DROP FOREIGN KEY `{$fkName}`");
                        // Add new foreign key if employees table exists
                        if (Schema::hasTable('employees')) {
                            DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE');
                        }
                    }
                } elseif (Schema::hasTable('employees') && !Schema::hasTable('pegawai')) {
                    // No foreign key exists but employees table does, add it
                    DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE');
                }
            } catch (\Exception $e) {
                // Ignore errors - foreign key might already be correct or table might not exist yet
            }
            return;
        }

        // Determine which employee table name to use
        $employeeTable = Schema::hasTable('employees') ? 'employees' : 'pegawai';

        Schema::create('files', function (Blueprint $table) use ($employeeTable) {
            $table->id();
            $table->string('name'); // Original file name
            $table->string('file_name'); // Stored file name
            $table->string('file_path'); // Path in MinIO
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // Size in bytes
            $table->string('file_type')->default('document'); // 'document' or 'photo'
            $table->string('category')->nullable(); // e.g., 'contract', 'certificate', 'id_card', etc.
            $table->text('description')->nullable();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index('file_type');
            $table->index('category');
        });

        // Add foreign keys after table creation
        if (Schema::hasTable($employeeTable)) {
            DB::statement("ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `{$employeeTable}` (`id`) ON DELETE CASCADE");
        }
        if (Schema::hasTable('users')) {
            DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};

