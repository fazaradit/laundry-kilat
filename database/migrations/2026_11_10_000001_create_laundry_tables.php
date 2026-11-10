<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('nomor_telepon', 15);
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nama_layanan', 100);
            $table->enum('satuan', ['kg', 'pcs']);
            $table->unsignedInteger('harga');
            $table->unsignedTinyInteger('estimasi_hari');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_order', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('tanggal_masuk');
            $table->date('estimasi_selesai');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('diskon')->default(0);
            $table->unsignedInteger('total');
            $table->enum('status', ['diterima', 'dicuci', 'disetrika', 'siap_diambil', 'diambil', 'dibatalkan'])
                ->default('diterima');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tanggal_masuk');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->decimal('qty', 8, 2);
            $table->unsignedInteger('harga_satuan');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('metode', ['tunai', 'transfer', 'qris']);
            $table->unsignedInteger('jumlah_bayar');
            $table->unsignedInteger('kembalian')->default(0);
            $table->string('catatan', 255)->nullable();
            $table->dateTime('tanggal_bayar');
            $table->timestamps();
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status_lama', 20);
            $table->string('status_baru', 20);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');
    }
};
