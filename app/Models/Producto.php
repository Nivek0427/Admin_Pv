<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventarioMovimiento;
use Illuminate\Support\Facades\Auth;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria',
        'descripcion',
        'precio',
        'stock',
        'genero',
    ];

    /**
     * Relación: un producto puede aparecer en muchos detalles de venta.
     */
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    public function productoTallas()
    {
        return $this->hasMany(ProductoTalla::class);
    }

    public function tallas()
    {
        return $this->belongsToMany(Talla::class, 'producto_talla')
            ->withPivot('stock')
            ->withTimestamps();
    }

    public function esZapato(): bool
    {
        return $this->categoria === 'Zapatos';
    }

    public function sincronizarStockTotal(): int
    {
        $stockTotal = (int) $this->productoTallas()->sum('stock');
        $this->update(['stock' => $stockTotal]);

        return $stockTotal;
    }

    public function aumentarStockTalla(int $tallaId, int $cantidad): ProductoTalla
    {
        if ($cantidad < 0) {
            throw new \InvalidArgumentException('La cantidad a aumentar no puede ser negativa.');
        }

        $productoTalla = $this->productoTallas()
            ->where('talla_id', $tallaId)
            ->lockForUpdate()
            ->firstOrFail();

        $productoTalla->increment('stock', $cantidad);
        $productoTalla->refresh();

        return $productoTalla;
    }

    public function disminuirStockTalla(int $tallaId, int $cantidad): ProductoTalla
    {
        if ($cantidad < 0) {
            throw new \InvalidArgumentException('La cantidad a disminuir no puede ser negativa.');
        }

        $productoTalla = $this->productoTallas()
            ->where('talla_id', $tallaId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($productoTalla->stock < $cantidad) {
            throw new \InvalidArgumentException('La talla no tiene suficiente stock.');
        }

        $productoTalla->decrement('stock', $cantidad);
        $productoTalla->refresh();

        return $productoTalla;
    }

    /**
     * Disminuye el stock del producto (al realizar una venta).
     */
    public function disminuirStock($cantidad)
    {
        $this->stock -= $cantidad;
        $this->save();
    }

    /**
     * Aumenta el stock del producto (al revocar una venta).
     */
    public function aumentarStock($cantidad)
    {
        $this->stock += $cantidad;
        $this->save();
    }

    public function registrarMovimiento(int $cantidad, string $tipo, ?int $usuarioId = null)
    {
        $usuarioId = $usuarioId ?? Auth::id();

        return InventarioMovimiento::create([
            'producto_id' => $this->id,
            'cantidad' => $cantidad,
            'tipo' => $tipo,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function registrarMovimientoConTalla(
        int $tallaId,
        int $cantidad,
        string $tipo,
        ?int $usuarioId = null
    ) {
        $usuarioId = $usuarioId ?? Auth::id();

        return InventarioMovimiento::create([
            'producto_id' => $this->id,
            'talla_id' => $tallaId,
            'cantidad' => $cantidad,
            'tipo' => $tipo,
            'usuario_id' => $usuarioId,
        ]);
    }


}
