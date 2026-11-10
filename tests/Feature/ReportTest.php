<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'data' => [[
                'id' => 1,
                'kode_order' => 'LDR-20261110-0001',
                'customer' => ['id' => 1, 'nama' => 'Rina Wulandari', 'nomor_telepon' => '081234567890'],
                'petugas' => ['id' => 1, 'name' => 'Siti'],
                'tanggal_masuk' => '2026-11-10 08:15:00',
                'estimasi_selesai' => '2026-11-12',
                'subtotal' => 47000,
                'diskon' => 0,
                'total' => 47000,
                'dibayar' => 20000,
                'sisa_bayar' => 27000,
                'status_pelunasan' => 'DP',
                'status' => 'diproses',
                'items' => [],
            ]],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => 1,
                'per_page' => 15,
                'total_nilai' => 47000,
                'total_dibayar' => 20000,
            ],
        ], $override);
    }

    public function test_laporan_menampilkan_data_dari_api(): void
    {
        Http::fake([
            '*/api/orders*' => Http::response($this->payload(), 200),
        ]);

        $this->actingAs($this->admin())
            ->get(route('orders.report'))
            ->assertOk()
            ->assertSee('LDR-20261110-0001', false)
            ->assertSee('Rina Wulandari', false)
            ->assertSee('Rp 47.000', false)
            ->assertSee('Rp 20.000', false)
            ->assertSee('DP', false);
    }

    public function test_laporan_memakai_dari_dan_sampai_default_hari_ini(): void
    {
        Http::fake([
            '*/api/orders*' => Http::response($this->payload(), 200),
        ]);

        $this->actingAs($this->admin())->get(route('orders.report'))->assertOk();

        $hariIni = Carbon::today()->toDateString();

        Http::assertSent(function ($request) use ($hariIni) {
            return str_contains($request->url(), '/api/orders')
                && $request['dari'] === $hariIni
                && $request['sampai'] === $hariIni;
        });
    }

    public function test_filter_periode_diteruskan_ke_api(): void
    {
        Http::fake([
            '*/api/orders*' => Http::response($this->payload(), 200),
        ]);

        $this->actingAs($this->admin())
            ->get(route('orders.report', ['dari' => '2026-11-01', 'sampai' => '2026-11-10']))
            ->assertOk();

        Http::assertSent(function ($request) {
            return $request['dari'] === '2026-11-01' && $request['sampai'] === '2026-11-10';
        });
    }

    public function test_laporan_tetap_tampil_saat_server_api_mati(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->actingAs($this->admin())
            ->get(route('orders.report'))
            ->assertOk()
            ->assertSee('Tidak ada pesanan pada periode ini', false)
            ->assertSee('Rp 0', false);
    }

    public function test_laporan_tetap_tampil_saat_api_mengembalikan_error(): void
    {
        Http::fake([
            '*/api/orders*' => Http::response(['message' => 'boom'], 500),
        ]);

        $this->actingAs($this->admin())
            ->get(route('orders.report'))
            ->assertOk()
            ->assertSee('Tidak ada pesanan pada periode ini', false);
    }

    public function test_laporan_butuh_login(): void
    {
        $this->get('/orders/report')->assertRedirect('/login');
    }

    public function test_struk_menampilkan_harga_snapshot_bukan_harga_master(): void
    {
        $service = Service::factory()->create([
            'nama_layanan' => 'Cuci Kering',
            'satuan' => 'kg',
            'harga' => 8000,
        ]);

        $order = Order::factory()->create([
            'customer_id' => Customer::factory(),
            'user_id' => $this->admin()->id,
            'subtotal' => 28000,
            'diskon' => 0,
            'total' => 28000,
            'status' => OrderStatus::Selesai,
        ]);

        $order->items()->create([
            'service_id' => $service->id,
            'qty' => 3.5,
            'harga_satuan' => 8000,
            'subtotal' => 28000,
        ]);

        // Harga master dinaikkan setelah pesanan dibuat.
        $service->update(['harga' => 20000]);

        $response = $this->actingAs($this->admin())->get(route('orders.struk', $order));

        $response->assertOk()
            ->assertSee('LDR-', false)
            ->assertSee('Rp 8.000', false)
            ->assertSee('Rp 28.000', false)
            // Harga baru tidak boleh muncul di struk lama.
            ->assertDontSee('Rp 20.000', false);
    }

    public function test_struk_tidak_memakai_layout_dengan_navbar(): void
    {
        $order = Order::factory()->create([
            'customer_id' => Customer::factory(),
            'user_id' => $this->admin()->id,
            'total' => 10000,
            'subtotal' => 10000,
        ]);

        $this->actingAs($this->admin())
            ->get(route('orders.struk', $order))
            ->assertOk()
            ->assertDontSee('class="appbar"', false)
            ->assertSee('Cetak', false);
    }

    public function test_struk_butuh_login(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.struk', $order))->assertRedirect('/login');
    }
}
