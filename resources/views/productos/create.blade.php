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
            <select name="categoria" id="categoria" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Camisas" {{ old('categoria') == 'Camisas' ? 'selected' : '' }}>Camisas</option>
                <option value="Pantalones" {{ old('categoria') == 'Pantalones' ? 'selected' : '' }}>Pantalones</option>
                <option value="Zapatos" {{ old('categoria') == 'Zapatos' ? 'selected' : '' }}>Zapatos</option>
                <option value="Gorras" {{ old('categoria') == 'Gorras' ? 'selected' : '' }}>Gorras</option>
                <option value="Accesorios" {{ old('categoria') == 'Accesorios' ? 'selected' : '' }}>Accesorios</option>
            </select>
        </div>

        <div class="mb-3" id="genero-group">
            <label class="form-label">Género</label>
            <select name="genero" id="genero" class="form-control">
                <option value="">Seleccione...</option>
                <option value="Hombre" {{ old('genero') == 'Hombre' ? 'selected' : '' }}>Hombre</option>
                <option value="Mujer" {{ old('genero') == 'Mujer' ? 'selected' : '' }}>Mujer</option>
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
            <label for="costo">Costo actual</label>
            <input type="number" name="costo" class="form-control" value="{{ old('costo') }}" min="0" step="0.01" required>
        </div>

        <div class="form-group mt-3" id="stock-general-group">
            <label for="stock">Stock inicial</label>
            <input type="number" name="stock" class="form-control" value="{{ old('stock', 0) }}" min="0" required>
        </div>

        <div class="form-group mt-3" id="tallas-container" style="display: none;">
            <label class="form-label">Tallas y stock</label>
            <div class="row">
                @foreach ($tallas as $talla)
                    @php
                        $tallaSeleccionada = in_array($talla->id, old('tallas', []));
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
                            value="{{ old('stock_tallas.' . $talla->id, 0) }}"
                            min="0"
                            step="1"
                            {{ $tallaSeleccionada ? '' : 'disabled' }}
                        >
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-success mt-4">
            <i class="fas fa-save"></i> Guardar Producto
        </button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary mt-4">Cancelar</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoria = document.getElementById('categoria');
    const tallasContainer = document.getElementById('tallas-container');
    const stockGeneralGroup = document.getElementById('stock-general-group');
    const generoGroup = document.getElementById('genero-group');
    const genero = document.getElementById('genero');
    const tallaOptions = document.querySelectorAll('.talla-option');

    function actualizarTallas() {
        const esZapatos = categoria.value === 'Zapatos';
        tallasContainer.style.display = esZapatos ? 'block' : 'none';
        stockGeneralGroup.style.display = esZapatos ? 'none' : 'block';
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
