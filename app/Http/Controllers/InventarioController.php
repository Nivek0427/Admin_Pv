<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function index()
    {
        // Tomamos directamente los productos con su stock actual
        $productos = Producto::all();
        return view('inventario.index', compact('productos'));
    }

    public function updateCantidad(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|numeric'
        ]);

        $producto = Producto::findOrFail($id);
        $producto->stock += $request->cantidad;
        $producto->save();
        $tipo = $request->cantidad > 0 ? 'entrada' : 'salida';
        $producto->registrarMovimiento($request->cantidad, $tipo);


        return redirect()
            ->route('inventario.index')
            ->with('success', 'Stock actualizado correctamente');
    }
}
