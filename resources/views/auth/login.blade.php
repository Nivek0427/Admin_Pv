@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="login-box">

    <!-- Logo -->
    <div class="login-logo">
        <img src="{{ asset('images/logo_FrStore.png') }}"
          alt="Logo"
          style="width: 50%; height: auto;">
    </div>

    <div class="card">
        <div class="card-body login-card-body">

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf

                {{-- Usuario --}}
                <div class="input-group mb-3">
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Usuario"
                        required autofocus>

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                @error('username')
<span class="invalid-feedback" role="alert">
    <strong>{{ $message }}</strong>
</span>
@enderror

                {{-- Password --}}
                <div class="input-group mb-3">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Contraseña"
                        required>

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

                {{-- Botón --}}
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block"
                            style="background-color:#FFD700; color:#000; font-weight:bold";>
                            Entrar
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection
