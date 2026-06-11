<?php
namespace App\Reservas\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $table = 'mesas';
    public $timestamps = false;

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'mesa_id');
    }
}
