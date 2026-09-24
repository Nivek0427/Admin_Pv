<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function index()
    {
        $productos = Producto::with('productoTallas.talla')->get();
        return view('inventario.index', compact('productos'));
    }

    public function updateCantidad(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|numeric',
            'talla_id' => 'nullable|integer|exists:tallas,id',
        ]);

        $producto = Producto::findOrFail($id);

        if ($producto->esZapato()) {
            $this->validarMovimientoPorTalla($request, $producto);
            $cantidad = (int) $request->cantidad;

            DB::transaction(function () use ($producto, $request, $cantidad) {
                if ($cantidad >= 0) {
                    $producto->aumentarStockTalla((int) $request->talla_id, $cantidad);
                    $tipo = 'entrada';
                } else {
                    $producto->disminuirStockTalla((int) $request->talla_id, abs($cantidad));
                    $tipo = 'salida';
                }

                $producto->sincronizarStockTotal();
                $producto->registrarMovimientoConTalla(
                    (int) $request->talla_id,
                    $cantidad,
                    $tipo
                );
            });
        } else {
            DB::transaction(function () use ($producto, $request) {
                $nuevoStock = $producto->stock + $request->cantidad;

                if ($nuevoStock < 0) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'El stock no puede quedar negativo.',
                    ]);
                }

                $producto->stock = $nuevoStock;
                $producto->save();
                $tipo = $request->cantidad > 0 ? 'entrada' : 'salida';
                $producto->registrarMovimiento($request->cantidad, $tipo);
            });
        }


        return redirect()
            ->route('inventario.index')
            ->with('success', 'Stock actualizado correctamente');
    }

    private function validarMovimientoPorTalla(Request $request, Producto $producto): void
    {
        $cantidad = $request->input('cantidad');

        if ($request->filled('talla_id') === false) {
            throw ValidationException::withMessages([
                'talla_id' => 'Debe seleccionar una talla.',
            ]);
        }

        if (filter_var($cantidad, FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages([
                'cantidad' => 'La cantidad para una talla debe ser un número entero.',
            ]);
        }

        $productoTalla = $producto->productoTallas()
            ->where('talla_id', $request->talla_id)
            ->with('talla')
            ->first();

        if (!$productoTalla) {
            throw ValidationException::withMessages([
                'talla_id' => 'La talla seleccionada no pertenece a este producto.',
            ]);
        }

        if ((int) $cantidad < 0 && $productoTalla->stock < abs((int) $cantidad)) {
            throw ValidationException::withMessages([
                'cantidad' => 'La talla no tiene suficiente stock.',
            ]);
        }
    }
}
