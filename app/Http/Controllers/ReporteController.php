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
        //filtro por metodo de pago
        if ($request->filled('metodo_pago')) {
            $ventas->where('metodo_pago', $request->metodo_pago);
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

        $query = Venta::query()->with('detalles.producto');

        // Filtro por rango (fecha)
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha', [$request->desde, $request->hasta]);
        }
        // Filtros rápidos
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

        // Filtro por estado
        if ($estado === 'activa' || $estado === 'revocada') {
            $query->where('estado', $estado);
        }

        // Filtro por método de pago
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        $ventas = $query->orderBy('fecha', 'desc')->get();

        // ==============================
        //  TOTALES CORRECTOS
        // ==============================

        // Total dinero (solo activas)
        $totalVentas = $ventas->where('estado', 'activa')->sum('total');

        // Total de unidades (solo activas)
        $totalProductosVendidos = 0;
        $productosVendidos = [];

        foreach ($ventas as $venta) {
            if ($venta->estado === 'activa') {
                foreach ($venta->detalles as $detalle) {

                    // sumar unidades totales
                    $totalProductosVendidos += $detalle->cantidad;

                    // agrupar por producto
                    $nombre = $detalle->producto?->nombre ?? '[producto eliminado]';

                    if (!isset($productosVendidos[$nombre])) {
                        $productosVendidos[$nombre] = 0;
                    }

                    $productosVendidos[$nombre] += $detalle->cantidad;
                }
            }
        }

        // ==============================
        //  FILTROS PARA EL PDF
        // ==============================
        $filtros = [];

        if ($request->filled('desde') && $request->filled('hasta')) {
            $filtros[] = 'Desde: '.$request->desde.' - Hasta: '.$request->hasta;
        } elseif ($tipo) {
            $filtros[] = 'Filtro rápido: '.ucfirst($tipo);
        } else {
            $filtros[] = 'Filtro rápido: Ninguno';
        }

        $filtros[] = 'Estado: '.($estado ?: 'Todos');

        if ($request->filled('metodo_pago')) {
            $filtros[] = 'Método de pago: '.ucfirst($request->metodo_pago);
        }

        // logo
        $logoPath = public_path('images/logo_FrStore.png');

        $pdf = Pdf::loadView('reportes.pdf', [
            'ventas' => $ventas,
            'titulo' => 'Reporte de Ventas',
            'totalVentas' => $totalVentas,
            'totalProductosVendidos' => $totalProductosVendidos,
            'productosVendidos' => $productosVendidos,  // << SE AGREGA
            'filtros' => $filtros,
            'logo' => $logoPath,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ventas_'.now()->format('Ymd_His').'.pdf');
    }



}
