@extends('layouts.app')

@section('title', 'Registrar Producto')

@section('content')
<div class="container">
    <h2 class="mb-4">Registrar Producto</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Ups!</strong> Hay algunos errores:<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('productos.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="nombre">Nombre del producto</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select name="categoria" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Ropa">Ropa</option>
                <option value="Accesorio">Accesorio</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Género</label>
            <select name="genero" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Hombre">Hombre</option>
                <option value="Mujer">Mujer</option>
                <option value="Unisex">Unisex</option>
            </select>
        </div>


        <div class="form-group mt-3">
            <label for="descripcion">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion') }}</textarea>
        </div>

        <div class="form-group mt-3">
            <label for="precio">Precio</label>
            <input type="number" name="precio" class="form-control" value="{{ old('precio') }}" min="0" step="0.01" required>
        </div>

        <div class="form-group mt-3">
            <label for="stock">Stock inicial</label>
            <input type="number" name="stock" class="form-control" value="{{ old('stock', 0) }}" min="0" required>
        </div>

        <button type="submit" class="btn btn-success mt-4">
            <i class="fas fa-save"></i> Guardar Producto
        </button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary mt-4">Cancelar</a>
    </form>
</div>
@endsection
