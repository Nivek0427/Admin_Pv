@extends('layouts.app')

@section('title', 'Realizar Venta')

@section('content')
<div class="container">
    <h2 class="mb-4">Realizar Venta</h2>

    <form action="{{ route('ventas.store') }}" method="POST" id="ventaForm">
        @csrf

        <!-- Selección de producto -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="producto" class="form-label">Producto</label>
                <select id="producto" name="producto_id" class="form-control">
                    <option value="">Seleccione un producto</option>
                    @foreach ($productos as $producto)
                        <option
                            value="{{ $producto->id }}"
                            data-nombre="{{ $producto->nombre }}"
                            data-precio="{{ $producto->precio }}"
                            data-stock="{{ $producto->stock }}">
                            {{ $producto->nombre }} (Stock: {{ $producto->stock }})
                        </option>
                    @endforeach
                </select>
                <small id="stock-info" class="text-muted d-block mt-2">Stock disponible: 0</small>
            </div>

            <div class="col-md-2">
                <label for="cantidad" class="form-label">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" min="1">

            </div>

            <div class="col-md-2">
                <label for="precio" class="form-label">Precio</label>
                <input type="number" id="precio" class="form-control">
            </div>

            <div class="col-md-3 d-flex align-items-end">
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
    const agregarBtn = document.getElementById('agregar');
    const detalleVenta = document.querySelector('#detalleVenta tbody');
    const totalVenta = document.getElementById('totalVenta');
    const productosInput = document.getElementById('productos'); // 👈 corregido aquí
    const stockInfo = document.getElementById('stock-info'); // 👈 id corregido a lo que usas en tu HTML

    let productos = [];

    // Mostrar stock y precio al seleccionar producto
    productoSelect.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stock = selected.getAttribute('data-stock') || 0;

    // Obtener precio como número
    let precio = selected.getAttribute('data-precio') || '';
    precio = parseFloat(precio);

    // Mostrar stock
    stockInfo.textContent = 'Stock disponible: ' + stock;

    // Mostrar precio formateado en el input
    if (!isNaN(precio)) {
        precioInput.value = precio.toLocaleString('es-CO');
    } else {
        precioInput.value = '';
    }
});

    // Agregar producto
    agregarBtn.addEventListener('click', function() {
        const selected = productoSelect.options[productoSelect.selectedIndex];
        const id = selected.value;
        const nombre = selected.getAttribute('data-nombre');
        const precio = parseFloat(precioInput.value.replace(/\./g, '').replace(/,/g, '.'));
        const cantidad = parseInt(cantidadInput.value);
        const stock = parseInt(selected.getAttribute('data-stock'));

        if (!id) {
            alert('Seleccione un producto.');
            return;
        }
        if (isNaN(precio) || precio <= 0 || isNaN(cantidad) || cantidad <= 0) {
            alert('Ingrese una cantidad y precio válidos.');
            return;
        }
        if (cantidad > stock) {
            alert('No puedes vender más unidades de las disponibles.');
            return;
        }

        let existente = productos.find(p => p.id == id);
        if (existente) {
            if (existente.cantidad + cantidad > stock) {
                alert('No puedes superar el stock disponible.');
                return;
            }
            existente.cantidad += cantidad;
            existente.subtotal = existente.cantidad * existente.precio;
        } else {
            productos.push({
                id: parseInt(id),
                nombre,
                precio,
                cantidad,
                subtotal: precio * cantidad
            });
        }

        renderTabla();
    });

    // Renderizar tabla
    function renderTabla() {
        detalleVenta.innerHTML = '';
        let total = 0;

        productos.forEach((p, index) => {
            total += p.subtotal;
            detalleVenta.innerHTML += `
                <tr>
                    <td>${p.nombre}</td>
                    <td>${p.precio.toLocaleString('es-CO')}</td>
                    <td>${p.cantidad}</td>
                    <td>${p.subtotal.toLocaleString('es-CO')}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminar(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
        });

        totalVenta.textContent = total.toLocaleString('es-CO');
        productosInput.value = JSON.stringify(productos); // 👈 este campo es el que se envía al backend
    }

    // Eliminar producto
    window.eliminar = function(index) {
        productos.splice(index, 1);
        renderTabla();
    };
});
</script>
@endsection
