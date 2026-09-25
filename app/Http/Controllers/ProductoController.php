<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoTalla;
use App\Models\Talla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    // Mostrar todos los productos
    public function index(Request $request)
    {
        $query = Producto::query();

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

        return view('productos.index', compact('productos'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        $tallas = Talla::orderBy('numero')->get();

        return view('productos.create', compact('tallas'));
    }

    // Guardar producto nuevo
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'precio' => 'required|numeric|min:0',
        'categoria' => 'nullable|string|max:255',
        'stock' => 'nullable|integer|min:0',
        'tallas' => 'nullable|array',
        'tallas.*' => 'integer|distinct|exists:tallas,id',
        'stock_tallas' => 'nullable|array',
    ]);

    $tallasSeleccionadas = $this->validarTallas($request);

    $producto = DB::transaction(function () use ($request, $tallasSeleccionadas) {
        $esZapato = $request->input('categoria') === 'Zapatos';
        $stock = $esZapato
            ? collect($tallasSeleccionadas)->sum('stock')
            : $request->input('stock', 0);

        $producto = Producto::create(array_merge(
            $request->only(['nombre', 'categoria', 'descripcion', 'precio', 'genero']),
            ['stock' => $stock]
        ));

        if ($esZapato) {
            foreach ($tallasSeleccionadas as $talla) {
                ProductoTalla::create([
                    'producto_id' => $producto->id,
                    'talla_id' => $talla['talla_id'],
                    'stock' => $talla['stock'],
                ]);

                if ($talla['stock'] > 0) {
                    $producto->registrarMovimientoConTalla(
                        $talla['talla_id'],
                        $talla['stock'],
                        'stock_inicial',
                        Auth::id()
                    );
                }
            }
        } elseif ($stock > 0) {
            $producto->registrarMovimiento(
                $stock,
                'stock_inicial',
                Auth::id()
            );
        }

        // Crear registro inicial en inventario automáticamente.
        \App\Models\Inventario::create([
            'producto_id' => $producto->id,
            'cantidad' => $producto->stock,
            'tipo_movimiento' => 'Creación',
            'descripcion' => 'Registro automático al crear producto'
        ]);

        return $producto;
    });

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
        $tallas = Talla::orderBy('numero')->get();
        $producto->load('productoTallas');

        return view('productos.edit', compact('producto', 'tallas'));
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
            'tallas' => 'nullable|array',
            'tallas.*' => 'integer|distinct|exists:tallas,id',
            'stock_tallas' => 'nullable|array',
        ]);

        $tallasSeleccionadas = $this->validarTallas($request);

        DB::transaction(function () use ($producto, $validated, $request, $tallasSeleccionadas) {
            $esZapato = $validated['categoria'] === 'Zapatos';

            $producto->update(collect($validated)->except(['tallas', 'stock_tallas'])->all());

            if (!$esZapato) {
                $producto->productoTallas()->delete();
                return;
            }

            $idsSeleccionados = collect($tallasSeleccionadas)->pluck('talla_id');

            $producto->productoTallas()
                ->whereNotIn('talla_id', $idsSeleccionados)
                ->delete();

            foreach ($tallasSeleccionadas as $talla) {
                $producto->productoTallas()->updateOrCreate(
                    ['talla_id' => $talla['talla_id']],
                    ['stock' => $talla['stock']]
                );
            }

            $producto->update([
                'stock' => collect($tallasSeleccionadas)->sum('stock'),
            ]);
        });

        return redirect()->route('productos.index')->with('success', 'Producto actualizado exitosamente.');
    }

    // Eliminar producto
    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    private function validarTallas(Request $request): array
    {
        if ($request->input('categoria') !== 'Zapatos') {
            return [];
        }

        $tallas = $request->input('tallas', []);
        $stocks = $request->input('stock_tallas', []);
        $resultado = [];

        foreach ($tallas as $tallaId) {
            $stock = $stocks[$tallaId] ?? null;

            if ($stock === null || !is_scalar($stock) || !preg_match('/^\d+$/', (string) $stock)) {
                throw ValidationException::withMessages([
                    "stock_tallas.{$tallaId}" => 'El stock de cada talla debe ser un entero mayor o igual a cero.',
                ]);
            }

            $resultado[] = [
                'talla_id' => (int) $tallaId,
                'stock' => (int) $stock,
            ];
        }

        return $resultado;
    }
}
