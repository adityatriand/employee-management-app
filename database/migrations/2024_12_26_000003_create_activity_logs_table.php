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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('model_type'); // e.g., 'App\Models\Employee'
            $table->unsignedBigInteger('model_id');
            $table->string('action'); // 'created', 'updated', 'deleted', 'restored'
            $table->json('old_values')->nullable(); // Old values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->text('description')->nullable(); // Human-readable description
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};

