<?php

use Illuminate\Database\Seeder;

class PositionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('positions')->insert([
            'id' => "1",
            'name' => "Security",
        ]);

        DB::table('positions')->insert([
            'id' => "2",
            'name' => "Designer",
        ]);

        DB::table('positions')->insert([
            'id' => "3",
            'name' => "Content manager",
        ]);

        DB::table('positions')->insert([
            'id' => "4",
            'name' => "Lawyer",
        ]);
    }
}
