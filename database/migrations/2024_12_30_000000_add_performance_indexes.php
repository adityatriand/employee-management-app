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
        // Add indexes for frequently queried columns
        Schema::table('employees', function (Blueprint $table) {
            // Index for workspace_id (most common filter)
            if (!$this->hasIndex('employees', 'employees_workspace_id_index')) {
                $table->index('workspace_id', 'employees_workspace_id_index');
            }
            // Composite index for workspace_id + created_at (for sorting)
            if (!$this->hasIndex('employees', 'employees_workspace_created_index')) {
                $table->index(['workspace_id', 'created_at'], 'employees_workspace_created_index');
            }
            // Index for position_id (filtering)
            if (!$this->hasIndex('employees', 'employees_position_id_index')) {
                $table->index('position_id', 'employees_position_id_index');
            }
            // Index for user_id (for user-employee relationship)
            if (!$this->hasIndex('employees', 'employees_user_id_index')) {
                $table->index('user_id', 'employees_user_id_index');
            }
        });

        Schema::table('positions', function (Blueprint $table) {
            if (!$this->hasIndex('positions', 'positions_workspace_id_index')) {
                $table->index('workspace_id', 'positions_workspace_id_index');
            }
        });

        Schema::table('files', function (Blueprint $table) {
            if (!$this->hasIndex('files', 'files_workspace_id_index')) {
                $table->index('workspace_id', 'files_workspace_id_index');
            }
            if (!$this->hasIndex('files', 'files_employee_id_index')) {
                $table->index('employee_id', 'files_employee_id_index');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (!$this->hasIndex('assets', 'assets_workspace_id_index')) {
                $table->index('workspace_id', 'assets_workspace_id_index');
            }
            if (!$this->hasIndex('assets', 'assets_assigned_to_index')) {
                $table->index('assigned_to', 'assets_assigned_to_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_workspace_id_index')) {
                $table->index('workspace_id', 'users_workspace_id_index');
            }
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->hasIndex('activity_logs', 'activity_logs_workspace_id_index')) {
                $table->index('workspace_id', 'activity_logs_workspace_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_workspace_id_index');
            $table->dropIndex('employees_workspace_created_index');
            $table->dropIndex('employees_position_id_index');
            $table->dropIndex('employees_user_id_index');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex('positions_workspace_id_index');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('files_workspace_id_index');
            $table->dropIndex('files_employee_id_index');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_workspace_id_index');
            $table->dropIndex('assets_assigned_to_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_workspace_id_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_workspace_id_index');
        });
    }

    /**
     * Check if an index exists
     *
     * @param string $table
     * @param string $indexName
     * @return bool
     */
    private function hasIndex($table, $indexName)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
};

