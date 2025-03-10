<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(1)->make()->each(function($user){
            $user->email = "ilyass@gmail.com";
            $user->role_id = RoleEnum::ADMIN;
            $user->save();
        });
    }
}
