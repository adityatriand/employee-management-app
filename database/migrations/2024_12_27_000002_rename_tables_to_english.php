<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        // Rename jabatan to positions
        if (Schema::hasTable('jabatan') && !Schema::hasTable('positions')) {
            DB::statement('RENAME TABLE `jabatan` TO `positions`');
        }

        // Rename pegawai to employees
        if (Schema::hasTable('pegawai') && !Schema::hasTable('employees')) {
            DB::statement('RENAME TABLE `pegawai` TO `employees`');
        }

        // Update foreign key in files table if it exists
        if (Schema::hasTable('files')) {
            try {
                // Get existing foreign key name
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'files' AND COLUMN_NAME = 'employee_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (!empty($foreignKeys)) {
                    $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `files` DROP FOREIGN KEY `{$fkName}`");
                }
                // Add new foreign key
                DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE');
            } catch (\Exception $e) {
                // Foreign key might not exist or already updated, try to add it anyway
                try {
                    DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE');
                } catch (\Exception $e2) {
                    // Ignore if it already exists
                }
            }
        }

        // Update foreign key in employees table (position_id references positions)
        if (Schema::hasTable('employees')) {
            try {
                // Get existing foreign key name
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'position_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
                if (!empty($foreignKeys)) {
                    $fkName = $foreignKeys[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE `employees` DROP FOREIGN KEY `{$fkName}`");
                }
                // Add new foreign key
                DB::statement('ALTER TABLE `employees` ADD CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE RESTRICT');
            } catch (\Exception $e) {
                // Foreign key might not exist or already updated, try to add it anyway
                try {
                    DB::statement('ALTER TABLE `employees` ADD CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE RESTRICT');
                } catch (\Exception $e2) {
                    // Ignore if it already exists
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Update foreign key in files table back to pegawai
        if (Schema::hasTable('files')) {
            try {
                DB::statement('ALTER TABLE `files` DROP FOREIGN KEY IF EXISTS `files_employee_id_foreign`');
                DB::statement('ALTER TABLE `files` ADD CONSTRAINT `files_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE');
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        // Update foreign key in employees table back
        if (Schema::hasTable('employees')) {
            try {
                DB::statement('ALTER TABLE `employees` DROP FOREIGN KEY IF EXISTS `employees_position_id_foreign`');
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        // Rename positions back to jabatan
        if (Schema::hasTable('positions') && !Schema::hasTable('jabatan')) {
            DB::statement('RENAME TABLE `positions` TO `jabatan`');
        }

        // Rename employees back to pegawai
        if (Schema::hasTable('employees') && !Schema::hasTable('pegawai')) {
            DB::statement('RENAME TABLE `employees` TO `pegawai`');
        }
    }
};

