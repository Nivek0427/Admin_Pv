<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talla extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
    ];

    public function productoTallas()
    {
        return $this->hasMany(ProductoTalla::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_talla')
            ->withPivot('stock')
            ->withTimestamps();
    }
}
