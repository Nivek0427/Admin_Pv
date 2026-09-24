@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
@php
  $tallasSeleccionadas = old('tallas', $producto->productoTallas->pluck('talla_id')->toArray());
  $stocksTallas = old('stock_tallas', $producto->productoTallas->pluck('stock', 'talla_id')->toArray());
@endphp
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
            <label class="form-label">Categoria</label>
            <select name="categoria" id="categoria" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Ropa" {{ old('categoria', $producto->categoria) == 'Ropa' ? 'selected' : '' }}>Ropa</option>
                <option value="Accesorio" {{ old('categoria', $producto->categoria) == 'Accesorio' ? 'selected' : '' }}>Accesorio</option>
                <option value="Zapatos" {{ old('categoria', $producto->categoria) == 'Zapatos' ? 'selected' : '' }}>Zapatos</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Género</label>
            <select name="genero" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Hombre" {{ $producto->genero == 'Hombre' ? 'selected' : '' }}>Hombre</option>
                <option value="Mujer" {{ $producto->genero == 'Mujer' ? 'selected' : '' }}>Mujer</option>
                <option value="Unisex" {{ $producto->genero == 'Unisex' ? 'selected' : '' }}>Unisex</option>
            </select>
        </div>


        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3">{{ $producto->descripcion }}</textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Precio</label>
          <input type="number" name="precio" class="form-control" step="0.01" value="{{ $producto->precio }}" required>
        </div>

        <div class="form-group mt-3" id="tallas-container" style="display: none;">
          <label class="form-label">Tallas y stock</label>
          <div class="row">
            @foreach ($tallas as $talla)
              @php
                $tallaSeleccionada = in_array($talla->id, $tallasSeleccionadas);
              @endphp
              <div class="col-md-3 mb-3">
                <div class="form-check">
                  <input
                    type="checkbox"
                    class="form-check-input talla-checkbox"
                    name="tallas[]"
                    value="{{ $talla->id }}"
                    id="talla-{{ $talla->id }}"
                    {{ $tallaSeleccionada ? 'checked' : '' }}
                  >
                  <label class="form-check-label" for="talla-{{ $talla->id }}">
                    {{ $talla->numero }}
                  </label>
                </div>
                <input
                  type="number"
                  class="form-control mt-2 talla-stock"
                  name="stock_tallas[{{ $talla->id }}]"
                  value="{{ $stocksTallas[$talla->id] ?? 0 }}"
                  min="0"
                  step="1"
                  {{ $tallaSeleccionada ? '' : 'disabled' }}
                >
              </div>
            @endforeach
          </div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  const categoria = document.getElementById('categoria');
  const tallasContainer = document.getElementById('tallas-container');

  function actualizarTallas() {
    tallasContainer.style.display = categoria.value === 'Zapatos' ? 'block' : 'none';
  }

  document.querySelectorAll('.talla-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
      this.closest('.form-check').nextElementSibling.disabled = !this.checked;
    });
  });

  categoria.addEventListener('change', actualizarTallas);
  actualizarTallas();
});
</script>
@endsection
