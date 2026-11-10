<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Diterima = 'diterima';
    case Dicuci = 'dicuci';
    case Disetrika = 'disetrika';
    case SiapDiambil = 'siap_diambil';
    case Diambil = 'diambil';
    case Dibatalkan = 'dibatalkan';

    /**
     * Label yang ditampilkan di UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Dicuci => 'Dicuci',
            self::Disetrika => 'Disetrika',
            self::SiapDiambil => 'Siap Diambil',
            self::Diambil => 'Diambil',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Class badge yang dipakai di tampilan. Hanya 4 makna:
     * netral (baru diterima), progres (sedang dikerjakan),
     * sukses (selesai / siap), bahaya (dibatalkan).
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Diterima => 'badge-neutral',
            self::Dicuci, self::Disetrika => 'badge-progress',
            self::SiapDiambil, self::Diambil => 'badge-success',
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
        return [self::Diterima, self::Dicuci, self::Disetrika, self::SiapDiambil, self::Diambil];
    }

    /**
     * Status berikutnya yang sah menurut state machine.
     * Status akhir (diambil / dibatalkan) tidak punya kelanjutan.
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
     * Selain status berikutnya, Admin boleh membatalkan dari diterima/dicuci
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
        return $this === self::Diterima || $this === self::Dicuci;
    }

    public function sudahFinal(): bool
    {
        return $this === self::Diambil || $this === self::Dibatalkan;
    }

    /**
     * Apakah perpindahan dari status ini ke $tujuan diizinkan (tanpa cek role).
     */
    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->transisiYangBoleh(), true);
    }

    /**
     * Status yang dianggap masih aktif dikerjakan.
     *
     * @return array<int, string>
     */
    public static function aktifValues(): array
    {
        return [
            self::Diterima->value,
            self::Dicuci->value,
            self::Disetrika->value,
            self::SiapDiambil->value,
        ];
    }
}
