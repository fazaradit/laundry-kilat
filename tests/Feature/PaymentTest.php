<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::factory()->create(['role' => 'kasir']);
    }

    private function pesanan(int $total = 45000): Order
    {
        return Order::factory()->create([
            'customer_id' => Customer::factory(),
            'total' => $total,
            'subtotal' => $total,
            'status' => OrderStatus::Diterima,
        ]);
    }

    public function test_dp_menyisakan_sisa_dan_berstatus_dp(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 20000,
            ])
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();

        $this->assertSame(20000, $order->dibayar);
        $this->assertSame(25000, $order->sisa_bayar);
        $this->assertSame('DP', $order->status_pelunasan);
    }

    public function test_pembayaran_melebihi_sisa_ditolak(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 20000,
            ]);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 30000,
            ])
            ->assertSessionHasErrors('jumlah_bayar');

        $this->assertSame(20000, $order->fresh()->dibayar);
        $this->assertCount(1, $order->fresh()->payments);
    }

    public function test_pelunasan_penuh_mengubah_status_menjadi_lunas(): void
    {
        $order = $this->pesanan(45000);
        $kasir = $this->kasir();

        $this->actingAs($kasir)->post(route('orders.payments.store', $order), [
            'metode' => 'transfer',
            'jumlah_bayar' => 20000,
        ]);

        $this->actingAs($kasir)->post(route('orders.payments.store', $order), [
            'metode' => 'transfer',
            'jumlah_bayar' => 25000,
        ])->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(45000, $order->dibayar);
        $this->assertSame(0, $order->sisa_bayar);
        $this->assertSame('Lunas', $order->status_pelunasan);
    }

    public function test_pesanan_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())->post(route('orders.payments.store', $order), [
            'metode' => 'transfer',
            'jumlah_bayar' => 45000,
        ]);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 1000,
            ])
            ->assertSessionHasErrors('jumlah_bayar');

        $this->assertSame(45000, $order->fresh()->dibayar);
    }

    public function test_tunai_menghitung_kembalian_di_server(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())->post(route('orders.payments.store', $order), [
            'metode' => 'tunai',
            'jumlah_bayar' => 25000,
            'uang_diterima' => 50000,
        ])->assertSessionHasNoErrors();

        $payment = $order->fresh()->payments->first();

        $this->assertSame(25000, $payment->jumlah_bayar);
        $this->assertSame(50000, $payment->uang_diterima);
        $this->assertSame(25000, $payment->kembalian);
    }

    public function test_tunai_tanpa_uang_diterima_ditolak(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'tunai',
                'jumlah_bayar' => 25000,
            ])
            ->assertSessionHasErrors('uang_diterima');

        $this->assertCount(0, $order->fresh()->payments);
    }

    public function test_tunai_kurang_dari_jumlah_bayar_ditolak(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'tunai',
                'jumlah_bayar' => 25000,
                'uang_diterima' => 20000,
            ])
            ->assertSessionHasErrors('uang_diterima');

        $this->assertCount(0, $order->fresh()->payments);
    }

    public function test_transfer_tidak_menyimpan_uang_diterima(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())->post(route('orders.payments.store', $order), [
            'metode' => 'qris',
            'jumlah_bayar' => 45000,
        ])->assertSessionHasNoErrors();

        $payment = $order->fresh()->payments->first();

        $this->assertNull($payment->uang_diterima);
        $this->assertSame(0, $payment->kembalian);
    }

    public function test_pesanan_dibatalkan_tidak_bisa_dibayar(): void
    {
        $order = Order::factory()->dibatalkan()->create([
            'customer_id' => Customer::factory(),
            'total' => 45000,
            'subtotal' => 45000,
        ]);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 10000,
            ])
            ->assertSessionHasErrors('jumlah_bayar');

        $this->assertCount(0, $order->fresh()->payments);
    }

    public function test_jumlah_bayar_nol_ditolak(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'transfer',
                'jumlah_bayar' => 0,
            ])
            ->assertSessionHasErrors('jumlah_bayar');
    }

    public function test_metode_tidak_dikenal_ditolak(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->post(route('orders.payments.store', $order), [
                'metode' => 'bitcoin',
                'jumlah_bayar' => 10000,
            ])
            ->assertSessionHasErrors('metode');
    }

    public function test_pembayaran_butuh_login(): void
    {
        $order = $this->pesanan();

        $this->post(route('orders.payments.store', $order), [
            'metode' => 'transfer',
            'jumlah_bayar' => 10000,
        ])->assertRedirect('/login');
    }

    public function test_halaman_detail_menampilkan_form_pembayaran(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Catat Pembayaran', false)
            ->assertDontSee('tersedia pada tahap berikutnya', false);
    }

    public function test_pesanan_lunas_tidak_menampilkan_form_pembayaran(): void
    {
        $order = $this->pesanan(45000);

        $this->actingAs($this->kasir())->post(route('orders.payments.store', $order), [
            'metode' => 'transfer',
            'jumlah_bayar' => 45000,
        ]);

        $this->actingAs($this->kasir())
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Catat Pembayaran', false)
            ->assertSee('sudah lunas', false);
    }
}
