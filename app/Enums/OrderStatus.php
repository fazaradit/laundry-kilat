<?php

namespace App\Enums;

/**
 * Alur status pesanan.
 *
 * Tiga tahap generik: diterima -> diproses -> selesai.
 * Tahap operasional seperti dicuci atau disetrika sengaja tidak dijadikan
 * status, karena tidak semua layanan melewatinya. Cuci Sepatu tidak pernah
 * disetrika, dan layanan Setrika tidak pernah dicuci. Status generik lebih
 * jujur daripada memaksa tahap yang tidak relevan.
 */
enum OrderStatus: string
{
    case Diterima = 'diterima';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    /**
     * Label yang ditampilkan di UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Class badge yang dipakai di tampilan. Hanya 4 makna:
     * netral (baru diterima), progres (sedang dikerjakan),
     * sukses (selesai), bahaya (dibatalkan).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Diterima => 'badge-neutral',
            self::Diproses => 'badge-progress',
            self::Selesai => 'badge-success',
            self::Dibatalkan => 'badge-danger',
        };
    }

    /**
     * Urutan alur operasional normal (dipakai untuk menentukan transisi sah).
     *
     * @return array<int, self>
     */
    public static function urutan(): array
    {
        return [self::Diterima, self::Diproses, self::Selesai];
    }

    /**
     * Status berikutnya yang sah menurut state machine.
     * Status akhir (selesai / dibatalkan) tidak punya kelanjutan.
     */
    public function berikutnya(): ?self
    {
        $urutan = self::urutan();
        $index = array_search($this, $urutan, true);

        if ($index === false || ! isset($urutan[$index + 1])) {
            return null;
        }

        return $urutan[$index + 1];
    }

    /**
     * Daftar status yang boleh dipilih dari status saat ini.
     * Selain status berikutnya, Admin boleh membatalkan dari diterima/diproses
     * (aturan BR-05); pembatasan role dilakukan di controller.
     *
     * @return array<int, self>
     */
    public function transisiYangBoleh(): array
    {
        $boleh = [];

        if ($berikutnya = $this->berikutnya()) {
            $boleh[] = $berikutnya;
        }

        if ($this->bolehDibatalkan()) {
            $boleh[] = self::Dibatalkan;
        }

        return $boleh;
    }

    public function bolehDibatalkan(): bool
    {
        return $this === self::Diterima || $this === self::Diproses;
    }

    public function sudahFinal(): bool
    {
        return $this === self::Selesai || $this === self::Dibatalkan;
    }

    /**
     * Apakah perpindahan dari status ini ke $tujuan diizinkan (tanpa cek role).
     */
    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->transisiYangBoleh(), true);
    }

    /**
     * Status yang dianggap masih aktif dikerjakan (belum selesai / batal).
     *
     * @return array<int, string>
     */
    public static function aktifValues(): array
    {
        return [
            self::Diterima->value,
            self::Diproses->value,
        ];
    }
}
