<?php
namespace App\Reservas\Controllers;

use App\Reservas\Models\Reserva;
use App\Reservas\Models\Mesa;
use Exception;

class ReservaController
{
    public function getReservas($filtros = [])
    {
        $query = Reserva::with('mesa');
        if (!empty($filtros['estado']))  $query->where('estado', $filtros['estado']);
        if (!empty($filtros['fecha']))   $query->where('fecha', $filtros['fecha']);
        if (!empty($filtros['cliente'])) $query->where('nombre_cliente', 'like', '%'.$filtros['cliente'].'%');
        return $query->orderBy('fecha')->orderBy('hora')->get();
    }

    public function getReserva($id)
    {
        $reserva = Reserva::with('mesa')->find($id);
        if (empty($reserva)) throw new Exception("La reserva $id no existe", 1);
        return $reserva;
    }

    public function crearReserva($data)
    {
        $nombre   = trim($data['nombre_cliente']    ?? '');
        $telefono = trim($data['telefono_cliente']  ?? '');
        $personas = intval($data['cantidad_personas'] ?? 0);
        $fecha    = trim($data['fecha']             ?? '');
        $hora     = trim($data['hora']              ?? '');
        $mesaId   = intval($data['mesa_id']         ?? 0);
        $obs      = trim($data['observaciones']     ?? '');

        if (empty($nombre) || empty($telefono) || empty($fecha) || empty($hora) || $mesaId <= 0) {
            throw new Exception('Nombre, teléfono, fecha, hora y mesa son obligatorios', 400);
        }
        if ($personas <= 0) throw new Exception('La cantidad de personas debe ser mayor a cero', 400);
        if ($fecha < date('Y-m-d'))  throw new Exception('No se permiten reservas en fechas pasadas', 400);

        $mesa = Mesa::find($mesaId);
        if (!$mesa) throw new Exception('Mesa no encontrada', 1);
        if ($mesa->estado === 'fuera_servicio') throw new Exception('No se puede reservar una mesa fuera de servicio', 400);
        if ($personas > $mesa->capacidad) throw new Exception("La mesa solo tiene capacidad para {$mesa->capacidad} personas", 400);

        $duplicada = Reserva::where('mesa_id', $mesaId)
            ->where('fecha', $fecha)->where('hora', $hora)
            ->whereIn('estado', ['pendiente','confirmada'])->exists();
        if ($duplicada) throw new Exception('Ya existe una reserva para esa mesa en ese horario', 409);

        $reserva = new Reserva();
        $reserva->nombre_cliente    = $nombre;
        $reserva->telefono_cliente  = $telefono;
        $reserva->cantidad_personas = $personas;
        $reserva->fecha             = $fecha;
        $reserva->hora              = $hora;
        $reserva->observaciones     = $obs ?: null;
        $reserva->estado            = 'pendiente';
        $reserva->mesa_id           = $mesaId;
        $reserva->created_at        = date('Y-m-d H:i:s');
        $reserva->updated_at        = date('Y-m-d H:i:s');
        $reserva->save();

        $mesa->estado = 'reservada';
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();

        return $reserva->load('mesa');
    }

    public function modificarReserva($id, $data)
    {
        $reserva = $this->getReserva($id);
        if (in_array($reserva->estado, ['cancelada','finalizada'])) {
            throw new Exception('No se puede editar una reserva cancelada o finalizada', 400);
        }

        $fecha   = $data['fecha']              ?? $reserva->fecha;
        $hora    = $data['hora']               ?? $reserva->hora;
        $mesaId  = intval($data['mesa_id']     ?? $reserva->mesa_id);
        $personas= intval($data['cantidad_personas'] ?? $reserva->cantidad_personas);
        $obs     = $data['observaciones']      ?? $reserva->observaciones;

        if ($fecha < date('Y-m-d')) throw new Exception('No se permiten fechas pasadas', 400);

        $reserva->fecha              = $fecha;
        $reserva->hora               = $hora;
        $reserva->mesa_id            = $mesaId;
        $reserva->cantidad_personas  = $personas;
        $reserva->observaciones      = $obs;
        $reserva->updated_at         = date('Y-m-d H:i:s');
        $reserva->save();
        return $reserva->load('mesa');
    }

    public function cancelarReserva($id)
    {
        $reserva = $this->getReserva($id);
        if ($reserva->estado === 'cancelada') throw new Exception('La reserva ya está cancelada', 400);

        $reserva->estado     = 'cancelada';
        $reserva->updated_at = date('Y-m-d H:i:s');
        $reserva->save();

        $mesa = Mesa::find($reserva->mesa_id);
        if ($mesa && $mesa->estado === 'reservada') {
            $mesa->estado     = 'disponible';
            $mesa->updated_at = date('Y-m-d H:i:s');
            $mesa->save();
        }
        return $reserva;
    }
}
