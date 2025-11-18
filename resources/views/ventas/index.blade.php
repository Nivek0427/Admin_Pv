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

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
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
