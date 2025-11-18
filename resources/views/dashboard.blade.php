@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h2 class="mb-4">Dashboard</h2>

    <div class="card shadow-sm p-4">

        <!-- Tarjetas -->
        <div class="row">

            <div class="col-md-4">
                <div class="card-frstore p-4 text-center">
                    <h5>Total de ventas del día</h5>
                    <h2>${{ number_format($totalHoy, 0, ',', '.') }}</h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-frstore-dark p-4 text-center">
                    <h5>Cantidad de ventas</h5>
                    <h2>{{ $ventasHoy }}</h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-frstore p-4 text-center">
                    <h5>Productos con bajo stock</h5>
                    <h2>{{ $productosBajoStock }}</h2>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ultimasVentas as $venta)
                        <tr>
                            <td>{{ $venta->fecha }}</td>
                            <td>${{ number_format($venta->total, 2) }}</td>
                            <td>{{ ucfirst($venta->estado) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>
</div>
@endsection
