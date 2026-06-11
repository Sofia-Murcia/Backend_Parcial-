<?php
namespace App\Pedidos\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    public $timestamps = false;

    public function detalles()
    {
        return $this->hasMany(Detalles::class, 'pedido_id');
    }
}
