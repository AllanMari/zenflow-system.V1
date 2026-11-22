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
        echo "\nADMIN SALES STATUS: " . $response->status() . "\n";
        if ($response->status() >= 400) {
            echo "RESPONSE BODY:\n" . substr($response->getContent(), 0, 1000) . "\n";
        }
        $response->assertStatus(200);
    }

    public function test_receptionist_sales_report_page_loads_when_authorized()
    {
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $rec = User::firstOrCreate(
            ['username' => 'test_rec_sales'],
            [
                'first_name' => 'Rec',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
                'can_view_sales_report' => true,
            ]
        );
        $rec->roles()->syncWithoutDetaching([$recRole->id]);
        $rec->can_view_sales_report = true;
        $rec->save();

        $response = $this->actingAs($rec)->get(route('receptionist.sales'));
        echo "\nREC AUTHORIZED STATUS: " . $response->status() . "\n";
        if ($response->status() >= 400) {
            echo "RESPONSE BODY:\n" . substr($response->getContent(), 0, 1000) . "\n";
        }
        $response->assertStatus(200);
    }

    public function test_receptionist_sales_report_page_forbidden_when_unauthorized()
    {
        $recRole = Role::firstOrCreate(['name' => 'receptionist'], ['label' => 'Receptionist']);
        $rec = User::firstOrCreate(
            ['username' => 'test_rec_sales2'],
            [
                'first_name' => 'Rec2',
                'last_name' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
                'can_view_sales_report' => false,
            ]
        );
        $rec->roles()->syncWithoutDetaching([$recRole->id]);
        $rec->can_view_sales_report = false;
        $rec->save();

        $response = $this->actingAs($rec)->get(route('receptionist.sales'));
        echo "\nREC UNAUTHORIZED STATUS: " . $response->status() . "\n";
        $response->assertRedirect(route('receptionist.dashboard'));
        $response->assertSessionHas('error');
    }
}
