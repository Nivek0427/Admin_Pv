@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="container">
    <h2 class="mb-4">Configuración</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-primary mb-4">
        <div class="card-header">
            <h3 class="card-title">Mi contraseña</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('configuracion.password') }}">
                @csrf
                <div class="form-group">
                    <label for="current_password">Contraseña actual</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group">
                    <label for="new_password_confirmation">Confirmar nueva contraseña</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
            </form>
        </div>
    </div>

    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Usuarios</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Username</th>
                        <th>Rol</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }}</td>
                            <td>{{ $usuario->username }}</td>
                            <td>{{ $usuario->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                            <td>
                                <form method="POST" action="{{ route('configuracion.usuarios.password', $usuario) }}">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label for="new_password_{{ $usuario->id }}">Nueva contraseña</label>
                                        <input type="password" name="new_password" id="new_password_{{ $usuario->id }}" class="form-control" minlength="8" required>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="new_password_confirmation_{{ $usuario->id }}">Confirmar nueva contraseña</label>
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation_{{ $usuario->id }}" class="form-control" minlength="8" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm">Cambiar contraseña</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
