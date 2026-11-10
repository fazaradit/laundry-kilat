<nav>
    <div class="brand">🧺 Laundry Kilat</div>

    @auth
        <ul>
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            </li>
            <li>
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Pelanggan</a>
            </li>
            @if (auth()->user()->isAdmin())
                <li>
                    <a href="{{ route('services.index') }}" class="{{ request()->routeIs('services.*') ? 'active' : '' }}">Layanan</a>
                </li>
            @endif
            @if (Route::has('orders.index'))
                <li>
                    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.index') || request()->routeIs('orders.create') || request()->routeIs('orders.show') || request()->routeIs('orders.struk') ? 'active' : '' }}">Pesanan</a>
                </li>
            @endif
            @if (Route::has('orders.report'))
                <li>
                    <a href="{{ route('orders.report') }}" class="{{ request()->routeIs('orders.report') ? 'active' : '' }}">Laporan</a>
                </li>
            @endif        </ul>

        <div class="navbar-user">
            <span>{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</span>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    @endauth

    @guest
        <a href="{{ route('login') }}" class="login-link {{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
    @endguest
</nav>
