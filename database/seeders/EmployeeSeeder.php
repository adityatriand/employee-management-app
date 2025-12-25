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

        // Get position IDs
        $positionIds = DB::table('jabatan')->pluck('id')->toArray();

        if (empty($positionIds)) {
            $this->command->warn('No positions found. Please run PositionSeeder first.');
            return;
        }

        // Create 20 employees
        for ($i = 0; $i < 20; $i++) {
            $gender = $faker->randomElement(['L', 'P']);
            $genderName = $gender === 'L' ? 'male' : 'female';

            DB::table('pegawai')->insert([
                'name' => $faker->name($genderName),
                'gender' => $gender,
                'birth_date' => $faker->date('Y-m-d', '-25 years', '-18 years'),
                'position_id' => $faker->randomElement($positionIds),
                'photo' => '', // You can add photo upload logic here
                'description' => $faker->sentence(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
