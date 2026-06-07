<?php

namespace App\Controllers;

use App\Models\Mesa;
use App\Models\Reserva;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReservaController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Reserva::with('mesa');

        if (!empty($params['fecha'])) {
            $query->where('fecha', $params['fecha']);
        }

       if (!empty($params['cliente'])) {
            $query->where('nombre_cliente', 'like', '%' . $params['cliente'] . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $reservas = $query->orderBy('fecha')->orderBy('hora')->get();

        return $this->json($response, [
            'success' => true,
            'data'    => $reservas,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::with('mesa')->find($args['id']);

        if (!$reserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Reserva no encontrada',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data'    => $reserva,
        ]);
    }

   public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $nombreCliente    = trim($body['nombre_cliente']    ?? '');
        $telefonoCliente  = trim($body['telefono_cliente']  ?? '');
        $cantidadPersonas = intval($body['cantidad_personas'] ?? 0);
        $fecha            = $body['fecha']          ?? '';
        $hora             = $body['hora']           ?? '';
        $observaciones    = $body['observaciones']  ?? null;
        $mesaId           = intval($body['mesa_id'] ?? 0);

        if (empty($nombreCliente)) {
            return $this->json($response, ['success' => false, 'message' => 'El nombre del cliente es obligatorio'], 400);
        }
        if (empty($telefonoCliente)) {
            return $this->json($response, ['success' => false, 'message' => 'El teléfono del cliente es obligatorio'], 400);
        }
        if ($cantidadPersonas <= 0) {
            return $this->json($response, ['success' => false, 'message' => 'La cantidad de personas debe ser mayor a cero'], 400);
        }
        if (empty($fecha)) {
            return $this->json($response, ['success' => false, 'message' => 'La fecha es obligatoria'], 400);
        }
        if (empty($hora)) {
            return $this->json($response, ['success' => false, 'message' => 'La hora es obligatoria'], 400);
        }
        if ($mesaId <= 0) {
            return $this->json($response, ['success' => false, 'message' => 'Debe seleccionar una mesa válida'], 400);
        }

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se pueden crear reservas en fechas pasadas',
            ], 400);
        }

        $mesa = Mesa::find($mesaId);
        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La mesa indicada no existe',
            ], 404);
        }

        if ($mesa->estado === 'fuera_servicio') {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se puede reservar una mesa que está fuera de servicio',
            ], 409);
        }

       if ($cantidadPersonas > $mesa->capacidad) {
            return $this->json($response, [
                'success' => false,
                'message' => "La mesa {$mesa->numero} tiene capacidad máxima para {$mesa->capacidad} personas",
            ], 409);
        }

        $dobleReserva = Reserva::where('mesa_id', $mesaId)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->first();

        if ($dobleReserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Esa mesa ya tiene una reserva para la misma fecha y hora',
            ], 409);
        }

        $reserva = Reserva::create([
            'nombre_cliente'    => $nombreCliente,
            'telefono_cliente'  => $telefonoCliente,
            'cantidad_personas' => $cantidadPersonas,
            'fecha'             => $fecha,
            'hora'              => $hora,
            'observaciones'     => $observaciones,
            'estado'            => 'pendiente',
            'mesa_id'           => $mesaId,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $mesa->estado     = 'reservada';
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Reserva creada correctamente',
            'data'    => $reserva->load('mesa'),
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::with('mesa')->find($args['id']);

        if (!$reserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Reserva no encontrada',
            ], 404);
        }

        if (in_array($reserva->estado, ['cancelada', 'finalizada'])) {
            return $this->json($response, [
                'success' => false,
                'message' => "No se puede editar una reserva en estado '{$reserva->estado}'",
            ], 409);
        }

        $body = $request->getParsedBody();

        $fecha            = $body['fecha']             ?? $reserva->fecha;
        $hora             = $body['hora']              ?? $reserva->hora;
        $cantidadPersonas = isset($body['cantidad_personas'])
            ? intval($body['cantidad_personas'])
            : $reserva->cantidad_personas;
        $observaciones    = $body['observaciones']     ?? $reserva->observaciones;
        $mesaId           = isset($body['mesa_id'])
            ? intval($body['mesa_id'])
            : $reserva->mesa_id;

        $hoy = date('Y-m-d');
        if ($fecha < $hoy) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se pueden asignar fechas pasadas a una reserva',
            ], 400);
        }

        $mesa = Mesa::find($mesaId);
        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La mesa indicada no existe',
            ], 404);
        }

        if ($mesa->estado === 'fuera_servicio') {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se puede asignar una mesa que está fuera de servicio',
            ], 409);
        }

        if ($cantidadPersonas > $mesa->capacidad) {
            return $this->json($response, [
                'success' => false,
                'message' => "La mesa {$mesa->numero} tiene capacidad máxima para {$mesa->capacidad} personas",
            ], 409);
        }

        $dobleReserva = Reserva::where('mesa_id', $mesaId)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->where('id', '!=', $reserva->id)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->first();

        if ($dobleReserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Esa mesa ya tiene otra reserva para la misma fecha y hora',
            ], 409);
        }

        $reserva->fecha             = $fecha;
        $reserva->hora              = $hora;
        $reserva->cantidad_personas = $cantidadPersonas;
        $reserva->observaciones     = $observaciones;
        $reserva->mesa_id           = $mesaId;
        $reserva->updated_at        = date('Y-m-d H:i:s');
        $reserva->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Reserva actualizada correctamente',
            'data'    => $reserva->load('mesa'),
        ]);
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::with('mesa')->find($args['id']);

        if (!$reserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Reserva no encontrada',
            ], 404);
        }

        $body   = $request->getParsedBody();
        $estado = $body['estado'] ?? '';

        $estadosValidos = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];
        if (!in_array($estado, $estadosValidos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos),
            ], 400);
        }

        $estadoAnterior  = $reserva->estado;
        $reserva->estado = $estado;
        $reserva->updated_at = date('Y-m-d H:i:s');
        $reserva->save();

        $mesa = Mesa::find($reserva->mesa_id);
        if ($mesa) {
            if ($estado === 'cancelada' || $estado === 'finalizada') {
                // Verificar si hay otras reservas activas para esa mesa
                $otrasReservas = Reserva::where('mesa_id', $mesa->id)
                    ->where('id', '!=', $reserva->id)
                    ->whereIn('estado', ['pendiente', 'confirmada'])
                    ->exists();

                $mesa->estado     = $otrasReservas ? 'reservada' : 'disponible';
                $mesa->updated_at = date('Y-m-d H:i:s');
                $mesa->save();
            } elseif ($estado === 'confirmada') {
                $mesa->estado     = 'reservada';
                $mesa->updated_at = date('Y-m-d H:i:s');
                $mesa->save();
            }
        }

        return $this->json($response, [
            'success' => true,
            'message' => "Estado de la reserva actualizado a '{$estado}'",
            'data'    => $reserva->load('mesa'),
        ]);
    }

    public function cancelar(Request $request, Response $response, array $args): Response
    {
        $reserva = Reserva::with('mesa')->find($args['id']);

        if (!$reserva) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Reserva no encontrada',
            ], 404);
        }

        if (in_array($reserva->estado, ['cancelada', 'finalizada'])) {
            return $this->json($response, [
                'success' => false,
                'message' => "La reserva ya está en estado '{$reserva->estado}'",
            ], 409);
        }

        $reserva->estado     = 'cancelada';
        $reserva->updated_at = date('Y-m-d H:i:s');
        $reserva->save();

        // Liberar la mesa si no hay otras reservas activas
        $mesa = Mesa::find($reserva->mesa_id);
        if ($mesa) {
            $otrasReservas = Reserva::where('mesa_id', $mesa->id)
                ->where('id', '!=', $reserva->id)
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->exists();

            $mesa->estado     = $otrasReservas ? 'reservada' : 'disponible';
            $mesa->updated_at = date('Y-m-d H:i:s');
            $mesa->save();
        }

        return $this->json($response, [
            'success' => true,
            'message' => 'Reserva cancelada correctamente',
            'data'    => $reserva->load('mesa'),
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
