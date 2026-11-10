<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Nilai fallback dipakai kalau server API internal (port 8011) belum menyala,
        // supaya halaman tetap tampil alih-alih error 500 (lihat PRD 8.5).
        $stats = [
            'pesanan_hari_ini' => 0,
            'pesanan_aktif' => 0,
            'pendapatan_hari_ini' => 0,
        ];

        try {
            $response = Http::timeout(5)->get(config('services.internal_api.base_url').'/api/stats');

            if ($response->successful()) {
                $stats = array_merge($stats, (array) $response->json());
            }
        } catch (ConnectionException $e) {
            // Server internal API tidak bisa dihubungi - statistik tetap tampil nol.
        }

        return view('dashboard', compact('stats'));
    }
}
