<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
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

        // Get position IDs for this workspace
        $positionIds = \App\Models\Position::where('workspace_id', $workspace->id)->pluck('id')->toArray();

        if (empty($positionIds)) {
            $this->command->warn('No positions found. Please run PositionSeeder first.');
            return;
        }

        // Create 20 employees
        for ($i = 0; $i < 20; $i++) {
            $gender = $faker->randomElement(['L', 'P']);
            $genderName = $gender === 'L' ? 'male' : 'female';

            \App\Models\Employee::create([
                'name' => $faker->name($genderName),
                'gender' => $gender,
                'birth_date' => $faker->date('Y-m-d', '-25 years', '-18 years'),
                'position_id' => $faker->randomElement($positionIds),
                'photo' => '', // You can add photo upload logic here
                'description' => $faker->sentence(10),
                'workspace_id' => $workspace->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
