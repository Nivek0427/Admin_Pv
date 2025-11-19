<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventarioMovimiento;
use Carbon\Carbon;

class InventarioMovimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = InventarioMovimiento::with(['producto', 'usuario']);

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('created_at', [
                $request->fecha_desde . ' 00:00:00',
                $request->fecha_hasta . ' 23:59:59'
            ]);
        }

        // Filtrar por tipo de movimiento (entrada o salida)
        if ($request->filled('tipo_mov')) {
            $query->where('tipo', $request->tipo_mov);
        }

        // Filtros rápidos por día, semana o mes
        if ($request->filled('tipo')) {
            switch ($request->tipo) {
                case 'dia':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'ayer':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'semana':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'mes':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        }

        // Si no se aplicó ningún filtro, mostrar movimientos de HOY
        if (
            !$request->filled('tipo') &&
            !$request->filled('fecha_desde') &&
            !$request->filled('fecha_hasta')
        ) {
            $query->whereDate('created_at', Carbon::today());
        }


        $movimientos = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('inventario.movimientos', compact('movimientos'));
    }
}
