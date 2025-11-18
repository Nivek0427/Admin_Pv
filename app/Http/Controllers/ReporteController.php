<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->input('tipo'); // dia, semana, mes
        $estado = $request->input('estado');
        $ventas = Venta::query();

        $hayFiltros = false;


        // Filtro por rango personalizado
        if ($request->filled('desde') && $request->filled('hasta')) {
            $ventas->whereBetween('fecha', [$request->desde, $request->hasta]);
            $hayFiltros = true;
        }
        
        // Filtros rápidos
        elseif ($tipo === 'dia') {
            $ventas->whereDate('fecha', Carbon::today());
            $hayFiltros = true;
        } elseif ($tipo === 'semana') {
            $ventas->whereBetween('fecha', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]);
            $hayFiltros = true;
        } elseif ($tipo === 'mes') {
            $ventas->whereMonth('fecha', Carbon::now()->month);
            $hayFiltros = true;
        }

        // Filtro por estado
        if ($estado === 'activa' || $estado === 'revocada') {
            $ventas->where('estado', $estado);
            $hayFiltros = true;
        }
        

        if (!$hayFiltros) {
            // Si no hay filtros, mostrar solo ventas del día por defecto
            $ventas->whereDate('fecha', Carbon::today());
            $tipo = 'dia';
        }

        $ventas = $ventas->get();

        $ventasActivas = $ventas->where('estado', 'activa');
        $totalVentas = $ventasActivas->sum('total');
        $ventasRevocadas = $ventas->where('estado', 'revocada')->count();

        return view('reportes.index', [
            'ventas' => $ventas,
            'totalVentas' => $totalVentas,
            'ventasActivas' => $ventasActivas->count(),
            'ventasRevocadas' => $ventasRevocadas,
            'tipo' => $tipo
        ]);
    }

    public function generarPDF(Request $request)
    {
        $tipo = $request->input('tipo');
        $estado = $request->input('estado');

        $query = Venta::query();

        // Filtro por rango (fecha es campo 'fecha' en tu modelo)
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha', [$request->desde, $request->hasta]);
        }
        // Filtro rápido
        elseif ($tipo === 'dia') {
            $query->whereDate('fecha', Carbon::today());
        } elseif ($tipo === 'semana') {
            $query->whereBetween('fecha', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]);
        } elseif ($tipo === 'mes') {
            $query->whereMonth('fecha', Carbon::now()->month);
        }

        // Filtro por estado (si viene)
        if ($estado === 'activa') {
            $query->where('estado', 'activa');
        } elseif ($estado === 'revocada') {
            $query->where('estado', 'revocada');
        }

        $ventas = $query->with('detalles')->orderBy('fecha', 'desc')->get();

        // Totales: total dinero y total unidades vendidas (sumando cantidades de detalles)
        $totalVentas = $ventas->where('estado', 'activa')->sum('total');
        $totalProductosVendidos = $ventas->flatMap(function($v){
            return $v->detalles;
        })->sum('cantidad');

        // Filtros aplicados (para mostrar en encabezado del PDF)
        $filtros = [];
        if ($request->filled('desde') && $request->filled('hasta')) {
            $filtros[] = 'Desde: '.$request->desde.' - Hasta: '.$request->hasta;
        } elseif ($tipo) {
            $filtros[] = 'Filtro rápido: '.ucfirst($tipo);
        } else {
            $filtros[] = 'Filtro rápido: Ninguno';
        }
        $filtros[] = 'Estado: '.($estado ?: 'Todos');

        // Logo - usar public_path para que DomPDF lo incluya correctamente
        $logoPath = public_path('images/logo_FrStore.png');

        $pdf = Pdf::loadView('reportes.pdf', [
            'ventas' => $ventas,
            'titulo' => 'Reporte de Ventas',
            'totalVentas' => $totalVentas,
            'totalProductosVendidos' => $totalProductosVendidos,
            'filtros' => $filtros,
            'logo' => $logoPath,
        ]);

        // Opciones (ajusta según necesidad)
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ventas_'.now()->format('Ymd_His').'.pdf');
    }


}
