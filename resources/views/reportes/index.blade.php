@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Reportes de Ventas</h3>

    <form method="GET" class="row g-3 mb-3">

    <div class="col-md-3">
        <label for="desde" class="form-label">Desde</label>
        <input type="date" name="desde" id="desde" class="form-control" value="{{ request('desde') }}">
    </div>

    <div class="col-md-3">
        <label for="hasta" class="form-label">Hasta</label>
        <input type="date" name="hasta" id="hasta" class="form-control" value="{{ request('hasta') }}">
    </div>

    <div class="col-md-3">
        <label for="tipo" class="form-label">Filtro rápido</label>
        <select name="tipo" id="tipo" class="form-control">
            <option value="">-- Seleccionar --</option>
            <option value="dia" {{ request('tipo')=='dia'?'selected':'' }}>Hoy</option>
            <option value="ayer" {{ request('tipo')=='ayer'?'selected':'' }}>Ayer</option>
            <option value="semana" {{ request('tipo')=='semana'?'selected':'' }}>Esta semana</option>
            <option value="mes" {{ request('tipo')=='mes'?'selected':'' }}>Este mes</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Estado</label>
        <select name="estado" id="estado" class="form-control">
            <option value="">Todos</option>
            <option value="activa" {{ request('estado')=='activa'?'selected':'' }}>Activas</option>
            <option value="revocada" {{ request('estado')=='revocada'?'selected':'' }}>Revocadas</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Método de pago</label>
        <select name="metodo_pago" class="form-control">
            <option value="">Todos</option>
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="addi">ADDI</option>
            <option value="tarjeta">Tarjeta (histórico)</option>
            <option value="sistecredito">Sistecrédito</option>
        </select>
    </div>


    <div class="col-md-12 d-flex align-items-end gap-2 mt-2">
        <button class="btn btn-primary w-25"
                style="background-color:#FFD700; color:#000; font-weight:bold;">Filtrar</button>
        <a href="{{ route('reportes.index') }}" class="btn btn-secondary w-25" style="margin-left: 10px;">Limpiar</a>
    </div>
</form>

<form action="{{ route('reportes.pdf') }}" method="GET" target="_blank">
    <input type="hidden" name="tipo" value="{{ request('tipo') }}">
    <input type="hidden" name="estado" value="{{ request('estado') }}">
    <input type="hidden" name="metodo_pago" value="{{ request('metodo_pago') }}">
    <input type="hidden" name="desde" value="{{ request('desde') }}">
    <input type="hidden" name="hasta" value="{{ request('hasta') }}">

    <button class="btn btn-danger mb-3">Generar PDF</button>
</form>


    <div class="alert alert-info"
            style="background-color:#6E6E6E; color:#000; font-weight:bold;">
        <strong>Total Ventas Activas:</strong> ${{ number_format($totalVentas, 0, ',', '.') }} <br>
        <strong>Ventas Activas:</strong> {{ $ventasActivas }} <br>
        <strong>Ventas Revocadas:</strong> {{ $ventasRevocadas }}
    </div>

    @can('reportes')
        <div class="alert alert-success">
            <strong>Ganancia calculada:</strong> ${{ number_format($gananciaTotal, 0, ',', '.') }}
            @if ($hayDetallesSinCostoHistorico)
                <br><small>* La ganancia corresponde únicamente a detalles con costo histórico registrado.</small>
            @endif
        </div>
    @endcan

    <div class="alert alert-secondary">
        <strong>Totales por medio de pago:</strong><br>
        Efectivo: ${{ number_format($totalesPorMetodo['efectivo'], 0, ',', '.') }}<br>
        Transferencia: ${{ number_format($totalesPorMetodo['transferencia'], 0, ',', '.') }}<br>
        ADDI: ${{ number_format($totalesPorMetodo['addi'], 0, ',', '.') }}<br>
        Sistecrédito: ${{ number_format($totalesPorMetodo['sistecredito'], 0, ',', '.') }}<br>
        Fiado: ${{ number_format($totalesPorMetodo['fiado'], 0, ',', '.') }}<br>
        Tarjeta (histórico): ${{ number_format($totalesPorMetodo['tarjeta'], 0, ',', '.') }}<br>
        <strong>Total: ${{ number_format($totalVentas, 0, ',', '.') }}</strong>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Método de pago</th>
                <th>Estado</th>
                <th>Productos</th>
                @can('reportes')
                    <th>Ganancia</th>
                @endcan
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
                <tr>
                    <td>{{ $venta->id }}</td>
                    <td>{{ $venta->cliente ?? '-' }}</td>
                    <td>{{ $venta->fecha }}</td>
                    <td>${{ number_format($venta->total, 0, ',', '.') }}</td>
                    <td>{{ $venta->metodo_pago === 'addi' ? 'ADDI' : ucfirst($venta->metodo_pago) }}</td>
                    <td>
                        @if($venta->estado === 'activa')
                            <span class="badge bg-success">Activa</span>
                        @else
                            <span class="badge bg-danger">Revocada</span>
                        @endif
                    </td>
                    <td>
                        @forelse($venta->detalles as $detalle)
                            {{ $detalle->producto?->nombre ?? '[producto eliminado]' }}
                            @if($detalle->talla_id)
                                - Talla {{ $detalle->talla?->numero ?? '-' }}
                            @endif
                            ({{ $detalle->cantidad }})@if(!$loop->last), @endif
                        @empty
                            -
                        @endforelse
                    </td>
                    @can('reportes')
                        @php($gananciaVenta = $gananciasPorVenta[$venta->id] ?? null)
                        <td>{{ $gananciaVenta === null ? '—' : '$' . number_format($gananciaVenta, 0, ',', '.') }}</td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No hay ventas en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
