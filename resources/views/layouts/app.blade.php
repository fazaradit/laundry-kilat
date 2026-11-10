<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beranda') | Laundry Kilat</title>
    <style>
        /* =====================================================================
           DESIGN TOKENS
           Satu-satunya tempat nilai warna, radius, dan skala tipe didefinisikan.
           Komponen di bawah WAJIB memakai var() ini, bukan hex mentah.

           RADIUS (3 tingkat, aturan tetap):
             8px   -> container : card, tabel, alert, panel
             6px   -> control   : tombol, input, select, textarea
             999px -> badge     : status saja
           ===================================================================== */
        :root {
            /* Warna permukaan */
            --surface:         #FAFAFA;
            --surface-raised:  #FFFFFF;
            --surface-sunken:  #F4F4F5;

            /* Garis */
            --border:          #E4E4E7;
            --border-strong:   #D4D4D8;

            /* Teks */
            --ink:             #18181B;
            --ink-muted:       #52525B;
            --ink-faint:       #8E8E96;

            /* Chrome (navbar) */
            --chrome:          #18181B;
            --chrome-hover:    #27272A;
            --chrome-border:   #3F3F46;
            --chrome-ink:      #F4F4F5;
            --chrome-ink-muted:#A1A1AA;

            /* Accent tunggal */
            --accent:          #0F766E;
            --accent-hover:    #0D5F58;
            --accent-soft:     #F0FDFA;
            --accent-border:   #CCFBF1;

            /* Status semantik */
            --success:         #15803D;
            --success-soft:    #F0FDF4;
            --success-border:  #BBF7D0;
            --success-ink:     #14532D;
            --danger:          #B91C1C;
            --danger-hover:    #991B1B;
            --danger-soft:     #FEF2F2;
            --danger-border:   #FECACA;
            --danger-ink:      #7F1D1D;
            --warning:         #B45309;
            --warning-soft:    #FFFBEB;
            --warning-border:  #FDE68A;
            --warning-ink:     #78350F;

            /* Netral tambahan */
            --on-accent:       #FFFFFF;
            --surface-hover:   #FCFCFD;

            /* Radius */
            --r-container: 8px;
            --r-control:   6px;

            /* Skala tipe */
            --text-xs:  12px;
            --text-sm:  13px;
            --text-base:14px;
            --text-md:  16px;
            --text-lg:  20px;
            --text-xl:  28px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: var(--surface);
            color: var(--ink);
            font-size: var(--text-base);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Uang, qty, tanggal, dan hitungan selalu rata digit */
        .num, td.num, th.num, .stat-value {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        /* ---------- Navbar : satu baris, tinggi 56px ---------- */
        nav {
            background: var(--chrome);
            color: var(--chrome-ink);
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            min-height: 56px;
        }

        nav .brand {
            font-weight: 700;
            font-size: var(--text-md);
            letter-spacing: -0.01em;
            margin-right: 4px;
            white-space: nowrap;
            color: var(--chrome-ink);
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2px;
            margin: 0;
            padding: 0;
            flex: 1;
            min-width: 0;
        }

        nav ul li a {
            color: var(--chrome-ink-muted);
            text-decoration: none;
            padding: 7px 11px;
            border-radius: var(--r-control);
            display: block;
            font-size: var(--text-sm);
            font-weight: 500;
            white-space: nowrap;
            transition: background-color 120ms ease, color 120ms ease;
        }

        nav ul li a:hover {
            background: var(--chrome-hover);
            color: var(--chrome-ink);
        }

        nav ul li a.active {
            background: var(--accent);
            color: var(--on-accent);
        }

        nav .navbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: var(--text-sm);
            color: var(--chrome-ink-muted);
            white-space: nowrap;
        }

        nav .navbar-user .user-name {
            color: var(--chrome-ink);
            font-weight: 500;
        }

        nav .navbar-user .role-chip {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid var(--chrome-border);
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--chrome-ink-muted);
        }

        nav .navbar-user .btn-logout {
            background: transparent;
            border: 1px solid var(--chrome-border);
            color: var(--chrome-ink-muted);
            padding: 5px 11px;
            border-radius: var(--r-control);
            cursor: pointer;
            font-size: var(--text-xs);
            font-family: inherit;
            transition: border-color 120ms ease, color 120ms ease;
        }

        nav .navbar-user .btn-logout:hover {
            border-color: var(--danger);
            color: var(--on-accent);
            background: var(--danger);
        }

        nav a.login-link {
            color: var(--chrome-ink);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: 500;
        }

        /* ---------- Kerangka halaman ---------- */
        main {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 24px 56px;
        }

        h1 {
            font-size: var(--text-lg);
            line-height: 1.25;
            letter-spacing: -0.015em;
            margin: 0 0 4px;
        }

        h2 {
            font-size: var(--text-md);
            letter-spacing: -0.01em;
            margin: 28px 0 10px;
        }

        p.subtitle {
            color: var(--ink-muted);
            margin: 0 0 20px;
            max-width: 65ch;
        }

        code {
            background: var(--surface-sunken);
            border: 1px solid var(--border);
            padding: 1px 5px;
            border-radius: var(--r-control);
            font-size: var(--text-xs);
            font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        }

        /* ---------- Flash message ---------- */
        .alert {
            padding: 11px 14px;
            border-radius: var(--r-container);
            margin-bottom: 18px;
            font-size: var(--text-base);
            border: 1px solid var(--border);
            border-left-width: 3px;
        }

        .alert-success {
            background: var(--success-soft);
            border-color: var(--success-border);
            border-left-color: var(--success);
            color: var(--success-ink);
        }

        .alert-error {
            background: var(--danger-soft);
            border-color: var(--danger-border);
            border-left-color: var(--danger);
            color: var(--danger-ink);
        }

        .alert-error ul { margin: 8px 0 0; padding-left: 20px; }
        .alert-error strong { font-weight: 600; }

        /* ---------- Container ---------- */
        .card {
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: var(--r-container);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* ---------- Tabel ---------- */
        table { width: 100%; border-collapse: collapse; font-size: var(--text-base); }

        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        th {
            background: var(--surface-sunken);
            font-weight: 600;
            color: var(--ink-muted);
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--surface-hover); }

        td.empty {
            text-align: center;
            color: var(--ink-muted);
            padding: 32px 24px;
        }

        /* ---------- Tombol ---------- */
        .btn {
            display: inline-block;
            background: var(--accent);
            color: var(--on-accent);
            padding: 9px 16px;
            border-radius: var(--r-control);
            text-decoration: none;
            border: 1px solid transparent;
            font-size: var(--text-base);
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
            transition: background-color 120ms ease, border-color 120ms ease;
        }

        .btn:hover { background: var(--accent-hover); }
        .btn:active { transform: translateY(1px); }
        .btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .btn-sm { padding: 6px 11px; font-size: var(--text-sm); }

        .btn-secondary {
            background: var(--surface-raised);
            color: var(--ink);
            border-color: var(--border-strong);
        }
        .btn-secondary:hover { background: var(--surface-sunken); }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: var(--danger-hover); }

        .btn-outline {
            background: var(--surface-raised);
            color: var(--accent);
            border-color: var(--accent-border);
        }
        .btn-outline:hover { background: var(--accent-soft); }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        form.inline { display: inline; }

        /* ---------- Form ---------- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--ink);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 9px 12px;
            border: 1px solid var(--border-strong);
            border-radius: var(--r-control);
            font-size: var(--text-base);
            font-family: inherit;
            background: var(--surface-raised);
            color: var(--ink);
            width: 100%;
            transition: border-color 120ms ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder { color: var(--ink-faint); }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-soft);
        }

        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-hint { font-size: var(--text-xs); color: var(--ink-muted); }
        .form-error { font-size: var(--text-sm); color: var(--danger); }
        .form-footer { margin-top: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

        /* ---------- Dashboard : hierarki, bukan 4 kartu identik ---------- */
        .overview {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 16px;
            margin-top: 20px;
        }

        .overview-primary {
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: var(--r-container);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 16px;
        }

        .overview-primary .label {
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ink-muted);
            font-weight: 600;
        }

        .overview-primary .stat-value {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: var(--ink);
        }

        .overview-primary .note { font-size: var(--text-sm); color: var(--ink-muted); }

        .metric-list {
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: var(--r-container);
            overflow: hidden;
        }

        .metric-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }

        .metric-row:last-child { border-bottom: none; }

        .metric-row .label { font-size: var(--text-sm); color: var(--ink-muted); }

        .metric-row .stat-value {
            font-size: var(--text-lg);
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        .page-actions { margin-top: 24px; display: flex; gap: 10px; flex-wrap: wrap; }

        /* ---------- Badge : 4 makna saja ---------- */
        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: var(--text-xs);
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .badge-neutral {
            background: var(--surface-sunken);
            border-color: var(--border);
            color: var(--ink-muted);
        }

        .badge-progress {
            background: var(--accent-soft);
            border-color: var(--accent-border);
            color: var(--accent);
        }

        .badge-success {
            background: var(--success-soft);
            border-color: var(--success-border);
            color: var(--success-ink);
        }

        .badge-danger {
            background: var(--danger-soft);
            border-color: var(--danger-border);
            color: var(--danger-ink);
        }

        /* Nama lama dipertahankan agar view lain tidak pecah */
        .badge-gray   { background: var(--surface-sunken); border-color: var(--border); color: var(--ink-muted); }
        .badge-green  { background: var(--success-soft); border-color: var(--success-border); color: var(--success-ink); }
        .badge-yellow { background: var(--warning-soft); border-color: var(--warning-border); color: var(--warning-ink); }
        .badge-red    { background: var(--danger-soft); border-color: var(--danger-border); color: var(--danger-ink); }
        .badge-blue   { background: var(--accent-soft); border-color: var(--accent-border); color: var(--accent); }

        /* ---------- Pagination ---------- */
        .pagination {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            font-size: var(--text-sm);
        }

        .pagination a {
            color: var(--accent);
            text-decoration: none;
            padding: 4px 8px;
            border-radius: var(--r-control);
        }

        .pagination a:hover { background: var(--accent-soft); }
        .pagination .pagination-current { font-weight: 700; color: var(--ink); padding: 4px 8px; }
        .pagination .pagination-disabled { color: var(--ink-faint); padding: 4px 8px; }

        /* ---------- Toolbar & filter ---------- */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .filter-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .filter-form .form-group { gap: 2px; }
        .filter-form input, .filter-form select { padding: 7px 10px; font-size: var(--text-sm); }

        /* ---------- Halaman login ---------- */
        .login-wrap {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 7vh;
        }

        .login-card {
            background: var(--surface-raised);
            border: 1px solid var(--border);
            border-radius: var(--r-container);
            padding: 32px;
            width: 100%;
            max-width: 400px;
        }

        .login-card .wordmark {
            font-size: var(--text-md);
            font-weight: 700;
            letter-spacing: -0.01em;
            margin-bottom: 2px;
        }

        .login-card h1 { text-align: left; font-size: var(--text-lg); margin: 0 0 4px; }
        .login-card p.subtitle { margin-bottom: 24px; font-size: var(--text-sm); }
        .login-card .btn { width: 100%; margin-top: 8px; padding: 11px; }

        /* ---------- Utilitas ---------- */
        .muted { color: var(--ink-muted); font-size: var(--text-sm); }
        .text-right { text-align: right; }
        .nowrap { white-space: nowrap; }

        /* ---------- Aksesibilitas : kurangi gerak ---------- */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
            .btn:active { transform: none; }
        }

        /* ---------- Responsif ---------- */
        @media (max-width: 768px) {
            nav { padding: 8px 16px; flex-wrap: wrap; gap: 10px; }
            nav ul { order: 3; width: 100%; flex-wrap: wrap; }
            main { margin: 20px auto; padding: 0 16px 40px; }
            .overview { grid-template-columns: 1fr; }
            .overview-primary .stat-value { font-size: 32px; }
            .form-grid { grid-template-columns: 1fr; }
            .toolbar { align-items: stretch; }
            .card { padding: 16px; }
            table { font-size: var(--text-sm); }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    @include('partials.navbar')

    <main>
        @include('partials.alert')

        @yield('content')
    </main>
</body>
</html>
