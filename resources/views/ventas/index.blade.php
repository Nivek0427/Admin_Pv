@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start mb-4">
        <div class="mb-3 mb-sm-0">
            <h1 class="fr-page-title">Ventas</h1>
            <p class="fr-text-muted mb-0">Consulta, filtra y revisa las ventas registradas.</p>
        </div>
        <a href="{{ route('ventas.create') }}" class="btn-fr-primary">
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
        <form class="fr-filters mb-3">

            <div class="col-auto">
                <select name="tipo" class="form-control form-control-fr" onchange="this.form.submit()">
                    <option value="">-- Filtro rápido --</option>
                    <option value="dia" {{request('tipo')=='dia'? 'selected':''}}>Hoy</option>
                    <option value="ayer" {{ request('tipo')=='ayer' ? 'selected' : '' }}>Ayer</option>
                    <option value="semana" {{ request('tipo')=='semana' ? 'selected' : '' }}>Esta semana</option>
                    <option value="mes" {{ request('tipo')=='mes' ? 'selected' : '' }}>Este mes</option>
                    <option value="rango" {{ request('tipo')=='rango' ? 'selected' : '' }}>Rango de fechas</option>
                </select>
            </div>

            <div class="col-auto">
                <select name="metodo_pago" class="form-control form-control-fr" onchange="this.form.submit()">
                    <option value="">Todos los métodos</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="addi">ADDI</option>
                    <option value="tarjeta">Tarjeta (histórico)</option>
                    <option value="sistecredito">Sistecrédito</option>
                </select>
            </div>

            @if(request('tipo') == 'rango')
            <div class="col-auto">
                <input type="date" name="desde" class="form-control form-control-fr" value="{{ request('desde') }}">
            </div>

            <div class="col-auto">
                <input type="date" name="hasta" class="form-control form-control-fr" value="{{ request('hasta') }}">
            </div>

            <div class="col-auto">
                <button class="btn-fr-primary">Aplicar</button>
            </div>
            @endif

        </form>
    </div>

    <form action="{{ route('ventas.index') }}" method="GET" class="fr-filters mb-3">
        {{-- Mantener filtros existentes --}}
        <input type="hidden" name="tipo" value="{{ request('tipo') }}">
        <input type="hidden" name="metodo_pago" value="{{ request('metodo_pago') }}">
        <input type="hidden" name="desde" value="{{ request('desde') }}">
        <input type="hidden" name="hasta" value="{{ request('hasta') }}">

        <div class="input-group">
            <input type="text" name="buscar" class="form-control form-control-fr"
                placeholder="Buscar por cliente, ID, total o estado..."
                value="{{ request('buscar') }}">

            <button class="btn-fr-primary" type="submit">Buscar</button>

            @if(request('buscar'))
                <a href="{{ route('ventas.index') }}" class="btn-fr-secondary">
                    Limpiar
                </a>
            @endif
        </div>
    </form>



        <div class="fr-card">
            <div class="fr-card-body">
             <div class="table-responsive">
        <table class="table table-bordered fr-table fr-table-sales">
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
                    <td>{{ $venta->metodo_pago === 'addi' ? 'ADDI' : ucfirst($venta->metodo_pago) }}</td>
                    <td>
                        @if($venta->estado === 'activa')
                            <span class="fr-badge fr-badge-active-sales">Activa</span>
                        @else
                            <span class="fr-badge fr-badge-revoked">Revocada</span>
                        @endif
                    </td>
                    <td>
                        @can('ventas.revocar')
                        @if($venta->estado === 'activa')
                            <form action="{{ route('ventas.revocar', $venta->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn-fr-warning btn-fr-sm">Revocar</button>
                            </form>
                        @endif
                        @endcan
                        <a href="{{ route('ventas.show', $venta->id) }}" class="btn-fr-info btn-fr-sm">
                            <i class="fas fa-eye"></i> Ver
                        </a>

                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
             </div>
            </div>
        </div>
</div>
@endsection
