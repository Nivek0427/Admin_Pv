@extends('layouts.app')

@section('title', 'Listado de Productos')

@section('content')
<div class="container">
    <h2 class="mb-4">Listado de Productos</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @can('productos.create')
    <div class="mb-3 text-end">
        <a href="{{ route('productos.create') }}" class="btn btn-primary"
                style="background-color:#FFD700; color:#000; font-weight:bold;">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
    </div>
    @endcan


    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        @can('acciones')
                        <th>Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td>{{ $producto->id }}</td>
                            <td>{{ $producto->nombre }}</td>
                            <td>{{ $producto->categoria ?? '—' }}</td>
                            <td>${{ number_format($producto->precio, 2) }}</td>
                            <td>{{ $producto->stock }}</td>
                            @can('acciones')
                            <td>
                                @can('productos.edit')
                                <a href="{{ route('productos.edit', $producto->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('productos.delete')
                                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay productos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Quita el efecto hover solo en el encabezado de la tabla */
    thead.table-dark tr:hover,
    thead.table-dark th:hover {
        background-color: #212529 !important; /* color original oscuro */
        color: #fff !important;
        cursor: default !important; /* evita el cambio de cursor */
    }
</style>
@endpush

