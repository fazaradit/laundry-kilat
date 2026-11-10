<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan dua hal yang sebelumnya tercampur pada satu kolom.
 *
 * `jumlah_bayar` tetap berarti nominal yang dialokasikan ke tagihan, jadi
 * nilainya tidak pernah melebihi sisa (BR-06). `uang_diterima` adalah uang
 * fisik yang diserahkan pelanggan, dan hanya relevan untuk tunai. Dari
 * selisih keduanya lahir `kembalian`.
 *
 * Tanpa kolom ini, struk tidak bisa mencetak "Tunai 50.000, kembali 25.000"
 * seperti nota asli, dan angka kembalian tidak bisa diperiksa ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('uang_diterima')->nullable()->after('jumlah_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('uang_diterima');
        });
    }
};
