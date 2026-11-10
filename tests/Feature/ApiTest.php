<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function pesanan(array $atribut = []): Order
    {
        $service = Service::factory()->create([
            'nama_layanan' => 'Cuci Kering',
            'satuan' => 'kg',
            'harga' => 8000,
            'estimasi_hari' => 2,
        ]);

        $order = Order::factory()->create(array_merge([
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'tanggal_masuk' => now()->startOfDay()->addHours(8),
            'subtotal' => 28000,
            'diskon' => 0,
            'total' => 28000,
            'status' => OrderStatus::Diterima,
        ], $atribut));

        $order->items()->create([
            'service_id' => $service->id,
            'qty' => 3.5,
            'harga_satuan' => 8000,
            'subtotal' => 28000,
        ]);

        return $order;
    }

    public function test_api_layanan_mengembalikan_daftar(): void
    {
        Service::factory()->count(3)->create();

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'nama_layanan', 'satuan', 'harga', 'estimasi_hari', 'status']],
            ]);
    }

    public function test_api_layanan_bisa_disaring_status(): void
    {
        Service::factory()->count(2)->create(['status' => 'aktif']);
        Service::factory()->create(['status' => 'nonaktif']);

        $this->getJson('/api/services?status=aktif')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_api_orders_mengembalikan_struktur_sesuai_prd(): void
    {
        $order = $this->pesanan();

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.0.kode_order', $order->kode_order)
            ->assertJsonPath('data.0.status', 'diterima')
            ->assertJsonStructure([
                'data' => [[
                    'id', 'kode_order',
                    'customer' => ['id', 'nama', 'nomor_telepon'],
                    'petugas' => ['id', 'name'],
                    'tanggal_masuk', 'estimasi_selesai',
                    'subtotal', 'diskon', 'total',
                    'dibayar', 'sisa_bayar', 'status_pelunasan',
                    'status',
                    'items' => [['service', 'satuan', 'qty', 'harga_satuan', 'subtotal']],
                ]],
                'meta' => ['current_page', 'last_page', 'total', 'total_nilai', 'total_dibayar'],
            ]);
    }

    public function test_api_orders_menyaring_status(): void
    {
        $this->pesanan(['status' => OrderStatus::Diproses]);
        $this->pesanan(['status' => OrderStatus::Diterima]);

        $this->getJson('/api/orders?status=diproses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'diproses');
    }

    public function test_status_tidak_dikenal_diabaikan(): void
    {
        $this->pesanan();
        $this->pesanan();

        $this->getJson('/api/orders?status=entah')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_api_orders_menyaring_rentang_tanggal(): void
    {
        $this->pesanan(['tanggal_masuk' => now()->subDays(10)->startOfDay()->addHours(9)]);
        $this->pesanan(['tanggal_masuk' => now()->startOfDay()->addHours(9)]);

        $dari = now()->subDays(2)->toDateString();
        $sampai = now()->toDateString();

        $this->getJson("/api/orders?dari={$dari}&sampai={$sampai}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_rentang_tanggal_terbalik_ditolak(): void
    {
        $this->getJson('/api/orders?dari=2026-11-10&sampai=2026-11-01')
            ->assertStatus(422);
    }

    public function test_meta_total_nilai_menghitung_seluruh_periode_bukan_hanya_satu_halaman(): void
    {
        // 20 pesanan, per_page 5: total harus tetap jumlah semuanya.
        Order::factory()->count(20)->create([
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'subtotal' => 10000,
            'total' => 10000,
            'status' => OrderStatus::Diterima,
        ]);

        $this->getJson('/api/orders?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.total_nilai', 200000);
    }

    public function test_detail_pesanan_mengembalikan_satu_pesanan(): void
    {
        $order = $this->pesanan();

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.items.0.harga_satuan', 8000);
    }

    public function test_detail_pesanan_tidak_ditemukan_mengembalikan_404_json(): void
    {
        $this->getJson('/api/orders/99999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_api_stats_mengembalikan_angka_aktual(): void
    {
        $this->pesanan();
        $this->pesanan(['status' => OrderStatus::Selesai]);

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('pesanan_hari_ini', 2)
            ->assertJsonPath('pesanan_aktif', 1);
    }
}
