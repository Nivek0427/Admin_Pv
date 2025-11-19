@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Ventas</h3>

    <a href="{{ route('ventas.create') }}" class="btn btn-primary"
        style="background-color:grey; color:#000; font-weight:bold;">
        <i class="fas fa-plus"></i> Nueva Venta
    </a>
</div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-3">
        <form class="row g-2">

            <div class="col-auto">
                <select name="tipo" class="form-control" onchange="this.form.submit()">
                    <option value="">Hoy</option>
                    <option value="ayer" {{ request('tipo')=='ayer' ? 'selected' : '' }}>Ayer</option>
                    <option value="semana" {{ request('tipo')=='semana' ? 'selected' : '' }}>Esta semana</option>
                    <option value="mes" {{ request('tipo')=='mes' ? 'selected' : '' }}>Este mes</option>
                    <option value="rango" {{ request('tipo')=='rango' ? 'selected' : '' }}>Rango de fechas</option>
                </select>
            </div>

            <div class="col-auto">
                <select name="metodo_pago" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los métodos</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="sistecredito">Sistecrédito</option>
                </select>
            </div>

            @if(request('tipo') == 'rango')
            <div class="col-auto">
                <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
            </div>

            <div class="col-auto">
                <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
            </div>

            <div class="col-auto">
                <button class="btn btn-primary">Aplicar</button>
            </div>
            @endif

        </form>
    </div>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Método de pago</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $venta)
                <tr>
                    <td>{{ $venta->id }}</td>
                    <td>{{ $venta->cliente ?? '-' }}</td>
                    <td>{{ $venta->fecha }}</td>
                    <td>${{ number_format($venta->total, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($venta->metodo_pago) }}</td>
                    <td>
                        @if($venta->estado === 'activa')
                            <span class="badge bg-success">Activa</span>
                        @else
                            <span class="badge bg-danger">Revocada</span>
                        @endif
                    </td>
                    <td>
                        @can('ventas.revocar')
                        @if($venta->estado === 'activa')
                            <form action="{{ route('ventas.revocar', $venta->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-warning btn-sm">Revocar</button>
                            </form>
                        @endif
                        @endcan
                        <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> Ver
                        </a>

                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
