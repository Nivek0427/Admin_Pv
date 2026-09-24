<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
use App\Models\Banco;
use App\Models\ProductoTalla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentaController extends Controller
{
    /**
     * Muestra todas las ventas registradas.
     */
    public function index(Request $request)
    {
        $query = Venta::orderBy('fecha', 'desc');

        // 1. Filtro rápido: tipo
        $tipo = $request->get('tipo');

        switch ($tipo) {
            case 'dia':
                $query->whereDate('fecha', Carbon::today());
                break;
            case 'ayer':
                $query->whereDate('fecha', Carbon::yesterday());
                break;

            case 'semana':
                $query->whereBetween('fecha', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;

            case 'mes':
                $query->whereMonth('fecha', Carbon::now()->month)
                    ->whereYear('fecha', Carbon::now()->year);
                break;

            case 'rango':
                if ($request->filled('desde') && $request->filled('hasta')) {
                    $query->whereBetween('fecha', [$request->desde, $request->hasta]);
                }
                break;

            default:
                // HOY por defecto
                if (!$request->filled('buscar')) {
                    $query->whereDate('fecha', Carbon::today());
                }
        }

        // filtro método de pago
        if ($request->filled('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            if ($request->filled('buscar')) {
            $busqueda = $request->buscar;

            $query->where(function ($q) use ($busqueda) {
                $q->where('cliente', 'LIKE', '%' . $busqueda . '%')
                ->orWhere('id', $busqueda)
                ->orWhere('total', 'LIKE', '%' . $busqueda . '%')
                ->orWhere('estado', 'LIKE', '%' . $busqueda . '%');
            });
        }

        $ventas = $query->get();

        return view('ventas.index', compact('ventas'));
    }


    public function show($id)
    {
        $venta = Venta::with(['detalles.producto', 'detalles.talla'])->findOrFail($id);
        return view('ventas.show', compact('venta'));
    }


    /**
     * Muestra el formulario para crear una nueva venta.
     */
    public function create()
    {
        $productos = Producto::with('productoTallas.talla')->get();
        $bancos = Banco::all();
        return view('ventas.create', compact('productos', 'bancos'));
    }

    /**
     * Guarda una nueva venta y sus detalles.
     */
    public function store(Request $request)
    {
        $esAdmin = auth()->user()->hasRole('admin');

        // Validar datos básicos de la venta
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,addi,transferencia,sistecredito,Fiado,fiado',
            'banco_id' => 'required_if:metodo_pago,transferencia'
        ]);

        // Solo los admin pueden establecer la fecha de la venta
        if ($esAdmin) {
        $request->validate([
            'fecha' => 'nullable|date|before_or_equal:today',
        ]);
}


        // Decodificar productos enviados desde el formulario
        $productos = json_decode($request->input('productos'), true);

        if (empty($productos)) {
            return back()->with('error', 'No se agregaron productos a la venta.');
        }

        DB::beginTransaction();

        try {
            // Crear la venta principal

            $fechaVenta = now();

            if($esAdmin && $request->filled('fecha')) {
                $fechaVenta = Carbon::parse($request->fecha);
            }

            $venta = Venta::create([
                'fecha' => $fechaVenta,
                'cliente' => $request->cliente ?? 'Cliente general',
                'total' => 0,
                'metodo_pago' => $request->metodo_pago,
                'banco_id' => $request->metodo_pago === 'transferencia'
                    ? $request->banco_id
                    : null,
                'estado' => 'activa',
            ]);

            $total = 0;

            // Procesar cada producto de la venta
            foreach ($productos as $p) {
                if (!isset($p['id'], $p['cantidad'], $p['precio'])) {
                    throw new \InvalidArgumentException('Los datos de un producto de la venta son inválidos.');
                }

                $costoUnitario = null;
                if ($esAdmin) {
                    $costoRecibido = $p['costo_unitario'] ?? null;

                    if (!is_scalar($costoRecibido) || !is_numeric($costoRecibido)) {
                        throw new \InvalidArgumentException("Debe ingresar un costo unitario válido para el producto.");
                    }

                    $costoUnitario = (float) $costoRecibido;
                    if (!is_finite($costoUnitario) || $costoUnitario < 0) {
                        throw new \InvalidArgumentException("El costo unitario debe ser un número mayor o igual a cero.");
                    }
                }

                $cantidad = filter_var($p['cantidad'], FILTER_VALIDATE_INT);
                if ($cantidad === false || $cantidad < 1) {
                    throw new \InvalidArgumentException('La cantidad de cada producto debe ser un entero positivo.');
                }

                $producto = Producto::whereKey($p['id'])->lockForUpdate()->first();

                if (!$producto) {
                    throw new \InvalidArgumentException("El producto con ID {$p['id']} no existe.");
                }

                $tallaId = null;
                $productoTalla = null;

                if ($producto->esZapato()) {
                    if (!isset($p['talla_id']) || !filter_var($p['talla_id'], FILTER_VALIDATE_INT)) {
                        throw new \InvalidArgumentException("Debe seleccionar una talla para '{$producto->nombre}'.");
                    }

                    $tallaId = (int) $p['talla_id'];
                    $productoTalla = ProductoTalla::where('producto_id', $producto->id)
                        ->where('talla_id', $tallaId)
                        ->lockForUpdate()
                        ->first();

                    if (!$productoTalla) {
                        throw new \InvalidArgumentException("La talla seleccionada no está configurada para '{$producto->nombre}'.");
                    }

                    if ($productoTalla->stock < $cantidad) {
                        throw new \InvalidArgumentException("El producto '{$producto->nombre}' no tiene suficiente stock en la talla seleccionada.");
                    }
                } elseif ($producto->stock < $cantidad) {
                    throw new \InvalidArgumentException("El producto '{$producto->nombre}' no tiene suficiente stock.");
                }

                // Calcular subtotal y actualizar stock
                $subtotal = $p['precio'] * $cantidad;
                $total += $subtotal;

                if ($producto->esZapato()) {
                    $productoTalla->decrement('stock', $cantidad);
                    $producto->sincronizarStockTotal();
                    $producto->registrarMovimientoConTalla($tallaId, -$cantidad, 'venta');
                } else {
                    $producto->decrement('stock', $cantidad);
                    $producto->registrarMovimiento(-$cantidad, 'venta');
                }


                // Crear el detalle de la venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'talla_id' => $tallaId,
                    'cantidad' => $cantidad,
                    // Asegúrate que el campo exista en tu tabla:
                    'precio_unitario' => $p['precio'],
                    'costo_unitario' => $costoUnitario,
                    'subtotal' => $subtotal,
                ]);
            }

            // Actualizar el total en la venta
            $venta->update(['total' => $total]);

            DB::commit();
            return redirect()
                ->route('ventas.index')
                ->with('success', '✅ Venta registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al registrar la venta: ' . $e->getMessage());
        }
    }



    /**
     * Revocar una venta (restaurar stock).
     */
    public function revocar(Request $request, $id)
{
    $venta = Venta::findOrFail($id);

    if ($venta->estado === 'revocada') {
        return back()->with('error', 'Esta venta ya fue revocada.');
    }

    try {
        DB::beginTransaction();

        // Bloquear la venta evita dos revocaciones concurrentes.
        $venta = Venta::with(['detalles.producto', 'detalles.talla'])
            ->lockForUpdate()
            ->findOrFail($id);

        if ($venta->estado === 'revocada') {
            DB::rollBack();
            return back()->with('error', 'Esta venta ya fue revocada.');
        }

        // Restaurar stock
        foreach ($venta->detalles as $detalle) {
            if ($detalle->talla_id !== null) {
                $productoTalla = ProductoTalla::where('producto_id', $detalle->producto_id)
                    ->where('talla_id', $detalle->talla_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $productoTalla->increment('stock', $detalle->cantidad);
                $detalle->producto->sincronizarStockTotal();
                $detalle->producto->registrarMovimientoConTalla(
                    $detalle->talla_id,
                    $detalle->cantidad,
                    'revocacion'
                );
            } else {
                // Conserva el comportamiento de ventas históricas y no zapatos.
                $detalle->producto->increment('stock', $detalle->cantidad);
                $detalle->producto->registrarMovimiento($detalle->cantidad, 'revocacion');
            }
        }

        // Guardar fecha y motivo
        $venta->update([
            'estado' => 'revocada',
            'revocada_fecha' => now(),
            'revocada_motivo' => $request->reason ?? 'Sin motivo',
        ]);

        DB::commit();
        return back()->with('success', 'Venta revocada correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}




}
