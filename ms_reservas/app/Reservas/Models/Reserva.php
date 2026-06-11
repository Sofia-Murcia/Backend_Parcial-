<?php
namespace App\Reservas\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';
    public $timestamps = false;

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}
