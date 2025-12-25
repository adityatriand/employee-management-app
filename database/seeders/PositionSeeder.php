<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Get or create demo workspace
        $workspace = \App\Models\Workspace::where('slug', 'demo-workspace')->first();
        if (!$workspace) {
            $this->command->warn('Demo workspace not found. Please run UserSeeder first.');
            return;
        }

        $positions = [
            'Direktur',
            'Manajer',
            'Sekretaris',
            'Supervisor',
            'Staff',
            'Koordinator',
            'Kepala Divisi',
            'Asisten Manajer',
        ];

        foreach ($positions as $position) {
            \App\Models\Position::firstOrCreate(
                [
                    'name' => $position,
                    'workspace_id' => $workspace->id,
                ],
                [
                    'name' => $position,
                    'description' => $faker->sentence(6),
                    'workspace_id' => $workspace->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
