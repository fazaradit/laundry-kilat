<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * Daftar pesanan untuk halaman laporan dan konsumsi eksternal.
     *
     * Filter: `status`, `dari`, `sampai` (keduanya inklusif, format Y-m-d),
     * `per_page` (1..100, default 15).
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $this->validatedFilter($request);

        $query = $this->terapkanFilter(Order::query(), $filter);

        // Kedua angka ini dihitung dari query yang sama sebelum paginate,
        // supaya total periode tetap utuh meski tabelnya lebih dari satu
        // halaman.
        $totalNilai = (int) (clone $query)->sum('total');

        $totalDibayar = (int) Payment::query()
            ->whereIn('order_id', (clone $query)->select('orders.id'))
            ->sum('jumlah_bayar');

        $orders = $query
            ->with(['customer:id,nama,nomor_telepon', 'user:id,name', 'items.service:id,nama_layanan,satuan'])
            ->withSum('payments as dibayar', 'jumlah_bayar')
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->paginate($filter['per_page'])
            ->withQueryString();

        // additional(['meta' => ...]) akan menumpuk dengan meta paginator
        // (total menjadi [n, n]), jadi agregat disisipkan ke meta yang sudah
        // dibuat paginator.
        $payload = OrderResource::collection($orders)->response()->getData(true);

        $payload['meta']['total_nilai'] = $totalNilai;
        $payload['meta']['total_dibayar'] = $totalDibayar;

        return response()->json($payload);
    }

    public function show(int $id): OrderResource
    {
        $order = Order::query()
            ->with(['customer:id,nama,nomor_telepon', 'user:id,name', 'items.service:id,nama_layanan,satuan'])
            ->withSum('payments as dibayar', 'jumlah_bayar')
            ->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * @return array{status:?string, dari:?string, sampai:?string, per_page:int}
     */
    private function validatedFilter(Request $request): array
    {
        $data = $request->validate([
            'status' => ['nullable', 'string'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Status tak dikenal diabaikan, bukan error: halaman laporan tidak
        // boleh pecah hanya karena URL-nya salah ketik.
        $status = isset($data['status']) && OrderStatus::tryFrom($data['status']) !== null
            ? $data['status']
            : null;

        return [
            'status' => $status,
            'dari' => $data['dari'] ?? null,
            'sampai' => $data['sampai'] ?? null,
            'per_page' => (int) ($data['per_page'] ?? 15),
        ];
    }

    /**
     * @param  Builder<Order>  $query
     * @param  array{status:?string, dari:?string, sampai:?string}  $filter
     * @return Builder<Order>
     */
    private function terapkanFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['status'] !== null, fn (Builder $q) => $q->where('status', $filter['status']))
            ->when($filter['dari'] !== null, fn (Builder $q) => $q->whereDate('tanggal_masuk', '>=', $filter['dari']))
            ->when($filter['sampai'] !== null, fn (Builder $q) => $q->whereDate('tanggal_masuk', '<=', $filter['sampai']));
    }
}
