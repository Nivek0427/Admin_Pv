<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria',
        'descripcion',
        'precio',
        'stock',
    ];

    /**
     * Relación: un producto puede aparecer en muchos detalles de venta.
     */
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
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
}
