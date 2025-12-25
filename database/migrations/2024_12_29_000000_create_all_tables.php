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
        // Create workspaces table
        if (!Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('logo')->nullable();
                $table->unsignedBigInteger('owner_id');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('slug');
            });
        }

        // Create users table (if not exists from Laravel default)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->integer('level')->default(0); // 0 = regular user, 1 = admin
                $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Add level if not exists (must be before workspace_id)
            if (!Schema::hasColumn('users', 'level')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->integer('level')->default(0)->after('password');
                });
            }
            // Add workspace_id to existing users table if not exists
            if (!Schema::hasColumn('users', 'workspace_id')) {
                // Check if level exists now (after potentially adding it above)
                $hasLevel = Schema::hasColumn('users', 'level');
                Schema::table('users', function (Blueprint $table) use ($hasLevel) {
                    // Add after level if it exists, otherwise after password
                    if ($hasLevel) {
                        $table->foreignId('workspace_id')->nullable()->after('level')->constrained()->onDelete('cascade');
                    } else {
                        $table->foreignId('workspace_id')->nullable()->after('password')->constrained()->onDelete('cascade');
                    }
                });
            }
        }

        // Create positions table
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id(); // Creates bigIncrements (unsigned big integer)
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('positions', 'workspace_id')) {
                Schema::table('positions', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->after('description')->constrained()->onDelete('cascade');
                });
            }
            // Add soft deletes if not exists
            if (!Schema::hasColumn('positions', 'deleted_at')) {
                Schema::table('positions', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // Create employees table
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('gender', 1); // L or P
                $table->date('birth_date');
                $table->string('photo', 50)->nullable(); // File ID reference
                $table->text('description');
                $table->unsignedBigInteger('position_id'); // Changed to unsignedBigInteger to match positions.id
                $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('position_id')->references('id')->on('positions')->onDelete('restrict');
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('employees', 'workspace_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->after('position_id')->constrained()->onDelete('cascade');
                });
            }
            // Add user_id if not exists
            if (!Schema::hasColumn('employees', 'user_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->after('workspace_id')->constrained()->onDelete('set null');
                });
            }
            // Add soft deletes if not exists
            if (!Schema::hasColumn('employees', 'deleted_at')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // Create activity_logs table
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('action'); // created, updated, deleted, restored
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['model_type', 'model_id']);
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('activity_logs', 'workspace_id')) {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
                });
            }
        }

        // Create files table
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('file_name');
                $table->string('file_path'); // Path in MinIO
                $table->string('mime_type')->nullable();
                $table->bigInteger('file_size')->nullable();
                $table->enum('file_type', ['document', 'photo'])->default('document');
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('employee_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('files', 'workspace_id')) {
                Schema::table('files', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->nullable()->after('uploaded_by')->constrained()->onDelete('cascade');
                });
            }
        }

        // Create assets table
        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('asset_tag')->unique()->nullable();
                $table->text('description')->nullable();
                $table->string('asset_type', 50);
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
                $table->string('image')->nullable(); // Path in MinIO
                $table->text('notes')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('employees')->onDelete('set null');
                $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
                $table->date('assigned_date')->nullable();
                $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('assets', 'workspace_id')) {
                Schema::table('assets', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->nullable()->after('assigned_date')->constrained()->onDelete('cascade');
                });
            }
        }

        // Create asset_assignments table
        if (!Schema::hasTable('asset_assignments')) {
            Schema::create('asset_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained()->onDelete('cascade');
                $table->foreignId('employee_id')->constrained()->onDelete('cascade');
                $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('returned_by')->nullable()->constrained('users')->onDelete('set null');
                $table->dateTime('assigned_at');
                $table->dateTime('returned_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        } else {
            // Add workspace_id if not exists
            if (!Schema::hasColumn('asset_assignments', 'workspace_id')) {
                Schema::table('asset_assignments', function (Blueprint $table) {
                    $table->foreignId('workspace_id')->nullable()->after('notes')->constrained()->onDelete('cascade');
                });
            }
        }

        // Create password_resets table (Laravel default)
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Create failed_jobs table (Laravel default)
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Create personal_access_tokens table (Laravel Sanctum)
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
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
        // Drop tables in reverse order (respecting foreign keys)
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('files');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_resets');
        // Note: We don't drop users table as it's a Laravel default
    }
};

