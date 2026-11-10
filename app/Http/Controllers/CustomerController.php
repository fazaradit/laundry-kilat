<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('nomor_telepon', 'like', "%{$search}%");
                });
            })
            ->withCount('orders')
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', "Pelanggan \"{$customer->nama}\" berhasil ditambahkan.");
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')
            ->with('success', "Data pelanggan \"{$customer->nama}\" berhasil diperbarui.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        // BR-08 / FR-08: pelanggan yang punya riwayat pesanan tidak boleh dihapus.
        if ($customer->orders()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', "Pelanggan \"{$customer->nama}\" tidak dapat dihapus karena sudah memiliki riwayat pesanan.");
        }

        $nama = $customer->nama;
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', "Pelanggan \"{$nama}\" berhasil dihapus.");
    }
}
