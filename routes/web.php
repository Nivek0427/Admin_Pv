<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\ReporteController;
use App\Models\Venta;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;

Auth::routes();

// Ruta de inicio → login
Route::get('/', function () {
    return redirect('/dashboard');
});

// Agrupar TODAS las rutas que requieren login
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {

        $ventasHoy = Venta::whereDate('fecha', today())
                            ->where('estado', 'activa')
                            ->count();

        $totalHoy = Venta::whereDate('fecha', today())
                            ->where('estado', 'activa')
                            ->sum('total');

        $productosBajoStock = Producto::where('stock', '<', 10)->count();

        $ultimasVentas = Venta::orderBy('fecha', 'desc')->take(5)->get();

        return view('dashboard', compact('ventasHoy', 'totalHoy', 'productosBajoStock', 'ultimasVentas'));
    })->name('dashboard');

    // Productos
    Route::resource('productos', ProductoController::class);
    //ruta para productos.create con middleware
    Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create')->middleware('can:productos.create');

    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index')->middleware('can:inventario');
    Route::post('/inventario/{id}/actualizar', [InventarioController::class, 'updateCantidad'])->name('inventario.updateCantidad');

    // Ventas
    Route::resource('ventas', VentaController::class);
    Route::put('ventas/{id}/revocar', [VentaController::class, 'revocar'])->name('ventas.revocar');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index')->middleware('can:reportes');
    Route::post('/reportes/filtrar', [ReporteController::class, 'filtrar'])->name('reportes.filtrar');
    Route::get('/reportes/pdf', [ReporteController::class, 'generarPDF'])->name('reportes.pdf');

});

// Ruta por defecto que Laravel UI usa como "home"
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
