<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Struk') | Laundry Kilat</title>
    <style>
        /* =====================================================================
           LAYOUT STRUK
           Sengaja terpisah dari layouts/app.blade.php: struk tidak butuh
           navbar, token warna dashboard, maupun lebar 1100px. Satu-satunya
           acuan visual yang diwarisi adalah tipografi dan angka tabular.
           ===================================================================== */
        :root {
            --ink:   #18181B;
            --muted: #52525B;
            --line:  #D4D4D8;
            --paper: #FFFFFF;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 32px 16px 64px;
            background: #F4F4F5;
            color: var(--ink);
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .num {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        .struk {
            width: 320px;
            margin: 0 auto;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 24px 20px;
        }

        .struk-head {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 1px dashed var(--line);
        }

        .struk-head .nama-laundry {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.01em;
            margin: 0 0 2px;
        }

        .struk-head .alamat {
            margin: 0;
            font-size: 11px;
            color: var(--muted);
        }

        .struk-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
            font-size: 12px;
        }

        .struk-meta .baris {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .struk-meta .baris span:first-child { color: var(--muted); }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .daftar-item {
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .daftar-item td {
            padding: 3px 0;
            vertical-align: top;
        }

        .daftar-item td.nama { padding-right: 8px; }
        .daftar-item td.nilai { text-align: right; white-space: nowrap; }

        .daftar-item .rincian {
            font-size: 11px;
            color: var(--muted);
            padding-bottom: 8px;
        }

        .ringkasan {
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .ringkasan .baris {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 2px 0;
        }

        .ringkasan .baris.total {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid var(--line);
            font-weight: 700;
            font-size: 14px;
        }

        .ringkasan .baris span:first-child { color: var(--muted); }
        .ringkasan .baris.total span:first-child { color: var(--ink); }

        .sisa {
            font-weight: 600;
        }

        .struk-foot {
            padding-top: 16px;
            text-align: center;
            font-size: 11px;
            color: var(--muted);
        }

        .toolbar-cetak {
            width: 320px;
            margin: 0 auto 16px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .tombol {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid var(--line);
            background: var(--paper);
            color: var(--ink);
            font-size: 12px;
            font-family: inherit;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 120ms ease;
        }

        .tombol:hover { background: #FAFAFA; }
        .tombol:active { transform: translateY(1px); }
        .tombol-utama {
            background: #0F766E;
            border-color: #0F766E;
            color: #FFFFFF;
        }
        .tombol-utama:hover { background: #0D5F58; border-color: #0D5F58; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
            .tombol:active { transform: none; }
        }

        /* Saat dicetak hanya kertas struk yang tersisa. */
        @media print {
            body { background: #FFFFFF; padding: 0; }
            .toolbar-cetak { display: none; }
            .struk {
                width: auto;
                border: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar-cetak">
        <a href="{{ url()->previous() }}" class="tombol">Kembali</a>
        <button type="button" class="tombol tombol-utama" onclick="window.print()">Cetak</button>
    </div>

    @yield('content')
</body>
</html>
