<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        // Insert roles
        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'admin',
                'description' => 'Full access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'receptionist',
                'description' => 'Front desk operations',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'staff',
                'description' => 'Basic access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,  // ← next available ID
                'name' => 'customer',
                'description' => 'Customer with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Insert users
        DB::table('users')->insert([
            [
                'username' => 'allan',
                'first_name' => 'Allan Mari',
                'last_name' => 'Castigador',
                'password' => Hash::make('allanmari'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'allan1',
                'first_name' => 'Allan Mari',
                'last_name' => 'Castigador',
                'password' => Hash::make('allanmari1'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'allan2',
                'first_name' => 'Allan Mari',
                'last_name' => 'Castigador',
                'password' => Hash::make('allanmari2'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);


    }
}