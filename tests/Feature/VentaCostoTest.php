<?php

namespace Tests\Feature;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\ProductoTalla;
use App\Models\Talla;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VentaCostoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_snapshots_the_product_cost_and_keeps_a_modified_sale_price(): void
    {
        $usuario = $this->crearUsuario();
        $producto = $this->crearProducto('Camisa', 80000, 5);

        $this->registrarVenta($usuario, [[
            'id' => $producto->id,
            'cantidad' => 1,
            'precio' => 120000,
        ]])->assertRedirect(route('ventas.index'));

        $detalle = DetalleVenta::firstOrFail();

        $this->assertSame(80000.0, (float) $detalle->costo_unitario);
        $this->assertSame(120000.0, (float) $detalle->precio_unitario);
        $this->assertSame(40000.0, (float) $detalle->precio_unitario - (float) $detalle->costo_unitario);
    }

    public function test_request_cost_is_ignored_and_gain_uses_the_snapshot_and_quantity(): void
    {
        $usuario = $this->crearUsuario();
        $producto = $this->crearProducto('Pantalón', 80000, 5);

        foreach ([1 => 30000.0, 2 => 60000.0] as $cantidad => $gananciaEsperada) {
            $this->registrarVenta($usuario, [[
                'id' => $producto->id,
                'cantidad' => $cantidad,
                'precio' => 110000,
                'costo_unitario' => 1,
            ]])->assertRedirect(route('ventas.index'));

            $detalle = DetalleVenta::latest('id')->firstOrFail();
            $ganancia = ((float) $detalle->precio_unitario - (float) $detalle->costo_unitario)
                * $detalle->cantidad;

            $this->assertSame(80000.0, (float) $detalle->costo_unitario);
            $this->assertSame(110000.0, (float) $detalle->precio_unitario);
            $this->assertSame($gananciaEsperada, $ganancia);
        }
    }

    public function test_zero_product_cost_is_saved_and_normal_user_does_not_see_cost_or_gain(): void
    {
        $usuario = $this->crearUsuario();
        $producto = $this->crearProducto('Gorra', 0, 3);

        $this->actingAs($usuario)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertDontSee('Costo unitario')
            ->assertDontSee('Ganancia')
            ->assertDontSee('data-costo="0"');

        $this->registrarVenta($usuario, [[
            'id' => $producto->id,
            'cantidad' => 2,
            'precio' => 120000,
        ]])->assertRedirect(route('ventas.index'));

        $detalle = DetalleVenta::firstOrFail();
        $this->assertSame(0.0, (float) $detalle->costo_unitario);
    }

    public function test_admin_does_not_see_cost_or_gain_during_creation(): void
    {
        $admin = $this->crearUsuario();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $producto = $this->crearProducto('Camisa admin', 80000, 2);

        $this->actingAs($admin)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertDontSee('Costo Unitario')
            ->assertDontSee('Ganancia')
            ->assertDontSee('data-costo', false)
            ->assertDontSee('costo_unitario', false);

        $this->registrarVenta($admin, [[
            'id' => $producto->id,
            'cantidad' => 1,
            'precio' => 110000,
            'costo_unitario' => 1,
        ]])->assertRedirect(route('ventas.index'));

        $detalle = DetalleVenta::firstOrFail();
        $this->assertSame(80000.0, (float) $detalle->costo_unitario);
    }

    public function test_shoe_sale_keeps_talla_stock_and_snapshots_product_cost(): void
    {
        $usuario = $this->crearUsuario();
        $talla = Talla::create(['numero' => 36]);
        $producto = $this->crearProducto('Zapato', 80000, 2, 'Zapatos', 'Mujer');
        ProductoTalla::create([
            'producto_id' => $producto->id,
            'talla_id' => $talla->id,
            'stock' => 2,
        ]);

        $this->registrarVenta($usuario, [[
            'id' => $producto->id,
            'talla_id' => $talla->id,
            'cantidad' => 1,
            'precio' => 120000,
            'costo_unitario' => 1,
        ]])->assertRedirect(route('ventas.index'));

        $detalle = DetalleVenta::firstOrFail();
        $this->assertSame($talla->id, $detalle->talla_id);
        $this->assertSame(80000.0, (float) $detalle->costo_unitario);
        $this->assertSame(1, (int) $producto->productoTallas()->firstOrFail()->stock);
        $this->assertDatabaseHas('inventario_movimientos', [
            'producto_id' => $producto->id,
            'talla_id' => $talla->id,
            'cantidad' => -1,
            'tipo' => 'venta',
        ]);
    }

    public function test_historical_null_cost_stays_null_when_new_sales_are_recorded(): void
    {
        $usuario = $this->crearUsuario();
        $producto = $this->crearProducto('Accesorio', 80000, 3);
        $ventaAnterior = Venta::create([
            'fecha' => today(),
            'cliente' => 'Histórico',
            'total' => 90000,
            'metodo_pago' => 'efectivo',
            'estado' => 'activa',
        ]);
        $detalleAnterior = DetalleVenta::create([
            'venta_id' => $ventaAnterior->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 90000,
            'costo_unitario' => null,
            'subtotal' => 90000,
        ]);

        $this->registrarVenta($usuario, [[
            'id' => $producto->id,
            'cantidad' => 1,
            'precio' => 120000,
        ]])->assertRedirect(route('ventas.index'));

        $this->assertNull($detalleAnterior->fresh()->costo_unitario);
        $this->get(route('ventas.show', $ventaAnterior))->assertOk();
    }

    public function test_admin_sees_snapshot_cost_and_gain_in_sale_detail(): void
    {
        $admin = $this->crearUsuario();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $producto = $this->crearProducto('Camisa detalle', 80000, 0);
        $venta = $this->crearVentaConDetalle($producto, 1, 120000, 80000);

        $this->actingAs($admin)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertSee('Costo unitario')
            ->assertSee('$80,000.00')
            ->assertSee('$40,000.00');
    }

    public function test_modified_sale_price_uses_snapshot_for_detail_gain(): void
    {
        $admin = $this->crearUsuario();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $producto = $this->crearProducto('Pantalón detalle', 80000, 0);
        $venta = $this->crearVentaConDetalle($producto, 2, 110000, 80000);

        $this->actingAs($admin)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertSee('$110,000.00')
            ->assertSee('$80,000.00')
            ->assertSee('$160,000.00')
            ->assertSee('$60,000.00');
    }

    public function test_non_admin_cannot_receive_cost_attributes_or_see_cost_and_gain(): void
    {
        $usuario = $this->crearUsuario();
        $producto = $this->crearProducto('Gorra detalle', 80000, 0);
        $venta = $this->crearVentaConDetalle($producto, 1, 120000, 80000);

        $response = $this->actingAs($usuario)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertDontSee('Costo unitario')
            ->assertDontSee('Costo total')
            ->assertDontSee('Ganancia');

        $response->assertViewHas('venta', function (Venta $venta) {
            $detalle = $venta->detalles->first();

            return $detalle !== null
                && !array_key_exists('costo_unitario', $detalle->getAttributes())
                && !array_key_exists('costo', $detalle->producto->getAttributes());
        });
    }

    public function test_null_historical_cost_shows_dashes_without_using_current_product_cost(): void
    {
        $admin = $this->crearUsuario();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $productoSinSnapshot = $this->crearProducto('Histórico sin snapshot', 80000, 0);
        $productoConSnapshot = $this->crearProducto('Histórico con snapshot', 40000, 0);
        $venta = $this->crearVentaConDetalle($productoSinSnapshot, 1, 90000, null);
        $this->crearDetalle($venta, $productoConSnapshot, 1, 50000, 40000);
        $venta->update(['total' => 140000]);

        $this->actingAs($admin)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertSee('—')
            ->assertSee('$40,000.00')
            ->assertSee('$10,000.00')
            ->assertDontSee('$80,000.00');
    }

    public function test_admin_sale_detail_keeps_shoe_size_and_snapshot_cost(): void
    {
        $admin = $this->crearUsuario();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $talla = Talla::create(['numero' => 36]);
        $producto = $this->crearProducto('Zapato detalle', 80000, 0, 'Zapatos', 'Mujer');
        $venta = $this->crearVentaConDetalle($producto, 2, 110000, 80000, $talla->id);

        $this->actingAs($admin)
            ->get(route('ventas.show', $venta))
            ->assertOk()
            ->assertSee('Talla 36')
            ->assertSee('$80,000.00')
            ->assertSee('$60,000.00');
    }

    private function crearUsuario(): User
    {
        return User::create([
            'name' => 'Vendedor de prueba',
            'username' => 'vendedor-prueba',
            'password' => 'password',
        ]);
    }

    private function crearProducto(
        string $nombre,
        float $costo,
        int $stock,
        string $categoria = 'Camisas',
        string $genero = 'Hombre'
    ): Producto {
        return Producto::create([
            'nombre' => $nombre,
            'categoria' => $categoria,
            'precio' => 120000,
            'costo' => $costo,
            'stock' => $stock,
            'genero' => $genero,
        ]);
    }

    private function crearVentaConDetalle(
        Producto $producto,
        int $cantidad,
        float $precioUnitario,
        ?float $costoUnitario,
        ?int $tallaId = null
    ): Venta {
        $venta = Venta::create([
            'fecha' => today(),
            'cliente' => 'Cliente de prueba',
            'total' => $precioUnitario * $cantidad,
            'metodo_pago' => 'efectivo',
            'estado' => 'activa',
        ]);

        $this->crearDetalle($venta, $producto, $cantidad, $precioUnitario, $costoUnitario, $tallaId);

        return $venta;
    }

    private function crearDetalle(
        Venta $venta,
        Producto $producto,
        int $cantidad,
        float $precioUnitario,
        ?float $costoUnitario,
        ?int $tallaId = null
    ): DetalleVenta {
        return DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'talla_id' => $tallaId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'costo_unitario' => $costoUnitario,
            'subtotal' => $precioUnitario * $cantidad,
        ]);
    }

    private function registrarVenta(User $usuario, array $productos)
    {
        return $this->actingAs($usuario)->post(route('ventas.store'), [
            'metodo_pago' => 'efectivo',
            'productos' => json_encode($productos),
        ]);
    }
}