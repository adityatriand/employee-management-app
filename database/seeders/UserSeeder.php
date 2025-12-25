<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user first (without workspace_id initially)
        $admin = User::firstOrCreate(
            ['email' => 'admin@hrms.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@hrms.com',
                'password' => Hash::make('admin123'),
                'level' => 1, // Admin level
                'workspace_id' => null, // Will be set after workspace is created
            ]
        );

        // Create a demo workspace with admin as owner
        $workspace = \App\Models\Workspace::firstOrCreate(
            ['slug' => 'demo-workspace'],
            [
                'name' => 'Demo Workspace',
                'slug' => 'demo-workspace',
                'owner_id' => $admin->id,
            ]
        );

        // Update admin user with workspace_id
        if (!$admin->workspace_id) {
            $admin->update(['workspace_id' => $workspace->id]);
        }

        // Create MinIO bucket for demo workspace
        try {
            $bucketService = new \App\Services\MinioBucketService();
            $bucketName = $bucketService->getBucketName($workspace->slug);
            $bucketService->createBucket($bucketName);
        } catch (\Exception $e) {
            Log::warning('Failed to create MinIO bucket for demo workspace: ' . $e->getMessage());
        }

        // Create regular user
        User::firstOrCreate(
            ['email' => 'user@hrms.com'],
            [
                'name' => 'User Test',
                'email' => 'user@hrms.com',
                'password' => Hash::make('user123'),
                'level' => 0, // Regular user
                'workspace_id' => $workspace->id,
            ]
        );
    }
}

