@extends('layout.app')

@section('title', 'Admin Login - MESS')

@section('content')
<div style="max-width: 400px; margin: 4rem auto;">
    <div class="card">
        <h1 style="margin-bottom: 2rem; text-align: center;">Admin Login</h1>

        @if ($errors->any())
            <div style="background-color: #fee; border: 1px solid var(--sk-red); padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li style="color: var(--sk-red);">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div style="margin-bottom: 1.5rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--sk-gray); border-radius: 4px; font-size: 1rem;"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--sk-gray); border-radius: 4px; font-size: 1rem;"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="remember" style="width: 18px; height: 18px;">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Login
            </button>

            <button type="button" onclick="autoLogin()" class="btn" style="width: 100%; margin-top: 1rem; background-color: #666; color: white;">
                Auto Login (Admin)
            </button>
        </form>
    </div>
</div>

<script>
    function autoLogin() {
        document.getElementById('email').value = 'admin@sk-elections.com';
        document.getElementById('password').value = 'password';
        document.querySelector('form').submit();
    }
</script>

@endsection
