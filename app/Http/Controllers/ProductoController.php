<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // Mostrar todos los productos
    public function index()
    {
        $productos = Producto::all();
        return view('productos.index', compact('productos'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        return view('productos.create');
    }

    // Guardar producto nuevo
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'precio' => 'required|numeric|min:0',
        'categoria' => 'nullable|string|max:255',
    ]);

    $producto = Producto::create($request->all());

    // 🔹 Crear registro inicial en inventario automáticamente
    \App\Models\Inventario::create([
        'producto_id' => $producto->id,
        'cantidad' => $producto->stock,
        'tipo_movimiento' => 'Creación',
        'descripcion' => 'Registro automático al crear producto'
    ]);

    return redirect()->route('productos.index')
        ->with('success', 'Producto creado correctamente y registrado en inventario.');
}

    // Mostrar un producto
    public function show(Producto $producto)
    {
        return view('productos.show', compact('producto'));
    }

    // Mostrar formulario de edición
    public function edit(Producto $producto)
    {
        return view('productos.edit', compact('producto'));
    }

    // Actualizar producto
    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'genero' => 'required|string|max:255',
        ]);

        $producto->update($validated);

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    // Eliminar producto
    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }
}
