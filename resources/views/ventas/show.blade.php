@extends('layouts.app')

@section('title', 'Detalle de Venta')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="fr-page-title">Detalle de Venta #{{ $venta->id }}</h1>
        <p class="fr-text-muted mb-0">Resumen de la venta y sus productos.</p>
    </div>

    <div class="fr-card mb-3">
        <div class="fr-card-body d-flex flex-wrap">
            <p class="mb-2 mr-4"><strong>Cliente:</strong> {{ $venta->cliente ?? 'Cliente general' }}</p>
            <p class="mb-2 mr-4"><strong>Total:</strong> ${{ number_format($venta->total, 2) }}</p>
            <p class="mb-2 mr-4"><strong>Fecha:</strong> {{ $venta->created_at->format('d/m/Y H:i') }}</p>
            <p class="mb-2"><strong>Método de pago:</strong> {{ $venta->metodo_pago === 'addi' ? 'ADDI' : ucfirst($venta->metodo_pago) }}</p>
        </div>
    </div>

    @if ($venta->estado === 'revocada')
    <div class="alert alert-warning border rounded shadow-sm mt-3">
        <strong>Venta revocada.</strong>

        <div class="mt-1">
            <b>Fecha:</b> {{ $venta->revocada_fecha ? $venta->revocada_fecha->format('d/m/Y H:i') : '—' }} <br>
            <b>Motivo:</b> {{ $venta->revocada_motivo ?? 'Sin motivo registrado' }}
        </div>
    </div>
    @endif


    <div class="fr-card mt-3">
        <div class="fr-card-body table-responsive">
            @role('admin')
                @php
                    $costoTotalVenta = 0;
                    $gananciaTotal = 0;
                    $tieneDetallesSinCosto = false;
                @endphp
            @endrole
            <table class="table table-striped fr-table fr-table-sales">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Género</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        @role('admin')
                            <th>Costo unitario</th>
                            <th>Costo total</th>
                        @endrole
                        <th>Subtotal</th>
                        @role('admin')
                            <th>Ganancia</th>
                        @endrole
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                        @role('admin')
                            @php
                                $tieneCosto = $detalle->costo_unitario !== null;
                                $costoTotalDetalle = $tieneCosto
                                    ? $detalle->costo_unitario * $detalle->cantidad
                                    : null;
                                $gananciaDetalle = $tieneCosto
                                    ? ($detalle->precio_unitario - $detalle->costo_unitario) * $detalle->cantidad
                                    : null;

                                if ($tieneCosto) {
                                    $costoTotalVenta += $costoTotalDetalle;
                                    $gananciaTotal += $gananciaDetalle;
                                } else {
                                    $tieneDetallesSinCosto = true;
                                }
                            @endphp
                        @endrole
                        <tr>
                            <td>
                                {{ $detalle->producto->nombre }}
                                @if($detalle->talla_id)
                                    - Talla {{ $detalle->talla?->numero ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $detalle->producto->genero }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                            @role('admin')
                                <td>{{ $tieneCosto ? '$' . number_format($detalle->costo_unitario, 2) : '—' }}</td>
                                <td>{{ $tieneCosto ? '$' . number_format($costoTotalDetalle, 2) : '—' }}</td>
                            @endrole
                            <td>${{ number_format($detalle->subtotal, 2) }}</td>
                            @role('admin')
                                <td>{{ $tieneCosto ? '$' . number_format($gananciaDetalle, 2) : '—' }}</td>
                            @endrole
                        </tr>
                    @endforeach
                </tbody>
                @role('admin')
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-end">Total venta:</th>
                            <th>${{ number_format($venta->total, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="7" class="text-end">Costo total:</th>
                            <th>
                                {{ $tieneDetallesSinCosto ? '—' : '$' . number_format($costoTotalVenta, 2) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="7" class="text-end">Ganancia total:</th>
                            <th>
                                {{ $tieneDetallesSinCosto ? '—' : '$' . number_format($gananciaTotal, 2) }}
                            </th>
                        </tr>
                        @if($tieneDetallesSinCosto)
                            <tr>
                                <td colspan="8" class="text-muted">
                                    Esta venta tiene detalles sin costo registrado; no se puede mostrar una ganancia total completa.
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                @endrole
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('ventas.index') }}" class="btn-fr-secondary">Volver</a>

        @if($venta->estado !== 'revocada')
            <!-- Formulario pequeño para revocar y enviar una razón opcional -->
            <form id="revocarForm" action="{{ route('ventas.revocar', $venta->id) }}" method="POST" class="d-inline">
    @csrf
    @method('PUT')

    <input type="hidden" name="reason" id="reasonInput">

    @can('ventas.revocar')
    <button type="button" class="btn-fr-warning" onclick="revocarVenta()">
        Revocar venta
    </button>
    @endcan
</form>

<script>
function revocarVenta() {
    if (!confirm('¿Confirmar revocación de la venta?')) {
        return;
    }

    let motivo = prompt('Ingrese el motivo de la revocación (opcional):');

    // Si cancela el prompt → no hacer nada
    if (motivo === null) {
        return;
    }

    // Guardar el motivo en el input oculto
    document.getElementById('reasonInput').value = motivo.trim() !== '' ? motivo : 'Sin motivo';

    // Enviar el formulario
    document.getElementById('revocarForm').submit();
}
</script>

        @endif
    </div>
</div>
@endsection
