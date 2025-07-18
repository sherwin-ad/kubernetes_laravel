<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // reset users table
        \Illuminate\Support\Facades\DB::table('users')->truncate();

        // make super admin user
        $user = new \App\Models\User();
        $user->name = 'Super Admin';
        $user->email = 'admin@email.com';
        $user->password = \Illuminate\Support\Facades\Hash::make('QazGW97spcvKoAW');
        $user->is_admin = true;
        $user->save();
    }
}
