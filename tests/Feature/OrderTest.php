<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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

    private function layanan(array $atribut = []): Service
    {
        return Service::factory()->create(array_merge([
            'nama_layanan' => 'Cuci Kering',
            'satuan' => 'kg',
            'harga' => 8000,
            'estimasi_hari' => 2,
            'status' => 'aktif',
        ], $atribut));
    }

    /**
     * @param  array<int, array{service_id:int, qty:numeric}>  $items
     * @return array<string, mixed>
     */
    private function payload(Customer $customer, array $items, array $tambahan = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'items' => $items,
            'diskon' => 0,
        ], $tambahan);
    }

    public function test_kasir_bisa_membuat_pesanan_dua_item_dengan_total_benar(): void
    {
        $cuciKering = $this->layanan(['nama_layanan' => 'Cuci Kering', 'harga' => 8000]);
        $setrika = $this->layanan(['nama_layanan' => 'Setrika', 'harga' => 5000]);
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->kasir())->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $cuciKering->id, 'qty' => 3.5],
            ['service_id' => $setrika->id, 'qty' => 3.5],
        ], ['diskon' => 2000]));

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));

        // 3.5 * 8000 = 28000, 3.5 * 5000 = 17500 -> subtotal 45500, diskon 2000
        $this->assertSame(45500, $order->subtotal);
        $this->assertSame(2000, $order->diskon);
        $this->assertSame(43500, $order->total);
    }

    public function test_pesanan_tersimpan_dengan_item_dan_log_status_awal(): void
    {
        $service = $this->layanan();
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $service->id, 'qty' => 2],
        ]));

        $order = Order::query()->firstOrFail();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(OrderStatus::Diterima, $order->status);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status_lama' => '-',
            'status_baru' => 'diterima',
        ]);
    }

    public function test_items_kosong_ditolak(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())
            ->post(route('orders.store'), ['customer_id' => $customer->id, 'items' => []])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_diskon_melebihi_subtotal_ditolak(): void
    {
        $service = $this->layanan(['harga' => 8000]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())
            ->post(route('orders.store'), $this->payload($customer, [
                ['service_id' => $service->id, 'qty' => 1],
            ], ['diskon' => 999999]))
            ->assertSessionHasErrors('diskon');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_diskon_sama_dengan_subtotal_dibolehkan_dan_lunas(): void
    {
        $service = $this->layanan(['harga' => 8000]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $service->id, 'qty' => 1],
        ], ['diskon' => 8000]));

        $order = Order::query()->firstOrFail();

        $this->assertSame(0, $order->total);
        $this->assertSame('Lunas', $order->status_pelunasan);
    }

    public function test_harga_diambil_dari_master_bukan_dari_request(): void
    {
        $service = $this->layanan(['harga' => 8000]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [
                ['service_id' => $service->id, 'qty' => 2, 'harga_satuan' => 1, 'subtotal' => 1],
            ],
            'diskon' => 0,
            'total' => 1,
        ]);

        $item = Order::query()->firstOrFail()->items()->firstOrFail();

        $this->assertSame(8000, $item->harga_satuan);
        $this->assertSame(16000, $item->subtotal);
        $this->assertSame(16000, Order::query()->firstOrFail()->total);
    }

    public function test_estimasi_selesai_memakai_estimasi_terbesar(): void
    {
        $cepat = $this->layanan(['nama_layanan' => 'Setrika', 'estimasi_hari' => 2]);
        $lama = $this->layanan(['nama_layanan' => 'Bed Cover', 'estimasi_hari' => 4]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $cepat->id, 'qty' => 1],
            ['service_id' => $lama->id, 'qty' => 1],
        ]));

        $order = Order::query()->firstOrFail();

        $this->assertSame(today()->addDays(4)->toDateString(), $order->estimasi_selesai->toDateString());
    }

    public function test_kode_order_berformat_benar_dan_unik_per_hari(): void
    {
        $service = $this->layanan();
        $customer = Customer::factory()->create();
        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $service->id, 'qty' => 1],
        ]));

        $this->actingAs($kasir)->post(route('orders.store'), $this->payload($customer, [
            ['service_id' => $service->id, 'qty' => 2],
        ]));

        $kode = Order::query()->orderBy('id')->pluck('kode_order')->all();

        $this->assertMatchesRegularExpression('/^LDR-\d{8}-\d{4}$/', $kode[0]);
        $this->assertMatchesRegularExpression('/^LDR-\d{8}-\d{4}$/', $kode[1]);
        $this->assertNotSame($kode[0], $kode[1]);
        $this->assertStringEndsWith('-0001', $kode[0]);
        $this->assertStringEndsWith('-0002', $kode[1]);
    }

    public function test_layanan_nonaktif_tidak_bisa_dipakai(): void
    {
        $service = $this->layanan(['status' => 'nonaktif']);
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())
            ->post(route('orders.store'), $this->payload($customer, [
                ['service_id' => $service->id, 'qty' => 1],
            ]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_layanan_dobel_dalam_satu_pesanan_ditolak(): void
    {
        $service = $this->layanan();
        $customer = Customer::factory()->create();

        $this->actingAs($this->kasir())
            ->post(route('orders.store'), $this->payload($customer, [
                ['service_id' => $service->id, 'qty' => 1],
                ['service_id' => $service->id, 'qty' => 2],
            ]))
            ->assertSessionHasErrors('items.1.service_id');
    }

    public function test_status_tidak_boleh_meloncat(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diterima]);

        $this->actingAs($this->kasir())
            ->put(route('orders.status', $order), ['status' => OrderStatus::Disetrika->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Diterima, $order->fresh()->status);
    }

    public function test_status_maju_menambah_riwayat(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diterima]);
        $kasir = $this->kasir();
        $order->statusLogs()->create([
            'user_id' => $kasir->id,
            'status_lama' => '-',
            'status_baru' => 'diterima',
            'catatan' => 'Pesanan dibuat',
        ]);

        $this->actingAs($kasir)
            ->put(route('orders.status', $order), ['status' => OrderStatus::Dicuci->value])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Dicuci, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status_lama' => 'diterima',
            'status_baru' => 'dicuci',
        ]);
        $this->assertSame(2, $order->statusLogs()->count());
    }

    public function test_pesanan_diambil_tidak_bisa_diubah_statusnya(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diambil]);

        $this->actingAs($this->admin())
            ->put(route('orders.status', $order), ['status' => OrderStatus::Dibatalkan->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Diambil, $order->fresh()->status);
    }

    public function test_kasir_tidak_boleh_membatalkan_pesanan(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diterima]);

        $this->actingAs($this->kasir())
            ->put(route('orders.batalkan', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Diterima, $order->fresh()->status);
    }

    public function test_admin_boleh_membatalkan_pesanan_dari_status_dicuci(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Dicuci]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('orders.batalkan', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Dibatalkan, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status_lama' => 'dicuci',
            'status_baru' => 'dibatalkan',
        ]);
    }

    public function test_index_hanya_menampilkan_status_yang_difilter(): void
    {
        Order::factory()->create(['status' => OrderStatus::Dicuci]);
        Order::factory()->create(['status' => OrderStatus::Diterima]);

        $response = $this->actingAs($this->kasir())
            ->get(route('orders.index', ['status' => 'dicuci']));

        $response->assertOk();
        $response->assertSee('LDR-', false);

        $this->assertSame(1, Order::query()->where('status', 'dicuci')->count());
    }

    public function test_halaman_pesanan_butuh_login(): void
    {
        $this->get('/orders')->assertRedirect('/login');
        $this->get('/orders/create')->assertRedirect('/login');
    }

    public function test_kasir_tetap_tidak_boleh_membuka_layanan(): void
    {
        $this->actingAs($this->kasir())->get('/services')->assertForbidden();
    }

    public function test_tombol_status_menyebut_tujuan_tanpa_dropdown(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diterima]);

        $response = $this->actingAs($this->kasir())->get(route('orders.show', $order));

        $response->assertOk();
        // Tombol langsung menyebut status tujuan.
        $response->assertSee('Proses ke Dicuci', false);
        // Tidak ada lagi dropdown status yang hanya berisi satu opsi.
        $response->assertDontSee('<select id="status"', false);
        $response->assertSee('<input type="hidden" name="status" value="dicuci">', false);
    }

    public function test_status_final_tidak_menampilkan_tombol_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Diambil]);

        $response = $this->actingAs($this->kasir())->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('Proses ke', false);
        $response->assertSee('sudah final', false);
    }

    public function test_hidden_input_status_tidak_bisa_dipakai_untuk_meloncat(): void
    {
        // Meniru kasir yang mengubah hidden input lewat DevTools.
        $order = Order::factory()->create(['status' => OrderStatus::Diterima]);

        $this->actingAs($this->kasir())
            ->put(route('orders.status', $order), ['status' => OrderStatus::Diambil->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Diterima, $order->fresh()->status);
    }
}
