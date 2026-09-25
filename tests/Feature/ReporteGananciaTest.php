<?php

namespace Tests\Feature;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReporteGananciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_report_aggregates_snapshot_gains_for_modified_prices_and_quantities(): void
    {
        $admin = $this->crearAdmin();
        $productoA = $this->crearProducto('Camisa costo 80', 80000);
        $productoB = $this->crearProducto('Pantalón costo 30', 30000);
        $ventaA = $this->crearVentaConDetalle($productoA, 1, 120000, 80000);
        $ventaB = $this->crearVentaConDetalle($productoB, 2, 60000, 30000);

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => today()->toDateString(), 'hasta' => today()->toDateString()]))
            ->assertOk()
            ->assertViewHas('gananciaTotal', 100000.0)
            ->assertViewHas('gananciasPorVenta', [
                $ventaA->id => 40000.0,
                $ventaB->id => 60000.0,
            ])
            ->assertViewHas('hayDetallesSinCostoHistorico', false)
            ->assertSee('Ganancia calculada:')
            ->assertSee('$100.000')
            ->assertSee('Ganancia')
            ->assertSee('$40.000')
            ->assertSee('$60.000')
            ->assertDontSee('Costo unitario')
            ->assertDontSee('Costo total');
    }

    public function test_null_snapshots_are_omitted_and_mixed_results_are_marked_partial(): void
    {
        $admin = $this->crearAdmin();
        $productoConCosto = $this->crearProducto('Producto con snapshot', 80000);
        $productoSinCosto = $this->crearProducto('Producto histórico sin snapshot', 80000);
        $productoRevocado = $this->crearProducto('Producto revocado', 1000);

        $ventaConCosto = $this->crearVentaConDetalle($productoConCosto, 1, 120000, 80000);
        $ventaSinCosto = $this->crearVentaConDetalle($productoSinCosto, 1, 90000, null);
        $this->crearVentaConDetalle($productoRevocado, 1, 500000, 1000, null, 'efectivo', 'revocada');

        $this->actingAs($admin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertViewHas('gananciaTotal', 40000.0)
            ->assertViewHas('gananciasPorVenta', [
                $ventaConCosto->id => 40000.0,
                $ventaSinCosto->id => null,
            ])
            ->assertViewHas('hayDetallesSinCostoHistorico', true)
            ->assertSee('Ganancia calculada:')
            ->assertSee('$40.000')
            ->assertSee('únicamente a detalles con costo histórico registrado')
            ->assertSee('—')
            ->assertDontSee('$80.000');
    }

    public function test_sale_with_mixed_snapshot_details_shows_dash_and_keeps_known_gains_in_partial_total(): void
    {
        $admin = $this->crearAdmin();
        $productoUno = $this->crearProducto('Detalle conocido uno', 80000);
        $productoDos = $this->crearProducto('Detalle conocido dos', 60000);
        $productoSinSnapshot = $this->crearProducto('Detalle sin snapshot', 5000);
        $venta = Venta::create([
            'fecha' => today(),
            'cliente' => 'Venta con snapshots mixtos',
            'total' => 430000,
            'metodo_pago' => 'efectivo',
            'estado' => 'activa',
        ]);

        foreach ([
            [$productoUno, 1, 120000, 80000],
            [$productoDos, 2, 110000, 60000],
            [$productoSinSnapshot, 1, 90000, null],
        ] as [$producto, $cantidad, $precio, $costoSnapshot]) {
            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'costo_unitario' => $costoSnapshot,
                'subtotal' => $precio * $cantidad,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertViewHas('gananciaTotal', 140000.0)
            ->assertViewHas('gananciasPorVenta', [$venta->id => null])
            ->assertViewHas('hayDetallesSinCostoHistorico', true)
            ->assertSee('—')
            ->assertSee('$140.000')
            ->assertSee('únicamente a detalles con costo histórico registrado')
            ->assertDontSee('$225.000');
    }

    public function test_date_payment_and_yesterday_filters_apply_to_gain_and_match_pdf(): void
    {
        $admin = $this->crearAdmin();
        $productoEfectivoHoy = $this->crearProducto('Efectivo hoy', 80000);
        $productoTransferenciaHoy = $this->crearProducto('Transferencia hoy', 30000);
        $productoEfectivoAyer = $this->crearProducto('Efectivo ayer', 20000);
        $productoRevocado = $this->crearProducto('Revocado hoy', 10000);

        $this->crearVentaConDetalle($productoEfectivoHoy, 1, 120000, 80000, today(), 'efectivo');
        $this->crearVentaConDetalle($productoTransferenciaHoy, 1, 80000, 30000, today(), 'transferencia');
        $this->crearVentaConDetalle($productoEfectivoAyer, 1, 50000, 20000, today()->subDay(), 'efectivo');
        $this->crearVentaConDetalle($productoRevocado, 1, 100000, 10000, today(), 'efectivo', 'revocada');

        $this->actingAs($admin)
            ->get(route('reportes.index', [
                'desde' => today()->toDateString(),
                'hasta' => today()->toDateString(),
            ]))
            ->assertViewHas('gananciaTotal', 90000.0);

        $this->get(route('reportes.index', ['metodo_pago' => 'efectivo']))
            ->assertViewHas('gananciaTotal', 70000.0);

        $this->get(route('reportes.index', ['tipo' => 'ayer']))
            ->assertViewHas('gananciaTotal', 30000.0);

        $filtros = [
            'desde' => today()->toDateString(),
            'hasta' => today()->toDateString(),
            'metodo_pago' => 'efectivo',
        ];
        $gananciaWeb = null;
        $gananciasPorVentaWeb = null;

        $this->get(route('reportes.index', $filtros))
            ->assertViewHas('gananciaTotal', function ($ganancia) use (&$gananciaWeb) {
                $gananciaWeb = $ganancia;
                return true;
            })
            ->assertViewHas('gananciasPorVenta', function ($ganancias) use (&$gananciasPorVentaWeb) {
                $gananciasPorVentaWeb = $ganancias;
                return true;
            });

        $documentoPdf = Mockery::mock(DomPdfDocument::class);
        $documentoPdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $documentoPdf->shouldReceive('download')->once()->andReturn(response('PDF de prueba'));

        $gananciaPdf = null;
        $gananciasPorVentaPdf = null;
        Pdf::shouldReceive('loadView')
            ->once()
            ->with('reportes.pdf', Mockery::on(function (array $datos) use (&$gananciaPdf, &$gananciasPorVentaPdf) {
                $gananciaPdf = $datos['gananciaTotal'];
                $gananciasPorVentaPdf = $datos['gananciasPorVenta'];
                return true;
            }))
            ->andReturn($documentoPdf);

        $this->get(route('reportes.pdf', $filtros))->assertOk();

        $this->assertSame($gananciaWeb, $gananciaPdf);
        $this->assertSame($gananciasPorVentaWeb, $gananciasPorVentaPdf);
        $this->assertSame(40000.0, $gananciaPdf);
    }

    public function test_user_without_report_permission_cannot_open_web_or_pdf(): void
    {
        $usuario = $this->crearUsuario('sin-permiso-reportes');

        $this->actingAs($usuario)->get(route('reportes.index'))->assertForbidden();
        $this->get(route('reportes.pdf'))->assertForbidden();
    }

    private function crearAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permiso = Permission::findOrCreate('reportes', 'web');
        $rol = Role::findOrCreate('admin', 'web');
        $rol->givePermissionTo($permiso);

        $admin = $this->crearUsuario('admin-reportes');
        $admin->assignRole($rol);

        return $admin;
    }

    private function crearUsuario(string $username): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'password' => 'password',
        ]);
    }

    private function crearProducto(string $nombre, float $costo): Producto
    {
        return Producto::create([
            'nombre' => $nombre,
            'categoria' => 'Camisas',
            'precio' => 120000,
            'costo' => $costo,
            'stock' => 0,
            'genero' => 'Hombre',
        ]);
    }

    private function crearVentaConDetalle(
        Producto $producto,
        int $cantidad,
        float $precioUnitario,
        ?float $costoUnitario,
        $fecha = null,
        string $metodoPago = 'efectivo',
        string $estado = 'activa'
    ): Venta {
        $venta = Venta::create([
            'fecha' => $fecha ?? today(),
            'cliente' => 'Cliente de reporte',
            'total' => $precioUnitario * $cantidad,
            'metodo_pago' => $metodoPago,
            'estado' => $estado,
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'costo_unitario' => $costoUnitario,
            'subtotal' => $precioUnitario * $cantidad,
        ]);

        return $venta;
    }
}