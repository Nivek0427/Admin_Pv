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
              <option value="Camisas" {{ old('categoria', $producto->categoria) == 'Camisas' ? 'selected' : '' }}>Camisas</option>
              <option value="Pantalones" {{ old('categoria', $producto->categoria) == 'Pantalones' ? 'selected' : '' }}>Pantalones</option>
                <option value="Zapatos" {{ old('categoria', $producto->categoria) == 'Zapatos' ? 'selected' : '' }}>Zapatos</option>
              <option value="Gorras" {{ old('categoria', $producto->categoria) == 'Gorras' ? 'selected' : '' }}>Gorras</option>
                <option value="Accesorios" {{ old('categoria', $producto->categoria) == 'Accesorios' ? 'selected' : '' }}>Accesorios</option>
            </select>
        </div>

          <div class="mb-3" id="genero-group">
            <label class="form-label">Género</label>
            <select name="genero" id="genero" class="form-control">
                <option value="">Seleccione...</option>
              <option value="Hombre" {{ old('genero', $producto->genero) == 'Hombre' ? 'selected' : '' }}>Hombre</option>
              <option value="Mujer" {{ old('genero', $producto->genero) == 'Mujer' ? 'selected' : '' }}>Mujer</option>
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

        <div class="mb-3">
          <label class="form-label">Costo actual</label>
          <input type="number" name="costo" class="form-control" min="0" step="0.01" value="{{ old('costo', $producto->costo) }}" required>
        </div>

        <div class="form-group mt-3" id="tallas-container" style="display: none;">
          <label class="form-label">Tallas y stock</label>
          <div class="row">
            @foreach ($tallas as $talla)
              @php
                $tallaSeleccionada = in_array($talla->id, $tallasSeleccionadas);
              @endphp
              <div class="col-md-3 mb-3 talla-option" data-numero="{{ $talla->numero }}">
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
  const generoGroup = document.getElementById('genero-group');
  const genero = document.getElementById('genero');
  const tallaOptions = document.querySelectorAll('.talla-option');

  function actualizarTallas() {
    const esZapatos = categoria.value === 'Zapatos';
    tallasContainer.style.display = esZapatos ? 'block' : 'none';
    generoGroup.style.display = esZapatos ? 'block' : 'none';
    genero.disabled = !esZapatos;

    const rango = genero.value === 'Mujer' ? [36, 40] : genero.value === 'Hombre' ? [38, 44] : null;

    tallaOptions.forEach(function (option) {
      const checkbox = option.querySelector('.talla-checkbox');
      const stock = option.querySelector('.talla-stock');
      const numero = Number(option.dataset.numero);
      const enRango = rango && numero >= rango[0] && numero <= rango[1];
      const disponible = esZapatos && enRango;

      option.style.display = disponible ? '' : 'none';
      if (esZapatos && !enRango) {
        checkbox.checked = false;
      }
      checkbox.disabled = !disponible;
      stock.disabled = !disponible || !checkbox.checked;
    });
  }

  document.querySelectorAll('.talla-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
      actualizarTallas();
    });
  });

  categoria.addEventListener('change', actualizarTallas);
  genero.addEventListener('change', actualizarTallas);
  actualizarTallas();
});
</script>
@endsection
