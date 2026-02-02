@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h2 class="mb-4">Dashboard</h2>

    <div class="card shadow-sm p-4">

       <!-- Tarjetas -->
<div class="row g-3">

    <!-- Ventas en efectivo -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card-frstore p-3 text-center h-100">
            <h6>Ventas en efectivo</h6>
            <h4 class="mt-2">
                ${{ number_format($totalEfectivo, 0, ',', '.') }}
            </h4>
        </div>
    </div>

    <!-- Ventas por transferencia -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card-frstore-dark p-3 text-center h-100">
            <h6>Transferencia</h6>
            <h4 class="mt-2">
                ${{ number_format($totalTransferencia, 0, ',', '.') }}
            </h4>
        </div>
    </div>

    <!-- Ventas por tarjeta -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card-frstore p-3 text-center h-100">
            <h6>Tarjeta</h6>
            <h4 class="mt-2">
                ${{ number_format($totalTarjeta, 0, ',', '.') }}
            </h4>
        </div>
    </div>

    <!-- Ventas por SisteCredito -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card-frstore-dark p-3 text-center h-100">
            <h6>SisteCrédito</h6>
            <h4 class="mt-2">
                ${{ number_format($totalSisteCredito, 0, ',', '.') }}
            </h4>
        </div>
    </div>

    <!-- Cantidad de ventas -->
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="card-frstore p-3 text-center h-100">
            <h6># Ventas</h6>
            <h4 class="mt-2">{{ $ventasHoy }}</h4>
        </div>
    </div>

</div>




        <!-- Últimas Ventas -->
        <h5 class="mt-4">Últimas Ventas</h5>

        @if ($ventasHoy == 0)
            <div class="alert alert-light mt-2 text-center">
                No hay ventas recientes
            </div>
        @else
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Método de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ultimasVentas as $venta)
                        <tr>
                            <td>{{ $venta->fecha }}</td>
                            <td>${{ number_format($venta->total, 0, ',', '.') }}</td>
                            <td>{{ ucfirst($venta->estado) }}</td>
                            <td>{{ ucfirst($venta->metodo_pago) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
</div>
@endsection
