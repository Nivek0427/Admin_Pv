@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Historial de Movimientos</h2>

    <!-- Filtros -->
    <form method="GET" class="row g-2 mb-4">
        <div class="col-auto">
            <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
        </div>
        <div class="col-auto">
            <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar por fecha</button>
        </div>

        <div class="col-auto">
            <select name="tipo_mov" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tipo de movimiento --</option>
                <option value="entrada" {{ request('tipo_mov')=='entrada' ? 'selected' : '' }}>Entradas</option>
                <option value="venta" {{ request('tipo_mov')=='venta' ? 'selected' : '' }}>ventas</option>
            </select>
        </div>

        <div class="col-auto">
            <select name="tipo" class="form-control" onchange="this.form.submit()">
                <option value="">-- Filtro rápido --</option>
                <option value="dia" {{ request('tipo')=='dia'?'selected':'' }}>Hoy</option>
                <option value="ayer" {{ request('tipo')=='ayer' ? 'selected' : '' }}>Ayer</option>
                <option value="semana" {{ request('tipo')=='semana' ? 'selected' : '' }}>Esta semana</option>
                <option value="mes" {{ request('tipo')=='mes' ? 'selected' : '' }}>Este mes</option>
            </select>
        </div>
        <div class="col-auto">
            <a href="{{ route('inventario.movimientos') }}" class="btn btn-secondary">
                Limpiar filtros
            </a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Producto</th>
                    <th>Género</th>
                    <th>Cantidad</th>
                    <th>Tipo</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $mov)
                    <tr>
                        <td>{{ $mov->producto->nombre }}</td>
                        <td>{{ $mov->producto->genero }}</td>
                        <td>{{ $mov->cantidad }}</td>
                        <td>{{ $mov->tipo }}</td>
                        <td>{{ $mov->usuario->name }}</td>
                        <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $movimientos->withQueryString()->links() }}
</div>
@endsection
