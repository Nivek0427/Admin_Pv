<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with('productoTallas.talla');

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));

            $query->where(function ($productoQuery) use ($buscar) {
                $productoQuery->where('nombre', 'like', "%{$buscar}%");

                if (ctype_digit($buscar)) {
                    $productoQuery->orWhere('id', (int) $buscar);
                }
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        $productos = $query->orderBy('id')->paginate(15)->withQueryString();

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

    public function actualizarTallas(Request $request, $id)
    {
        $validated = $request->validate([
            'ajustes' => 'required|array|min:1',
            'ajustes.*' => 'required|integer',
        ]);

        $ajustes = $validated['ajustes'];
        $tallaIds = array_map('strval', array_keys($ajustes));

        foreach ($tallaIds as $tallaId) {
            if (!ctype_digit($tallaId)) {
                throw ValidationException::withMessages([
                    'ajustes' => 'Una de las tallas seleccionadas no es válida.',
                ]);
            }
        }

        DB::transaction(function () use ($id, $ajustes, $tallaIds) {
            $producto = Producto::whereKey($id)->lockForUpdate()->firstOrFail();

            if (!$producto->esZapato()) {
                throw ValidationException::withMessages([
                    'ajustes' => 'La actualización múltiple solo aplica a productos Zapatos.',
                ]);
            }

            $productoTallas = $producto->productoTallas()
                ->whereIn('talla_id', $tallaIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('talla_id');

            if ($productoTallas->count() !== count($tallaIds)) {
                throw ValidationException::withMessages([
                    'ajustes' => 'Una de las tallas no pertenece a este producto.',
                ]);
            }

            foreach ($ajustes as $tallaId => $ajuste) {
                $ajuste = (int) $ajuste;
                $productoTalla = $productoTallas->get((int) $tallaId);

                if ($ajuste < 0 && $productoTalla->stock < abs($ajuste)) {
                    throw ValidationException::withMessages([
                        "ajustes.{$tallaId}" => 'La talla no tiene suficiente stock.',
                    ]);
                }
            }

            foreach ($ajustes as $tallaId => $ajuste) {
                $ajuste = (int) $ajuste;

                if ($ajuste === 0) {
                    continue;
                }

                if ($ajuste > 0) {
                    $producto->aumentarStockTalla((int) $tallaId, $ajuste);
                    $tipo = 'entrada';
                } else {
                    $producto->disminuirStockTalla((int) $tallaId, abs($ajuste));
                    $tipo = 'salida';
                }

                $producto->registrarMovimientoConTalla((int) $tallaId, $ajuste, $tipo);
            }

            $producto->sincronizarStockTotal();
        });

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
