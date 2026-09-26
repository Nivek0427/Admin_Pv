@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="fr-page-title">Reportes de Ventas</h1>
        <p class="fr-text-muted mb-0">Consulta resultados de ventas por fecha, estado y método de pago.</p>
    </div>

    <form method="GET" class="fr-filters mb-3">

    <div class="fr-filter-field">
        <label for="desde" class="form-label">Desde</label>
        <input type="date" name="desde" id="desde" class="form-control form-control-fr" value="{{ request('desde') }}">
    </div>

    <div class="fr-filter-field">
        <label for="hasta" class="form-label">Hasta</label>
        <input type="date" name="hasta" id="hasta" class="form-control form-control-fr" value="{{ request('hasta') }}">
    </div>

    <div class="fr-filter-field">
        <label for="tipo" class="form-label">Filtro rápido</label>
        <select name="tipo" id="tipo" class="form-control form-control-fr">
            <option value="">-- Seleccionar --</option>
            <option value="dia" {{ request('tipo')=='dia'?'selected':'' }}>Hoy</option>
            <option value="ayer" {{ request('tipo')=='ayer'?'selected':'' }}>Ayer</option>
            <option value="semana" {{ request('tipo')=='semana'?'selected':'' }}>Esta semana</option>
            <option value="mes" {{ request('tipo')=='mes'?'selected':'' }}>Este mes</option>
        </select>
    </div>

    <div class="fr-filter-field">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-control form-control-fr">
            <option value="">Todos</option>
            <option value="activa" {{ request('estado')=='activa'?'selected':'' }}>Activas</option>
            <option value="revocada" {{ request('estado')=='revocada'?'selected':'' }}>Revocadas</option>
        </select>
    </div>

    <div class="fr-filter-field">
        <label class="form-label">Método de pago</label>
        <select name="metodo_pago" class="form-control form-control-fr">
            <option value="">Todos</option>
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="addi">ADDI</option>
            <option value="sistecredito">Sistecrédito</option>
        </select>
    </div>


    <div class="fr-filter-actions">
        <button class="btn-fr-primary">Filtrar</button>
        <a href="{{ route('reportes.index') }}" class="btn-fr-secondary">Limpiar</a>
    </div>
</form>

<form action="{{ route('reportes.pdf') }}" method="GET" target="_blank" class="d-flex justify-content-end mb-3">
    <input type="hidden" name="tipo" value="{{ request('tipo') }}">
    <input type="hidden" name="estado" value="{{ request('estado') }}">
    <input type="hidden" name="metodo_pago" value="{{ request('metodo_pago') }}">
    <input type="hidden" name="desde" value="{{ request('desde') }}">
    <input type="hidden" name="hasta" value="{{ request('hasta') }}">

    <button class="btn-fr-danger">Generar PDF</button>
</form>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="fr-card h-100" style="background-color: #F4E3A1;">
                <div class="fr-card-body">
                    <div class="fr-text-muted">Total ventas activas</div>
                    <div class="fr-page-title mt-2">${{ number_format($totalVentas, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="fr-card h-100" style="background-color: #C6DDF2;">
                <div class="fr-card-body">
                    <div class="fr-text-muted">Ventas activas</div>
                    <div class="fr-page-title mt-2">{{ $ventasActivas }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="fr-card h-100" style="background-color: #F1C9C7;">
                <div class="fr-card-body">
                    <div class="fr-text-muted">Ventas revocadas</div>
                    <div class="fr-page-title mt-2">{{ $ventasRevocadas }}</div>
                </div>
            </div>
        </div>
    </div>

    @can('reportes')
        <div class="fr-card mb-3" style="background-color: #C8E6D0;">
            <div class="fr-card-body">
                <div class="fr-text-muted">Ganancia calculada:</div>
                <div class="fr-page-title mt-2">${{ number_format($gananciaTotal, 0, ',', '.') }}</div>
                @if ($hayDetallesSinCostoHistorico)
                    <small class="fr-text-muted">* La ganancia corresponde únicamente a detalles con costo histórico registrado.</small>
                @endif
            </div>
        </div>
    @endcan

    <div class="fr-card mb-3">
        <div class="fr-card-header">Totales por medio de pago</div>
        <div class="fr-card-body">
            <div class="row">
                <div class="col-sm-6 col-lg-4 mb-2">Efectivo <strong class="float-right">${{ number_format($totalesPorMetodo['efectivo'], 0, ',', '.') }}</strong></div>
                <div class="col-sm-6 col-lg-4 mb-2">Transferencia <strong class="float-right">${{ number_format($totalesPorMetodo['transferencia'], 0, ',', '.') }}</strong></div>
                <div class="col-sm-6 col-lg-4 mb-2">ADDI <strong class="float-right">${{ number_format($totalesPorMetodo['addi'], 0, ',', '.') }}</strong></div>
                <div class="col-sm-6 col-lg-4 mb-2">Sistecrédito <strong class="float-right">${{ number_format($totalesPorMetodo['sistecredito'], 0, ',', '.') }}</strong></div>
                <div class="col-sm-6 col-lg-4 mb-2">Fiado <strong class="float-right">${{ number_format($totalesPorMetodo['fiado'], 0, ',', '.') }}</strong></div>
            </div>
            <div class="border-top pt-2 mt-2 text-right">
                <strong>Total: ${{ number_format($totalVentas, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="fr-card">
      <div class="fr-card-body">
       <div class="table-responsive">
    <table class="table table-bordered table-striped fr-table">
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
                            <span class="fr-badge fr-badge-active">Activa</span>
                        @else
                            <span class="fr-badge fr-badge-revoked">Revocada</span>
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
                <tr><td colspan="8" class="fr-table-empty">No hay ventas en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>
       </div>
      </div>
    </div>
</div>
@endsection
