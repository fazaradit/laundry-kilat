<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint tulis API.
 *
 * Yang diuji di sini bukan validasi bentuk form (sudah diuji lewat web),
 * melainkan tiga hal yang khas API:
 *
 * 1. identitas pelaku dikirim eksplisit lewat `user_id`,
 * 2. aturan bisnis tetap ditegakkan di service, bukan dilewati karena
 *    pemanggilnya bukan form,
 * 3. respons memakai JSON yang rapi, bukan redirect seperti versi web.
 */
class ApiWriteTest extends TestCase
{
    use RefreshDatabase;

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

    private function pesanan(Service $service, array $atribut = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function kasir(): User
    {
        return User::factory()->create(['role' => 'kasir']);
    }

    // ---------------------------------------------------------------- POST

    public function test_buat_pesanan_lewat_api_mengembalikan_201(): void
    {
        $service = $this->layanan();
        $kasir = $this->kasir();
        $customer = Customer::factory()->create();

        $this->postJson('/api/orders', [
            'user_id' => $kasir->id,
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'qty' => 2]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'diterima')
            ->assertJsonPath('data.petugas.id', $kasir->id)
            ->assertJsonPath('data.items.0.harga_satuan', 8000)
            ->assertJsonPath('data.total', 16000);
    }

    public function test_total_dihitung_server_bukan_dari_input(): void
    {
        $service = $this->layanan();
        $customer = Customer::factory()->create();

        $this->postJson('/api/orders', [
            'user_id' => $this->kasir()->id,
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'qty' => 2]],
            'diskon' => 1000,
            // Nilai karangan klien harus diabaikan.
            'subtotal' => 1,
            'total' => 1,
            'status' => 'selesai',
        ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', 16000)
            ->assertJsonPath('data.total', 15000)
            ->assertJsonPath('data.status', 'diterima');
    }

    public function test_buat_pesanan_tanpa_user_id_ditolak(): void
    {
        $service = $this->layanan();
        $customer = Customer::factory()->create();

        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'qty' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_buat_pesanan_dengan_user_tidak_dikenal_ditolak(): void
    {
        $service = $this->layanan();

        $this->postJson('/api/orders', [
            'user_id' => 99999,
            'customer_id' => Customer::factory()->create()->id,
            'items' => [['service_id' => $service->id, 'qty' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_buat_pesanan_dengan_layanan_nonaktif_ditolak(): void
    {
        $service = $this->layanan(['status' => 'nonaktif']);

        $this->postJson('/api/orders', [
            'user_id' => $this->kasir()->id,
            'customer_id' => Customer::factory()->create()->id,
            'items' => [['service_id' => $service->id, 'qty' => 1]],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_pesan_validasi_api_memakai_bahasa_indonesia(): void
    {
        // Form Request API mewarisi messages() versi web, jadi pesannya tidak
        // kembali ke bawaan Laravel berbahasa Inggris.
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422);

        $this->assertStringContainsString(
            'Pelanggan wajib dipilih.',
            $response->json('errors.customer_id.0')
        );
    }

    // ------------------------------------------------------------ PUT status

    public function test_ubah_status_sah_mengembalikan_200_dan_mencatat_log(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->putJson("/api/orders/{$order->id}/status", [
            'user_id' => $this->kasir()->id,
            'status' => 'diproses',
            'catatan' => 'Mulai dikerjakan',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'diproses');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'diproses',
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status_lama' => 'diterima',
            'status_baru' => 'diproses',
        ]);
    }

    public function test_kasir_tidak_boleh_membatalkan_pesanan_lewat_api(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->putJson("/api/orders/{$order->id}/status", [
            'user_id' => $this->kasir()->id,
            'status' => 'dibatalkan',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Pembatalan pesanan hanya dapat dilakukan oleh Admin.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'diterima',
        ]);
    }

    public function test_admin_boleh_membatalkan_pesanan_lewat_api(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->putJson("/api/orders/{$order->id}/status", [
            'user_id' => $this->admin()->id,
            'status' => 'dibatalkan',
            'catatan' => 'Pelanggan membatalkan',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan');
    }

    public function test_lompat_status_ditolak(): void
    {
        // diterima -> selesai bukan satu langkah sah.
        $order = $this->pesanan($this->layanan());

        $this->putJson("/api/orders/{$order->id}/status", [
            'user_id' => $this->admin()->id,
            'status' => 'selesai',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_ubah_status_tanpa_user_id_ditolak(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->putJson("/api/orders/{$order->id}/status", ['status' => 'diproses'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_ubah_status_pesanan_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->putJson('/api/orders/99999/status', [
            'user_id' => $this->admin()->id,
            'status' => 'diproses',
        ])->assertNotFound();
    }

    // ---------------------------------------------------------- POST payment

    public function test_catat_pembayaran_tunai_mengembalikan_201_dan_kembalian(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->postJson("/api/orders/{$order->id}/payments", [
            'user_id' => $this->kasir()->id,
            'metode' => 'tunai',
            'jumlah_bayar' => 10000,
            'uang_diterima' => 20000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.jumlah_bayar', 10000)
            ->assertJsonPath('data.kembalian', 10000)
            ->assertJsonPath('data.order.dibayar', 10000)
            ->assertJsonPath('data.order.sisa_bayar', 18000);
    }

    public function test_pembayaran_melebihi_sisa_tagihan_ditolak(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->postJson("/api/orders/{$order->id}/payments", [
            'user_id' => $this->kasir()->id,
            'metode' => 'transfer',
            'jumlah_bayar' => 999999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jumlah_bayar');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_tunai_tanpa_uang_diterima_ditolak(): void
    {
        $order = $this->pesanan($this->layanan());

        $this->postJson("/api/orders/{$order->id}/payments", [
            'user_id' => $this->kasir()->id,
            'metode' => 'tunai',
            'jumlah_bayar' => 5000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('uang_diterima');
    }

    public function test_pembayaran_untuk_pesanan_dibatalkan_ditolak(): void
    {
        $order = $this->pesanan($this->layanan(), ['status' => OrderStatus::Dibatalkan]);

        $this->postJson("/api/orders/{$order->id}/payments", [
            'user_id' => $this->kasir()->id,
            'metode' => 'qris',
            'jumlah_bayar' => 5000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('jumlah_bayar');
    }

    // -------------------------------------------------------- DELETE layanan

    public function test_layanan_yang_sudah_dipakai_pesanan_tidak_bisa_dihapus(): void
    {
        $service = $this->layanan();
        $this->pesanan($service);

        $this->deleteJson("/api/services/{$service->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('service');

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_layanan_yang_belum_dipakai_bisa_dihapus(): void
    {
        $service = $this->layanan();

        $this->deleteJson("/api/services/{$service->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Layanan "Cuci Kering" berhasil dihapus.');

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_hapus_layanan_tidak_ditemukan_mengembalikan_404(): void
    {
        $this->deleteJson('/api/services/99999')->assertNotFound();
    }
}
