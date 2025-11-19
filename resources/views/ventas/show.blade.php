@extends('layouts.app')

@section('title', 'Detalle de Venta')

@section('content')
<div class="container">
    <h2 class="mb-3">Detalle de Venta #{{ $venta->id }}</h2>

    <p><strong>Cliente:</strong> {{ $venta->cliente ?? 'Cliente general' }}</p>
    <p><strong>Total:</strong> ${{ number_format($venta->total, 2) }}</p>
    <p><strong>Fecha:</strong> {{ $venta->created_at->format('d/m/Y H:i') }}</p>
    <p><strong>Método de pago:</strong> {{ ucfirst($venta->metodo_pago) }}</p>

    @if ($venta->estado === 'revocada')
    <div class="alert alert-warning border rounded shadow-sm mt-3">
        <strong>Venta revocada.</strong>

        <div class="mt-1">
            <b>Fecha:</b> {{ $venta->revocada_fecha ? $venta->revocada_fecha->format('d/m/Y H:i') : '—' }} <br>
            <b>Motivo:</b> {{ $venta->revocada_motivo ?? 'Sin motivo registrado' }}
        </div>
    </div>
    @endif


    <div class="card mt-3">
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->nombre }}</td>
                            <td>{{ $detalle->cantidad }}</td>
                            <td>${{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td>${{ number_format($detalle->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Volver</a>

        @if($venta->estado !== 'revocada')
            <!-- Formulario pequeño para revocar y enviar una razón opcional -->
            <form id="revocarForm" action="{{ route('ventas.revocar', $venta->id) }}" method="POST" class="d-inline">
    @csrf
    @method('PUT')

    <input type="hidden" name="reason" id="reasonInput">

    @can('ventas.revocar')
    <button type="button" class="btn btn-warning" onclick="revocarVenta()">
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
