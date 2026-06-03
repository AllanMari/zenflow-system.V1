<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean out existing records to avoid duplicate key errors on redeploy
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_user')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Insert roles
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
                'id' => 4,
                'name' => 'customer',
                'description' => 'Customer with limited access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3. Insert users
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

        // 4. Query the database to get the IDs assigned to your new users
        $userAllan  = DB::table('users')->where('username', 'allan')->first();
        $userAllan1 = DB::table('users')->where('username', 'allan1')->first();
        $userAllan2 = DB::table('users')->where('username', 'allan2')->first();

        // 5. Connect users to roles via the role_user pivot table
        DB::table('role_user')->insert([
            [
                'user_id'    => $userAllan->id,
                'role_id'    => 1, // Admin role
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $userAllan1->id,
                'role_id'    => 2, // Receptionist role
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id'    => $userAllan2->id,
                'role_id'    => 3, // Staff role
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}