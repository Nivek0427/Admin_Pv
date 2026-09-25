<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$query, $tipo] = $this->consultaVentasFiltradas($request);
        $ventas = $query->get();

        $ventasActivas = $ventas->where('estado', 'activa');
        $totalVentas = $ventasActivas->sum('total');
        $ventasRevocadas = $ventas->where('estado', 'revocada')->count();
        $ganancias = $this->calcularGanancias($ventasActivas);
        $totalesPorMetodo = [
            'efectivo' => $ventasActivas->where('metodo_pago', 'efectivo')->sum('total'),
            'transferencia' => $ventasActivas->where('metodo_pago', 'transferencia')->sum('total'),
            'addi' => $ventasActivas->where('metodo_pago', 'addi')->sum('total'),
            'sistecredito' => $ventasActivas->where('metodo_pago', 'sistecredito')->sum('total'),
            'fiado' => $ventasActivas->whereIn('metodo_pago', ['fiado', 'Fiado'])->sum('total'),
            'tarjeta' => $ventasActivas->where('metodo_pago', 'tarjeta')->sum('total'),
        ];

        return view('reportes.index', [
            'ventas' => $ventas,
            'totalVentas' => $totalVentas,
            'totalesPorMetodo' => $totalesPorMetodo,
            'ventasActivas' => $ventasActivas->count(),
            'ventasRevocadas' => $ventasRevocadas,
            'gananciaTotal' => $ganancias['total'],
            'gananciasPorVenta' => $ganancias['porVenta'],
            'hayDetallesSinCostoHistorico' => $ganancias['parcial'],
            'tipo' => $tipo
        ]);
    }

    public function generarPDF(Request $request)
    {
        [$query, $tipo] = $this->consultaVentasFiltradas($request);
        $estado = $request->input('estado');

        $ventas = $query->orderBy('fecha', 'desc')->get();

        // ==============================
        //  TOTALES CORRECTOS
        // ==============================

        // Total dinero (solo activas)
        $ventasActivas = $ventas->where('estado', 'activa');
        $totalVentas = $ventasActivas->sum('total');
        $ganancias = $this->calcularGanancias($ventasActivas);
        $totalesPorMetodo = [
            'efectivo' => $ventasActivas->where('metodo_pago', 'efectivo')->sum('total'),
            'transferencia' => $ventasActivas->where('metodo_pago', 'transferencia')->sum('total'),
            'addi' => $ventasActivas->where('metodo_pago', 'addi')->sum('total'),
            'sistecredito' => $ventasActivas->where('metodo_pago', 'sistecredito')->sum('total'),
            'fiado' => $ventasActivas->whereIn('metodo_pago', ['fiado', 'Fiado'])->sum('total'),
            'tarjeta' => $ventasActivas->where('metodo_pago', 'tarjeta')->sum('total'),
        ];

        // Total de unidades (solo activas)
        $totalProductosVendidos = 0;
        $productosVendidos = [];
        $productosGeneros = [];

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
                    $productosGeneros[$nombre] = $detalle->producto?->genero ?? '-';
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
            'totalesPorMetodo' => $totalesPorMetodo,
            'totalProductosVendidos' => $totalProductosVendidos,
            'productosVendidos' => $productosVendidos,  // << SE AGREGA
            'productosGeneros' => $productosGeneros,
            'gananciaTotal' => $ganancias['total'],
            'gananciasPorVenta' => $ganancias['porVenta'],
            'gananciasPorProducto' => $ganancias['porProducto'],
            'hayDetallesSinCostoHistorico' => $ganancias['parcial'],
            'filtros' => $filtros,
            'logo' => $logoPath,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ventas_'.now()->format('Ymd_His').'.pdf');
    }

    private function consultaVentasFiltradas(Request $request): array
    {
        $tipo = $request->input('tipo');
        $estado = $request->input('estado');
        $query = Venta::query()->with(['detalles.producto', 'detalles.talla']);
        $hayFiltros = false;

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereDate('fecha', '>=', $request->desde)
                ->whereDate('fecha', '<=', $request->hasta);
            $hayFiltros = true;
        } elseif ($tipo === 'dia') {
            $query->whereDate('fecha', Carbon::today());
            $hayFiltros = true;
        } elseif ($tipo === 'ayer') {
            $query->whereDate('fecha', Carbon::yesterday());
            $hayFiltros = true;
        } elseif ($tipo === 'semana') {
            $query->whereBetween('fecha', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            $hayFiltros = true;
        } elseif ($tipo === 'mes') {
            $query->whereMonth('fecha', Carbon::now()->month);
            $hayFiltros = true;
        }

        if ($estado === 'activa' || $estado === 'revocada') {
            $query->where('estado', $estado);
            $hayFiltros = true;
        }

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
            $hayFiltros = true;
        }

        if (!$hayFiltros) {
            $query->whereDate('fecha', Carbon::today());
            $tipo = 'dia';
        }

        return [$query, $tipo];
    }

    private function calcularGanancias(Collection $ventasActivas): array
    {
        $total = 0;
        $porVenta = [];
        $porProducto = [];
        $parcial = false;

        foreach ($ventasActivas as $venta) {
            $gananciaVenta = 0;
            $tieneDetalles = false;
            $tieneCostoHistoricoCompleto = true;

            foreach ($venta->detalles as $detalle) {
                $tieneDetalles = true;
                $nombre = $detalle->producto?->nombre ?? '[producto eliminado]';

                if (!array_key_exists($nombre, $porProducto)) {
                    $porProducto[$nombre] = null;
                }

                if ($detalle->costo_unitario === null) {
                    $parcial = true;
                    $tieneCostoHistoricoCompleto = false;
                    continue;
                }

                $ganancia = ((float) $detalle->precio_unitario - (float) $detalle->costo_unitario)
                    * (int) $detalle->cantidad;

                $total += $ganancia;
                $gananciaVenta += $ganancia;
                $porProducto[$nombre] = ($porProducto[$nombre] ?? 0) + $ganancia;
            }

            $porVenta[$venta->id] = $tieneDetalles && $tieneCostoHistoricoCompleto
                ? $gananciaVenta
                : null;
        }

        return [
            'total' => $total,
            'porVenta' => $porVenta,
            'porProducto' => $porProducto,
            'parcial' => $parcial,
        ];
    }



}
