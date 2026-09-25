<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Courier;
use App\Models\District;
use App\Models\Product;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductionEcommerceFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    public function test_full_ecommerce_and_logistics_pipeline(): void
    {
        $provinceRes = $this->getJson('/api/locations/provinces');
        $provinceRes->assertStatus(200)->assertJson(['success' => true]);

        $western = Province::where('name', 'Western')->first();
        $this->assertNotNull($western, 'Western province must exist');

        $colomboDistrict = District::where('name', 'Colombo')->first();
        $this->assertNotNull($colomboDistrict, 'Colombo district must exist');

        $citiesRes = $this->getJson('/api/locations/cities?district_id=' . $colomboDistrict->id);
        $citiesRes->assertStatus(200)->assertJson(['success' => true]);

        // 2. Shipping Methods & Calculation
        $methodsRes = $this->getJson('/api/shipping/methods');
        $methodsRes->assertStatus(200)->assertJson(['success' => true]);

        $standardMethod = ShippingMethod::where('status', true)->first();
        $this->assertNotNull($standardMethod, 'At least one active shipping method must exist');

        $calcRes = $this->postJson('/api/shipping/calculate', [
            'shipping_method_id' => $standardMethod->id,
            'district_id' => $colomboDistrict->id,
            'subtotal' => 25000,
        ]);
        $calcRes->assertStatus(200)->assertJson(['success' => true]);

        // 3. Customer Registration & Authentication
        $email = 'customer_' . Str::random(6) . '@example.com';
        $regRes = $this->postJson('/api/auth/register', [
            'name' => 'Ranil Fernando',
            'email' => $email,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'phone' => '0771234567',
        ]);
        $regRes->assertStatus(201)->assertJson(['success' => true]);
        $token = $regRes->json('data.token');
        $customerId = $regRes->json('data.user.id');

        // 4. Authenticated Customer Profile & Address Management
        $profileRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/profile');
        $profileRes->assertStatus(200)->assertJson(['success' => true]);

        $addressRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer/addresses', [
                'type' => 'Home',
                'full_name' => 'Ranil Fernando',
                'phone' => '0771234567',
                'address_line_1' => 'No. 42 Galle Road',
                'province_id' => $western->id,
                'district_id' => $colomboDistrict->id,
                'postal_code' => '00300',
                'is_default' => true,
            ]);
        $addressRes->assertStatus(201)->assertJson(['success' => true]);

        // 5. Product Stock & Atomic Checkout
        $product = Product::where('status', 'active')->where('stock_quantity', '>', 3)->first();
        $this->assertNotNull($product, 'Active product with stock needed for test');
        $initialStock = $product->stock_quantity;

        $idempotencyKey = (string) Str::uuid();
        $checkoutPayload = [
            'customer_name' => 'Ranil Fernando',
            'customer_email' => $email,
            'customer_phone' => '0771234567',
            'shipping_address' => 'No. 42 Galle Road',
            'province' => 'Western',
            'district' => 'Colombo',
            'city' => 'Colombo 03',
            'postal_code' => '00300',
            'payment_method' => 'COD',
            'shipping_method_id' => $standardMethod->id,
            'idempotency_key' => $idempotencyKey,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->selling_price,
                ]
            ],
        ];

        $checkoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/checkout', $checkoutPayload);
        $checkoutRes->assertStatus(201)->assertJson(['success' => true]);
        $orderId = $checkoutRes->json('data.order.id');
        $this->assertNotNull($orderId);

        // Verify stock decremented
        $product->refresh();
        $this->assertEquals($initialStock - 1, $product->stock_quantity);

        // Verify Shipment record was automatically generated
        $this->assertDatabaseHas('shipments', [
            'order_id' => $orderId,
            'status' => 'Pending',
        ]);

        // 6. Customer Order View
        $custOrderRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/orders/' . $orderId);
        $custOrderRes->assertStatus(200)->assertJson(['success' => true]);

        // 7. Admin Logistics Dispatch
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'email' => 'admin_test@example.com',
                'password' => bcrypt('AdminPass123!'),
                'role' => 'admin',
            ]);
        }
        $adminToken = $admin->createToken('admin-test')->plainTextToken;

        $courier = Courier::where('status', true)->first();
        $this->assertNotNull($courier, 'Courier needed for dispatch');

        // Fetch shipments list as admin
        $shipmentsRes = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/admin/shipments');
        $shipmentsRes->assertStatus(200)->assertJson(['success' => true]);

        $shipmentId = $checkoutRes->json('data.order.shipment.id');
        $this->assertNotNull($shipmentId);

        // Assign courier
        $assignRes = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/shipments/{$shipmentId}/assign-courier", [
                'courier_id' => $courier->id,
                'tracking_number' => 'DOMEX-TEST-998811',
            ]);
        $assignRes->assertStatus(200)->assertJson(['success' => true]);

        // Update shipping status to In Transit
        $statusRes = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson("/api/admin/shipments/{$shipmentId}/status", [
                'status' => 'In Transit',
                'notes' => 'Handed over to Domex driver.',
            ]);
        $statusRes->assertStatus(200)->assertJson(['success' => true]);

        // 8. PDF Downloads (Shipping Label & Invoice)
        $labelRes = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->get("/api/admin/shipments/{$shipmentId}/label");
        $labelRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $labelRes->headers->get('content-type'));

        $invoiceRes = $this->get("/api/orders/{$orderId}/invoice");
        $invoiceRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $invoiceRes->headers->get('content-type'));
    }
}
