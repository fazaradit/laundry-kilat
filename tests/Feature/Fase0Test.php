<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fase0Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => 'kasir']);
    }

    public function test_guest_cannot_access_protected_pages(): void
    {
        $this->get('/customers')->assertRedirect('/login');
        $this->get('/services')->assertRedirect('/login');
    }

    public function test_kasir_is_blocked_from_services_page(): void
    {
        $this->actingAs($this->kasir())
            ->get('/services')
            ->assertForbidden();
    }

    public function test_admin_can_open_services_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/services')
            ->assertOk();
    }

    public function test_customer_phone_must_start_with_zero(): void
    {
        $this->actingAs($this->admin())
            ->post('/customers', [
                'nama' => 'Uji Format',
                'nomor_telepon' => '12345',
            ])
            ->assertSessionHasErrors('nomor_telepon');

        $this->assertSame(0, Customer::count());
    }

    public function test_customer_phone_is_normalized_before_saving(): void
    {
        $this->actingAs($this->admin())
            ->post('/customers', [
                'nama' => 'Pelanggan Uji',
                'nomor_telepon' => '0812-3456-7890',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'nama' => 'Pelanggan Uji',
            'nomor_telepon' => '081234567890',
        ]);
    }

    public function test_customer_with_orders_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();

        $customer->orders()->create([
            'kode_order' => 'LDR-20261110-0001',
            'user_id' => $this->admin()->id,
            'tanggal_masuk' => now(),
            'estimasi_selesai' => now()->addDays(2),
            'subtotal' => 0,
            'diskon' => 0,
            'total' => 0,
            'status' => 'diterima',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_service_price_must_be_at_least_500(): void
    {
        $this->actingAs($this->admin())
            ->post('/services', [
                'nama_layanan' => 'Harga Nol',
                'satuan' => 'kg',
                'harga' => 0,
                'estimasi_hari' => 2,
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('harga');

        $this->assertSame(0, Service::count());
    }

    public function test_service_used_by_order_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();

        $order = $customer->orders()->create([
            'kode_order' => 'LDR-20261110-0002',
            'user_id' => $admin->id,
            'tanggal_masuk' => now(),
            'estimasi_selesai' => now()->addDays(2),
            'subtotal' => 7000,
            'diskon' => 0,
            'total' => 7000,
            'status' => 'diterima',
        ]);

        $order->items()->create([
            'service_id' => $service->id,
            'qty' => 1,
            'harga_satuan' => 7000,
            'subtotal' => 7000,
        ]);

        $this->actingAs($admin)
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_order_status_only_moves_forward(): void
    {
        $this->assertTrue(OrderStatus::Diterima->bolehPindahKe(OrderStatus::Dicuci));
        $this->assertFalse(OrderStatus::Diterima->bolehPindahKe(OrderStatus::Disetrika));
        $this->assertFalse(OrderStatus::Diambil->bolehPindahKe(OrderStatus::Dicuci));
        $this->assertTrue(OrderStatus::Diambil->sudahFinal());
        $this->assertSame(OrderStatus::Dicuci, OrderStatus::Diterima->berikutnya());
    }

    public function test_stats_api_returns_expected_keys(): void
    {
        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonStructure([
                'pesanan_hari_ini',
                'pesanan_aktif',
                'pendapatan_hari_ini',
                'total_pelanggan',
            ]);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->admin())
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_page_has_no_appbar(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('class="appbar"', false);
    }

    public function test_pagination_nav_does_not_use_appbar_class(): void
    {
        Customer::factory()->count(11)->create();

        $response = $this->actingAs($this->admin())->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('<nav class="pagination">', false);
        $response->assertDontSee('<nav class="appbar pagination">', false);
    }
}
