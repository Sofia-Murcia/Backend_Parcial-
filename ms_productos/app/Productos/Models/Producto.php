<?php
namespace App\Productos\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    public $timestamps = false;

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
