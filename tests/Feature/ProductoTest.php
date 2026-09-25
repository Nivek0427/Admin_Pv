<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Producto;
use App\Models\Talla;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_legacy_categories_and_negative_cost(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test User',
            'username' => 'test-user',
            'password' => 'password',
        ]));

        $response = $this->from('/productos/create')->post(route('productos.store'), [
            'nombre' => 'Producto inválido',
            'categoria' => 'Ropa',
            'precio' => 25,
            'costo' => -1,
            'genero' => 'Mujer',
            'stock' => 0,
        ]);

        $response->assertSessionHasErrors(['categoria', 'costo']);
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_requires_cost_when_creating_a_product(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test User',
            'username' => 'test-user',
            'password' => 'password',
        ]));

        $response = $this->from('/productos/create')->post(route('productos.store'), [
            'nombre' => 'Gorra',
            'categoria' => 'Gorras',
            'precio' => 25,
            'stock' => 0,
        ]);

        $response->assertSessionHasErrors('costo');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_non_shoes_are_forced_to_hombre_and_shoes_keep_selected_gender(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test User',
            'username' => 'test-user',
            'password' => 'password',
        ]));

        foreach (['Camisas', 'Pantalones', 'Gorras', 'Accesorios'] as $categoria) {
            $this->post(route('productos.store'), [
                'nombre' => $categoria,
                'categoria' => $categoria,
                'precio' => 25,
                'costo' => 10,
                'genero' => 'Mujer',
                'stock' => 0,
            ])->assertRedirect(route('productos.index'));

            $this->assertDatabaseHas('productos', [
                'nombre' => $categoria,
                'categoria' => $categoria,
                'genero' => 'Hombre',
                'costo' => 10,
            ]);
        }

        $this->post(route('productos.store'), [
            'nombre' => 'Zapato',
            'categoria' => 'Zapatos',
            'precio' => 25,
            'costo' => 0,
            'genero' => 'Mujer',
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Zapato',
            'categoria' => 'Zapatos',
            'genero' => 'Mujer',
            'costo' => 0,
        ]);
    }

    public function test_updating_a_product_requires_cost_and_forces_hombre_for_non_shoes(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test User',
            'username' => 'test-user',
            'password' => 'password',
        ]));

        $producto = Producto::create([
            'nombre' => 'Prenda',
            'categoria' => 'Camisas',
            'precio' => 25,
            'costo' => 10,
            'genero' => 'Hombre',
            'stock' => 0,
        ]);

        $this->put(route('productos.update', $producto), [
            'nombre' => 'Prenda',
            'categoria' => 'Gorras',
            'precio' => 25,
            'genero' => 'Mujer',
        ])->assertSessionHasErrors('costo');

        $this->put(route('productos.update', $producto), [
            'nombre' => 'Prenda',
            'categoria' => 'Gorras',
            'precio' => 25,
            'costo' => 6,
            'genero' => 'Mujer',
        ])->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'categoria' => 'Gorras',
            'genero' => 'Hombre',
            'costo' => 6,
        ]);
    }

    public function test_shoe_sizes_must_match_the_selected_gender(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test User',
            'username' => 'test-user',
            'password' => 'password',
        ]));

        $tallaIds = [];
        foreach ([36, 37, 38, 40, 41, 44, 45] as $numero) {
            $tallaIds[$numero] = Talla::create(['numero' => $numero])->id;
        }

        $casos = [
            ['Mujer', 36, true],
            ['Mujer', 40, true],
            ['Mujer', 41, false],
            ['Hombre', 37, false],
            ['Hombre', 38, true],
            ['Hombre', 44, true],
            ['Hombre', 45, false],
        ];

        foreach ($casos as [$genero, $numero, $valida]) {
            $nombre = "Zapato {$genero} {$numero}";
            $datos = [
                'nombre' => $nombre,
                'categoria' => 'Zapatos',
                'precio' => 25,
                'costo' => 10,
                'genero' => $genero,
                'tallas' => [$tallaIds[$numero]],
                'stock_tallas' => [$tallaIds[$numero] => 2],
            ];

            $respuesta = $this->post(route('productos.store'), $datos);

            if ($valida) {
                $respuesta->assertRedirect(route('productos.index'));
                $this->assertDatabaseHas('producto_talla', [
                    'talla_id' => $tallaIds[$numero],
                    'stock' => 2,
                ]);
            } else {
                $respuesta->assertSessionHasErrors('tallas');
                $this->assertDatabaseMissing('productos', ['nombre' => $nombre]);
            }
        }

        $producto = Producto::where('nombre', 'Zapato Mujer 36')->firstOrFail();

        $this->put(route('productos.update', $producto), [
            'nombre' => $producto->nombre,
            'categoria' => 'Zapatos',
            'precio' => 25,
            'costo' => 10,
            'genero' => 'Hombre',
            'tallas' => [$tallaIds[36]],
            'stock_tallas' => [$tallaIds[36] => 2],
        ])->assertSessionHasErrors('tallas');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'genero' => 'Mujer',
        ]);
    }
}