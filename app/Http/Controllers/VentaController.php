<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\DetalleVenta;
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
        $venta = Venta::with(['detalles.producto'])->findOrFail($id);
        return view('ventas.show', compact('venta'));
    }


    /**
     * Muestra el formulario para crear una nueva venta.
     */
    public function create()
    {
        $productos = Producto::all();
        return view('ventas.create', compact('productos'));
    }

    /**
     * Guarda una nueva venta y sus detalles.
     */
    public function store(Request $request)
    {
        // Decodificar productos enviados desde el formulario
        $productos = json_decode($request->input('productos'), true);

        if (empty($productos)) {
            return back()->with('error', 'No se agregaron productos a la venta.');
        }

        DB::beginTransaction();

        try {
            // Crear la venta principal
            $venta = Venta::create([
                'fecha' => now(),
                'cliente' => $request->cliente ?? 'Cliente general',
                'total' => 0,
                'metodo_pago' => $request->metodo_pago,
                'estado' => 'activa',
            ]);

            $total = 0;

            // Procesar cada producto de la venta
            foreach ($productos as $p) {
                $producto = Producto::find($p['id']);

                if (!$producto) {
                    DB::rollBack();
                    return back()->with('error', "El producto con ID {$p['id']} no existe.");
                }

                // Validar stock suficiente
                if ($producto->stock < $p['cantidad']) {
                    DB::rollBack();
                    return back()->with('error', "El producto '{$producto->nombre}' no tiene suficiente stock.");
                }

                // Calcular subtotal y actualizar stock
                $subtotal = $p['precio'] * $p['cantidad'];
                $total += $subtotal;

                $producto->decrement('stock', $p['cantidad']);
                $producto->registrarMovimiento(-$p['cantidad'], 'venta');


                // Crear el detalle de la venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $p['cantidad'],
                    // Asegúrate que el campo exista en tu tabla:
                    'precio_unitario' => $p['precio'],
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
    $venta = Venta::with('detalles.producto')->findOrFail($id);

    if ($venta->estado === 'revocada') {
        return back()->with('error', 'Esta venta ya fue revocada.');
    }

    try {
        DB::beginTransaction();

        // Restaurar stock
        foreach ($venta->detalles as $detalle) {
            $detalle->producto->increment('stock', $detalle->cantidad);
        }
        foreach ($venta->detalles as $detalle) {
            $detalle->producto->registrarMovimiento($detalle->cantidad, 'revocacion');
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
