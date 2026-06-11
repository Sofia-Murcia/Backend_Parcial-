<?php
namespace App\Reservas\Controllers;

use App\Reservas\Models\Mesa;
use Exception;

class MesaController
{
    public function getMesas()
    {
        return Mesa::all();
    }

    public function getMesa($id)
    {
        $mesa = Mesa::find($id);
        if (empty($mesa)) {
            throw new Exception("La mesa $id no existe", 1);
        }
        return $mesa;
    }

    public function crearMesa($data)
    {
        $numero    = trim($data['numero']   ?? '');
        $capacidad = intval($data['capacidad'] ?? 0);
        $estado    = trim($data['estado']   ?? 'disponible');

        if (empty($numero)) {
            throw new Exception('El número de mesa es obligatorio', 400);
        }
        if ($capacidad <= 0) {
            throw new Exception('La capacidad debe ser mayor a cero', 400);
        }
        if (Mesa::where('numero', $numero)->exists()) {
            throw new Exception('Ya existe una mesa con ese número', 409);
        }

        $mesa = new Mesa();
        $mesa->numero    = $numero;
        $mesa->capacidad = $capacidad;
        $mesa->estado    = in_array($estado, ['disponible','reservada','ocupada','fuera_servicio']) ? $estado : 'disponible';
        $mesa->created_at = date('Y-m-d H:i:s');
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();
        return $mesa;
    }

    public function modificarMesa($id, $data)
    {
        $mesa = $this->getMesa($id);

        if (isset($data['capacidad'])) {
            $cap = intval($data['capacidad']);
            if ($cap <= 0) throw new Exception('La capacidad debe ser mayor a cero', 400);
            $mesa->capacidad = $cap;
        }
        if (!empty($data['estado'])) {
            $estados = ['disponible','reservada','ocupada','fuera_servicio'];
            if (!in_array($data['estado'], $estados)) throw new Exception('Estado inválido', 400);
            $mesa->estado = $data['estado'];
        }
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();
        return $mesa;
    }

    public function cambiarEstado($id, $estado)
    {
        $estados = ['disponible','reservada','ocupada','fuera_servicio'];
        if (!in_array($estado, $estados)) {
            throw new Exception('Estado inválido', 400);
        }
        $mesa = $this->getMesa($id);
        $mesa->estado     = $estado;
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();
        return $mesa;
    }
}
