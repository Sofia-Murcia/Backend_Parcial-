<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'mesa_id',
        'fecha',
        'hora',
        'subtotal',
        'total',
        'estado',
    ];

    public function detalles()
    {
        return $this->hasMany(Detalles::class, 'pedido_id');
    }
}
