<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laundry Kilat') — Laundry Kilat</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ---------- Navbar ---------- */
        nav {
            background: #1e3a8a;
            color: #fff;
            padding: 0 24px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            min-height: 56px;
        }

        nav .brand {
            font-weight: bold;
            font-size: 16px;
            margin-right: 8px;
            white-space: nowrap;
        }

        nav ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin: 0;
            padding: 0;
            flex: 1;
        }

        nav ul li a {
            color: #c7d2fe;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 6px;
            display: block;
            font-size: 14px;
        }

        nav ul li a:hover { background: #1e40af; color: #fff; }
        nav ul li a.active { background: #2563eb; color: #fff; font-weight: 600; }

        nav .navbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            color: #c7d2fe;
        }

        nav .navbar-user .btn-logout {
            background: transparent;
            border: 1px solid #6366f1;
            color: #e0e7ff;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        nav .navbar-user .btn-logout:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
        nav a.login-link { color: #e0e7ff; text-decoration: none; }

        /* ---------- Kerangka halaman ---------- */
        main {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 24px 48px;
        }

        h1 { font-size: 24px; margin: 0 0 6px; }
        h2 { font-size: 18px; margin: 28px 0 10px; }
        p.subtitle { color: #6b7280; margin: 0 0 20px; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }

        /* ---------- Flash message ---------- */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .alert-success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .alert-error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert-error ul { margin: 8px 0 0; padding-left: 20px; }

        /* ---------- Tabel ---------- */
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f9fafb; font-weight: 600; color: #374151; white-space: nowrap; }
        tbody tr:hover { background: #fafafa; }
        td.empty { text-align: center; color: #6b7280; padding: 24px; }

        /* ---------- Tombol ---------- */
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 9px 16px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            font-family: inherit;
        }

        .btn:hover { background: #1d4ed8; }
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #d97706; }
        .btn-warning:hover { background: #b45309; }
        .btn-outline { background: #fff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-outline:hover { background: #eff6ff; }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        form.inline { display: inline; }

        /* ---------- Form ---------- */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 14px; font-weight: 600; color: #374151; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            width: 100%;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
        }

        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-hint { font-size: 12px; color: #6b7280; }
        .form-error { font-size: 13px; color: #dc2626; }
        .form-footer { margin-top: 20px; display: flex; gap: 10px; align-items: center; }

        /* ---------- Kartu statistik ---------- */
        .stats-grid { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; }
        .stat-card {
            flex: 1;
            min-width: 180px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #1e3a8a; }
        .stat-card .label { margin-top: 6px; color: #6b7280; font-size: 14px; }

        /* ---------- Badge ---------- */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-gray    { background: #f3f4f6; color: #374151; }
        .badge-blue    { background: #dbeafe; color: #1e40af; }
        .badge-yellow  { background: #fef3c7; color: #92400e; }
        .badge-green   { background: #dcfce7; color: #166534; }
        .badge-red     { background: #fee2e2; color: #991b1b; }
        .badge-purple  { background: #ede9fe; color: #5b21b6; }

        /* ---------- Pagination ---------- */
        .pagination { margin-top: 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; font-size: 14px; }
        .pagination a { color: #2563eb; text-decoration: none; }
        .pagination a:hover { text-decoration: underline; }
        .pagination .pagination-current { font-weight: bold; color: #1e3a8a; }
        .pagination .pagination-disabled { color: #9ca3af; }

        /* ---------- Toolbar & filter ---------- */
        .toolbar { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .filter-form { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .filter-form .form-group { gap: 2px; }
        .filter-form input, .filter-form select { padding: 7px 10px; font-size: 14px; }

        /* ---------- Halaman login ---------- */
        .login-wrap { display: flex; justify-content: center; align-items: flex-start; padding-top: 8vh; }
        .login-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; width: 100%; max-width: 400px; }
        .login-card h1 { text-align: center; font-size: 22px; margin-bottom: 4px; }
        .login-card p.subtitle { text-align: center; margin-bottom: 24px; }
        .login-card .btn { width: 100%; margin-top: 8px; padding: 11px; }

        .muted { color: #6b7280; font-size: 13px; }
        .text-right { text-align: right; }
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
