<?php
namespace App\Pedidos\Models;

use Illuminate\Database\Eloquent\Model;

class Detalles extends Model
{
    protected $table = 'detalles_pedidos';
    public $timestamps = false;

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
