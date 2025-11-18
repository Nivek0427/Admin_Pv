@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header bg-warning text-dark">
      <h4 class="mb-0">Editar producto</h4>
    </div>
    <div class="card-body">
      <form action="{{ route('productos.update', $producto) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="{{ $producto->nombre }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Categoría</label>
          <input type="text" name="categoria" class="form-control" value="{{ $producto->categoria }}">
        </div>

        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3">{{ $producto->descripcion }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Precio</label>
          <input type="number" name="precio" class="form-control" step="0.01" value="{{ $producto->precio }}" required>
        </div>

        <button type="submit" class="btn btn-warning">
          <i class="fas fa-save"></i> Actualizar
        </button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Volver
        </a>
      </form>
    </div>
  </div>
</div>
@endsection
