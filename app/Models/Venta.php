<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'cliente',
        'total',
        'estado', // 'activa' o 'revocada'
        'revocada_fecha',
        'revocada_motivo',
        'metodo_pago',
    ];

    protected $dates = ['revocada_fecha'];

    protected $casts = [
        'revocada_fecha' => 'datetime',
    ];

    /**
     * Una venta tiene muchos detalles de venta.
     */
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    /**
     * Calcula el total automáticamente sumando los subtotales.
     */
    public function calcularTotal()
    {
        return $this->detalles->sum('subtotal');
    }
}
