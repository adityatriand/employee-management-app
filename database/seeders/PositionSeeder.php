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
            DB::table('positions')->insert([
                'name' => $position,
                'description' => $faker->sentence(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
