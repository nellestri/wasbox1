<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Service;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;

class PerLoadServiceAndAddonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_load_service_with_addons_calculates_and_persists()
    {
        // Seed minimal data
        $branch = Branch::factory()->create();
        $staff = User::factory()->create(['branch_id' => $branch->id]);
        $customer = Customer::factory()->create(['preferred_branch_id' => $branch->id]);

        // Create per-load service
        $service = Service::factory()->create([
            'pricing_type' => 'per_load',
            'price_per_load' => 99.00,
        ]);

        // Add-ons
        $addon1 = AddOn::create(['name' => 'Softener', 'price' => 10.00]);
        $addon2 = AddOn::create(['name' => 'Bleach', 'price' => 5.00]);

        $this->actingAs($staff);

        $response = $this->post(route('staff.orders.store'), [
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'weight' => null, // not required for per-load
            'addons' => [$addon1->id, $addon2->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'service_id' => $service->id,
            'subtotal' => 99.00,
            'addons_total' => 15.00,
            'total_amount' => 114.00,
        ]);

        $this->assertDatabaseHas('order_addon', [
            'addon_id' => $addon1->id,
        ]);

        $this->assertDatabaseHas('order_addon', [
            'addon_id' => $addon2->id,
        ]);
    }
}
