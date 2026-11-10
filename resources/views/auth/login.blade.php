@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="login-wrap">
        <div class="login-card">
            <h1>🧺 Laundry Kilat</h1>
            <p class="subtitle">Masuk untuk mengelola pesanan laundry</p>

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="form-grid" style="grid-template-columns: 1fr;">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               placeholder="admin@laundry.test" required autofocus>
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn">Masuk</button>
            </form>

            <p class="muted" style="margin-top: 20px; text-align: center;">
                Akun demo: <code>admin@laundry.test</code> / <code>password</code>
            </p>
        </div>
    </div>
@endsection
