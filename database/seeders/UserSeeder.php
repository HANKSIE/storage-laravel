<?php

namespace Database\Seeders;

use App\Helpers\UrlHelper;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'faker',
            'email' => 'iamfaker@gmail.com',
            'password' => Hash::make('iamfaker'),
        ]);
        User::factory()->create([
            'name' => '123456789',
            'email' => '123456789@gmail.com',
            'password' => Hash::make('123456789'),
        ]);
        User::factory(10)->create();
    }
}
