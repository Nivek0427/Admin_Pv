<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'producto_id',
        'talla_id',
        'cantidad',
        'precio_unitario',
        'costo_unitario',
        'subtotal',
    ];

    /**
     * Cada detalle pertenece a una venta.
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Cada detalle pertenece a un producto.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function talla()
    {
        return $this->belongsTo(Talla::class);
    }
}
