@extends('layouts.app')

@section('title', 'Listado de Productos')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start mb-4">
        <div class="mb-3 mb-sm-0">
            <h1 class="fr-page-title">Productos</h1>
            <p class="fr-text-muted mb-0">Consulta y administra el catálogo de productos.</p>
        </div>

        @can('productos.create')
            <a href="{{ route('productos.create') }}" class="btn-fr-primary">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('productos.index') }}" class="fr-filters mb-3">
        <div class="fr-filter-field">
            <label for="buscar" class="form-label">Buscar por nombre o ID</label>
            <input type="search" name="buscar" id="buscar" class="form-control form-control-fr"
                value="{{ request('buscar') }}" placeholder="Ej. Nike o 12">
        </div>
        <div class="fr-filter-field">
            <label for="categoria" class="form-label">Categoría</label>
            <select name="categoria" id="categoria" class="form-control form-control-fr">
                <option value="">Todas</option>
                <option value="Camisas" {{ request('categoria') === 'Camisas' ? 'selected' : '' }}>Camisas</option>
                <option value="Pantalones" {{ request('categoria') === 'Pantalones' ? 'selected' : '' }}>Pantalones</option>
                <option value="Zapatos" {{ request('categoria') === 'Zapatos' ? 'selected' : '' }}>Zapatos</option>
                <option value="Gorras" {{ request('categoria') === 'Gorras' ? 'selected' : '' }}>Gorras</option>
                <option value="Accesorios" {{ request('categoria') === 'Accesorios' ? 'selected' : '' }}>Accesorios</option>
            </select>
        </div>
        <div class="fr-filter-actions">
            <button type="submit" class="btn-fr-primary">Buscar</button>
            <a href="{{ route('productos.index') }}" class="btn-fr-secondary">Limpiar</a>
        </div>
    </form>

    <div class="fr-card">
        <div class="fr-card-body">
            <div class="table-responsive">
                <table class="fr-table">
                <thead class="table-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Género</th>
                        <th>Precio</th>
                        @can('productos.verstock')<th>Stock</th>@endcan
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
                            <td>{{ $producto->genero }}</td>
                            <td>${{ number_format($producto->precio, 2) }}</td>
                            @can('productos.verstock')<td>{{ $producto->stock }}</td>@endcan
                            @can('acciones')
                            <td>
                                @can('productos.edit')
                                <a href="{{ route('productos.edit', $producto->id) }}" class="btn-fr-warning btn-fr-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('productos.delete')
                                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-fr-danger btn-fr-sm" onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="fr-table-empty">No hay productos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
            <div class="pagination-wrapper">
                {{ $productos->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

