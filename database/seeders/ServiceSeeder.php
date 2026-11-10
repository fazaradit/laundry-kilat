<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'nama_layanan' => 'Cuci Kering',
                'satuan' => 'kg',
                'harga' => 7000,
                'estimasi_hari' => 2,
            ],
            [
                'nama_layanan' => 'Setrika',
                'satuan' => 'kg',
                'harga' => 5000,
                'estimasi_hari' => 2,
            ],
            [
                'nama_layanan' => 'Cuci Setrika',
                'satuan' => 'kg',
                'harga' => 10000,
                'estimasi_hari' => 3,
            ],
            [
                'nama_layanan' => 'Cuci Sepatu',
                'satuan' => 'pcs',
                'harga' => 25000,
                'estimasi_hari' => 3,
            ],
            [
                'nama_layanan' => 'Bed Cover',
                'satuan' => 'pcs',
                'harga' => 30000,
                'estimasi_hari' => 4,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['nama_layanan' => $service['nama_layanan']],
                $service + ['status' => 'aktif']
            );
        }
    }
}
