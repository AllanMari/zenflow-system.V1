<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesReportKernelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sales_report_page_loads_without_500()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::firstOrCreate(
            ['username' => 'test_admin_sales'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $response = $this->actingAs($admin)->get(route('admin.sales'));
        $response->assertStatus(200);
    }

    public function test_receptionist_cannot_access_admin_sales_report()
    {
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $rec = User::firstOrCreate(
            ['username' => 'test_rec_sales'],
            [
                'first_name' => 'Rec',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $rec->roles()->syncWithoutDetaching([$recRole->id]);

        $response = $this->actingAs($rec)->get(route('admin.sales'));
        $response->assertStatus(403);
    }

    public function test_admin_sales_report_page_loads_when_gemini_api_key_is_null()
    {
        config(['services.gemini.api_key' => null]);
        putenv('GEMINI_API_KEY');

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::firstOrCreate(
            ['username' => 'test_admin_sales_no_gemini'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $response = $this->actingAs($admin)->get(route('admin.sales'));
        $response->assertStatus(200);
    }
}
