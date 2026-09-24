@extends('layouts.app')

@section('title', 'Realizar Venta')

@section('content')
<div class="container">
    <h2 class="mb-4">Realizar Venta</h2>

    <form action="{{ route('ventas.store') }}" method="POST" id="ventaForm">
        @csrf

       <div class="row mb-3">

        <div class="col-md-6">
            <label for="cliente" class="form-label">Cliente</label>
            <input
                type="text"
                name="cliente"
                id="cliente"
                class="form-control"
                placeholder="Juan Pérez"
            >
        </div>

        @role('admin')
        <div class="col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input
                type="date"
                name="fecha"
                id="fecha"
                class="form-control"
                value="{{ now()->toDateString() }}"
                max="{{ now()->toDateString() }}"
            >
        </div>
        @endrole

    </div>



        <!-- Selección de producto -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="producto" class="form-label">Producto</label>
                <select id="producto" name="producto_id" class="form-control">
                    <option value="">Seleccione un producto</option>
                    @foreach ($productos as $producto)
                        @php
                            $tallasParaVenta = $producto->productoTallas->map(function ($productoTalla) {
                                return [
                                    'id' => $productoTalla->talla_id,
                                    'numero' => $productoTalla->talla->numero,
                                    'stock' => $productoTalla->stock,
                                ];
                            })->values();
                        @endphp
                        <option
                            value="{{ $producto->id }}"
                            data-nombre="{{ $producto->nombre }}"
                            data-precio="{{ $producto->precio }}"
                            data-stock="{{ $producto->stock }}"
                            data-es-zapato="{{ $producto->esZapato() ? '1' : '0' }}"
                            data-tallas='@json($tallasParaVenta)'>
                            {{ $producto->nombre }}
                        </option>
                    @endforeach
                </select>
                @can('productos.verstock')
                    <small id="stock-info" class="text-muted d-block mt-2">Stock disponible: 0</small>
                @else
                    <small id="stock-info" class="d-none"></small>
                @endcan
            </div>

            <div class="col-md-2">
                <label for="cantidad" class="form-label">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" min="1">

            </div>

            <div class="col-md-3" id="talla-container" style="display:none">
                <label for="talla_id" class="form-label">Talla</label>
                <select id="talla_id" class="form-control">
                    <option value="">Seleccione una talla</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="precio" class="form-label">Precio</label>
                <input type="number" id="precio" class="form-control">
            </div>



            <div class="col-md-3">
                <label for="metodo_pago" class="form-label mt-3">Método de pago</label>
                <select name="metodo_pago" id="metodo_pago" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="sistecredito">Sistecrédito</option>
                    <option value="Fiado">Fiado</option>
                </select>
            </div>

            <div class="col-md-3" id="banco_container" style="display:none">
                <label class="form-label mt-3">Banco</label>
                <select name="banco_id" class="form-control">
                    <option value="">Seleccione banco</option>
                    @foreach($bancos as $banco)
                        <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="agregar" class="btn btn-success w-100">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
        </div>

        <p id="stock-info-extra" class="text-muted"></p>

        <!-- Tabla de productos agregados -->
        <table class="table table-bordered mt-4" id="detalleVenta">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td id="totalVenta">0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Campos ocultos -->
        <input type="hidden" name="productos" id="productos">

        <div class="mt-3 d-flex">
            <button type="submit" class="btn btn-primary"
             style="background-color:#FFD700; color:#000; font-weight:bold;">Guardar Venta</button>
            <a href="{{ route('ventas.index') }}" class="btn btn-secondary" style="margin-left: 10px;">Volver</a>
        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productoSelect = document.getElementById('producto');
    const cantidadInput = document.getElementById('cantidad');
    const precioInput = document.getElementById('precio');
    const tallaContainer = document.getElementById('talla-container');
    const tallaSelect = document.getElementById('talla_id');
    const agregarBtn = document.getElementById('agregar');
    const detalleVenta = document.querySelector('#detalleVenta tbody');
    const totalVenta = document.getElementById('totalVenta');
    const productosInput = document.getElementById('productos');
    const stockInfo = document.getElementById('stock-info');

    let productos = [];

    function productoSeleccionado() {
        return productoSelect.options[productoSelect.selectedIndex];
    }

    function obtenerTallas(option) {
        try {
            return JSON.parse(option.getAttribute('data-tallas') || '[]');
        } catch (error) {
            return [];
        }
    }

    function actualizarProducto() {
        const selected = productoSeleccionado();
        const esZapato = selected.getAttribute('data-es-zapato') === '1';
        const stock = selected.getAttribute('data-stock') || 0;
        const precio = parseFloat(selected.getAttribute('data-precio') || '');

        tallaContainer.style.display = esZapato ? 'block' : 'none';
        tallaSelect.innerHTML = '<option value="">Seleccione una talla</option>';

        if (esZapato) {
            obtenerTallas(selected).forEach(function (talla) {
                const option = document.createElement('option');
                option.value = talla.id;
                option.dataset.stock = talla.stock;
                option.textContent = talla.numero + ' - Stock: ' + talla.stock;
                tallaSelect.appendChild(option);
            });
            stockInfo.textContent = 'Seleccione una talla para consultar su stock.';
        } else {
            stockInfo.textContent = 'Stock disponible: ' + stock;
        }

        precioInput.value = !isNaN(precio) ? precio.toLocaleString('es-CO') : '';
    }

    function stockDisponible(id, tallaId) {
        const selected = productoSeleccionado();
        if (selected.getAttribute('data-es-zapato') !== '1') {
            return parseInt(selected.getAttribute('data-stock'), 10) || 0;
        }

        const talla = obtenerTallas(selected).find(function (item) {
            return String(item.id) === String(tallaId);
        });

        return talla ? parseInt(talla.stock, 10) : 0;
    }

    tallaSelect.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        stockInfo.textContent = option.value
            ? 'Stock disponible: ' + option.dataset.stock
            : 'Seleccione una talla para consultar su stock.';
    });

    productoSelect.addEventListener('change', actualizarProducto);

    agregarBtn.addEventListener('click', function() {
        const selected = productoSeleccionado();
        const id = selected.value;
        const nombre = selected.getAttribute('data-nombre');
        const esZapato = selected.getAttribute('data-es-zapato') === '1';
        const tallaId = esZapato ? tallaSelect.value : null;
        const tallaNumero = esZapato ? tallaSelect.options[tallaSelect.selectedIndex]?.textContent.split(' - ')[0] : null;
        const precio = parseFloat(precioInput.value.replace(/\./g, '').replace(/,/g, '.'));
        const cantidad = parseInt(cantidadInput.value, 10);

        if (!id) {
            alert('Seleccione un producto.');
            return;
        }
        if (esZapato && !tallaId) {
            alert('Seleccione una talla.');
            return;
        }
        if (isNaN(precio) || precio <= 0 || isNaN(cantidad) || cantidad <= 0) {
            alert('Ingrese una cantidad y precio válidos.');
            return;
        }

        const existente = productos.find(function (producto) {
            return producto.id === parseInt(id, 10) && String(producto.talla_id) === String(tallaId);
        });
        const cantidadActual = existente ? existente.cantidad : 0;

        if (cantidadActual + cantidad > stockDisponible(id, tallaId)) {
            alert('No puedes superar el stock disponible.');
            return;
        }

        if (existente) {
            existente.cantidad += cantidad;
            existente.subtotal = existente.cantidad * existente.precio;
        } else {
            productos.push({
                id: parseInt(id, 10),
                talla_id: tallaId ? parseInt(tallaId, 10) : null,
                talla_numero: tallaNumero,
                nombre,
                precio,
                cantidad,
                subtotal: precio * cantidad
            });
        }

        renderTabla();
    });

    function renderTabla() {
        detalleVenta.innerHTML = '';
        let total = 0;

        productos.forEach(function (producto, index) {
            total += producto.subtotal;
            const nombre = producto.talla_numero
                ? producto.nombre + ' - Talla ' + producto.talla_numero
                : producto.nombre;
            detalleVenta.innerHTML += `
                <tr>
                    <td>${nombre}</td>
                    <td>${producto.precio.toLocaleString('es-CO')}</td>
                    <td>${producto.cantidad}</td>
                    <td>${producto.subtotal.toLocaleString('es-CO')}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminar(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });

        totalVenta.textContent = total.toLocaleString('es-CO');
        productosInput.value = JSON.stringify(productos);
    }

    window.eliminar = function(index) {
        productos.splice(index, 1);
        renderTabla();
    };

    const metodoPago = document.getElementById('metodo_pago');
    const bancoContainer = document.getElementById('banco_container');

    metodoPago.addEventListener('change', function () {
        bancoContainer.style.display = this.value === 'transferencia' ? 'block' : 'none';
    });

    actualizarProducto();
});
</script>
@endsection
