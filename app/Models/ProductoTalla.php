<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoTalla extends Model
{
    use HasFactory;

    protected $table = 'producto_talla';

    protected $fillable = [
        'producto_id',
        'talla_id',
        'stock',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function talla()
    {
        return $this->belongsTo(Talla::class);
    }
}
