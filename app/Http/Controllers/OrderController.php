<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->with(['customer:id,nama', 'user:id,name'])
            ->withSum('payments as dibayar', 'jumlah_bayar')
            ->when(
                OrderStatus::tryFrom($status) !== null,
                fn ($query) => $query->where('status', $status)
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('kode_order', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('nama', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'statusOptions' => OrderStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $services = Service::query()
            ->aktif()
            ->orderBy('nama_layanan')
            ->get();

        $customers = Customer::query()
            ->orderBy('nama')
            ->get(['id', 'nama', 'nomor_telepon']);

        return view('orders.create', [
            'services' => $services,
            'customers' => $customers,
            'customerTerpilih' => (int) $request->query('customer_id', 0),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->buatPesanan(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('orders.show', $order)
            ->with('success', "Pesanan {$order->kode_order} berhasil dibuat.");
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'user:id,name',
            'items.service',
            'payments' => fn ($query) => $query->orderBy('tanggal_bayar')->orderBy('id'),
            'payments.user:id,name',
            'statusLogs.user:id,name',
        ]);

        return view('orders.show', compact('order'));
    }

    /**
     * Struk memakai layout print terpisah (tanpa navbar) dan harga snapshot
     * dari order_items.
     */
    public function struk(Order $order): View
    {
        $order->load([
            'customer:id,nama',
            'user:id,name',
            'items.service:id,nama_layanan,satuan',
            'payments' => fn ($query) => $query->orderBy('tanggal_bayar')->orderBy('id'),
        ]);

        return view('orders.struk', compact('order'));
    }

    /**
     * Halaman laporan mengambil datanya dari GET /api/orders, bukan query
     * langsung, supaya ada satu jalur data yang sama dengan konsumen API lain.
     *
     * Kalau server API internal mati, halaman tetap tampil dengan tabel kosong
     * dan total nol, bukan error 500 (AC-19).
     */
    public function report(Request $request): View
    {
        $data = $request->validate([
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'status' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $dari = $data['dari'] ?? Carbon::today()->toDateString();
        $sampai = $data['sampai'] ?? Carbon::today()->toDateString();
        $status = isset($data['status']) && OrderStatus::tryFrom($data['status']) !== null
            ? $data['status']
            : '';
        $halaman = (int) ($data['page'] ?? 1);

        $kosong = [
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
                'per_page' => 15,
                'total_nilai' => 0,
                'total_dibayar' => 0,
            ],
        ];

        try {
            $response = Http::timeout(5)->get(config('services.internal_api.base_url').'/api/orders', array_filter([
                'dari' => $dari,
                'sampai' => $sampai,
                'status' => $status,
                'page' => $halaman,
                'per_page' => 15,
            ], static fn ($nilai) => $nilai !== '' && $nilai !== null));

            $payload = $response->successful() ? (array) $response->json() : $kosong;
        } catch (ConnectionException $e) {
            $payload = $kosong;
        }

        $meta = array_merge($kosong['meta'], (array) ($payload['meta'] ?? []));
        $baris = (array) ($payload['data'] ?? []);

        // Hasil HTTP berupa array, jadi dibungkus paginator manual agar
        // partial pagination yang sudah ada bisa dipakai ulang.
        $pesanan = new LengthAwarePaginator(
            $baris,
            (int) $meta['total'],
            (int) $meta['per_page'],
            (int) $meta['current_page'],
            ['path' => route('orders.report'), 'query' => $request->query()]
        );

        return view('orders.report', [
            'pesanan' => $pesanan,
            'dari' => $dari,
            'sampai' => $sampai,
            'status' => $status,
            'statusOptions' => OrderStatus::cases(),
            'totalNilai' => (int) $meta['total_nilai'],
            'totalDibayar' => (int) $meta['total_dibayar'],
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $tujuan = OrderStatus::from($request->validated('status'));

        $this->orderService->ubahStatus(
            $order,
            $tujuan,
            $request->user(),
            $request->validated('catatan')
        );

        return redirect()->route('orders.show', $order)
            ->with('success', "Status pesanan diubah menjadi {$tujuan->label()}.");
    }

    /**
     * Pembatalan dipisah agar route-nya bisa dibatasi middleware admin,
     * selain penjagaan role di OrderService (FR-26).
     */
    public function batalkan(Request $request, Order $order): RedirectResponse
    {
        $catatan = $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ])['catatan'] ?? null;

        $this->orderService->ubahStatus(
            $order,
            OrderStatus::Dibatalkan,
            $request->user(),
            $catatan
        );

        return redirect()->route('orders.show', $order)
            ->with('success', "Pesanan {$order->kode_order} dibatalkan.");
    }
}
