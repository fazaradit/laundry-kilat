<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'payments.user:id,name',
            'statusLogs.user:id,name',
        ]);

        return view('orders.show', compact('order'));
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
