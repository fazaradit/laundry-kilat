<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyederhanakan status pesanan dari enam nilai menjadi empat:
 * diterima, diproses, selesai, dibatalkan.
 *
 * Urutan langkah sangat penting. Kalau enum langsung dipersempit, MySQL akan
 * menolak atau memotong baris yang masih berisi status lama. Jadi:
 *   1. lebarkan enum agar memuat nilai lama DAN baru,
 *   2. petakan data lama ke nilai baru,
 *   3. baru persempit ke daftar final.
 */
return new class extends Migration
{
    /**
     * Gabungan nilai lama dan baru, dipakai sebagai jembatan.
     *
     * @var array<int, string>
     */
    private array $jembatan = [
        'diterima',
        'dicuci',
        'disetrika',
        'siap_diambil',
        'diambil',
        'diproses',
        'selesai',
        'dibatalkan',
    ];

    /**
     * @var array<int, string>
     */
    private array $final = ['diterima', 'diproses', 'selesai', 'dibatalkan'];

    /**
     * @var array<int, string>
     */
    private array $lama = ['diterima', 'dicuci', 'disetrika', 'siap_diambil', 'diambil', 'dibatalkan'];

    public function up(): void
    {
        $this->ubahEnum($this->jembatan);

        // Tiga tahap lama yang masih berjalan dianggap sedang diproses.
        DB::table('orders')
            ->whereIn('status', ['dicuci', 'disetrika', 'siap_diambil'])
            ->update(['status' => 'diproses']);

        // Pesanan yang sudah diambil pelanggan dianggap selesai.
        DB::table('orders')
            ->where('status', 'diambil')
            ->update(['status' => 'selesai']);

        $this->petakanRiwayat();

        $this->ubahEnum($this->final);
    }

    public function down(): void
    {
        $this->ubahEnum($this->jembatan);

        DB::table('orders')
            ->where('status', 'diproses')
            ->update(['status' => 'dicuci']);

        DB::table('orders')
            ->where('status', 'selesai')
            ->update(['status' => 'diambil']);

        $this->ubahEnum($this->lama);
    }

    /**
     * Petakan status di order_status_logs supaya riwayat tidak menyebut tahap
     * yang sudah tidak ada. Baris yang jadi kembar berurutan setelah dipetakan
     * dihapus, karena "diproses -> diproses" tidak berarti apa pun.
     */
    private function petakanRiwayat(): void
    {
        $peta = [
            'dicuci' => 'diproses',
            'disetrika' => 'diproses',
            'siap_diambil' => 'diproses',
            'diambil' => 'selesai',
        ];

        $logs = DB::table('order_status_logs')->orderBy('order_id')->orderBy('id')->get();
        $terakhir = [];

        foreach ($logs as $log) {
            $lama = $peta[$log->status_lama] ?? $log->status_lama;
            $baru = $peta[$log->status_baru] ?? $log->status_baru;

            $sebelumnya = $terakhir[$log->order_id] ?? null;

            // Baris yang tidak menandai perpindahan apa pun dibuang.
            if ($baru === $sebelumnya) {
                DB::table('order_status_logs')->where('id', $log->id)->delete();

                continue;
            }

            DB::table('order_status_logs')->where('id', $log->id)->update([
                'status_lama' => $lama,
                'status_baru' => $baru,
            ]);

            $terakhir[$log->order_id] = $baru;
        }
    }

    /**
     * @param  array<int, string>  $nilai
     */
    private function ubahEnum(array $nilai): void
    {
        Schema::table('orders', function (Blueprint $table) use ($nilai) {
            $table->enum('status', $nilai)->default('diterima')->change();
        });
    }
};
