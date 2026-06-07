<?php

namespace App\Controllers;

use App\Models\Mesa;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MesaController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Mesa::query();

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $mesas = $query->orderBy('numero')->get();

        return $this->json($response, [
            'success' => true,
            'data'    => $mesas,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $mesa = Mesa::find($args['id']);

        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data'    => $mesa,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $numero    = trim($body['numero']    ?? '');
        $capacidad = intval($body['capacidad'] ?? 0);
        $estado    = $body['estado'] ?? 'disponible';

        if (empty($numero)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El número/nombre de la mesa es obligatorio',
            ], 400);
        }

        if ($capacidad <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La capacidad debe ser mayor a cero',
            ], 400);
        }

        $estadosValidos = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];
        if (!in_array($estado, $estadosValidos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos),
            ], 400);
        }

        $existe = Mesa::where('numero', $numero)->first();
        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe una mesa con ese número',
            ], 409);
        }

        $mesa = Mesa::create([
            'numero'     => $numero,
            'capacidad'  => $capacidad,
            'estado'     => $estado,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Mesa creada correctamente',
            'data'    => $mesa,
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $mesa = Mesa::find($args['id']);

        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }

        $body = $request->getParsedBody();

        $capacidad = isset($body['capacidad']) ? intval($body['capacidad']) : $mesa->capacidad;
        $estado    = $body['estado'] ?? $mesa->estado;

        if ($capacidad <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La capacidad debe ser mayor a cero',
            ], 400);
        }

        $estadosValidos = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];
        if (!in_array($estado, $estadosValidos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos),
            ], 400);
        }

        $mesa->capacidad   = $capacidad;
        $mesa->estado      = $estado;
        $mesa->updated_at  = date('Y-m-d H:i:s');
        $mesa->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Mesa actualizada correctamente',
            'data'    => $mesa,
        ]);
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $mesa = Mesa::find($args['id']);

        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }

        $body   = $request->getParsedBody();
        $estado = $body['estado'] ?? '';

        $estadosValidos = ['disponible', 'reservada', 'ocupada', 'fuera_servicio'];
        if (!in_array($estado, $estadosValidos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido. Valores permitidos: ' . implode(', ', $estadosValidos),
            ], 400);
        }

        $mesa->estado     = $estado;
        $mesa->updated_at = date('Y-m-d H:i:s');
        $mesa->save();

        return $this->json($response, [
            'success' => true,
            'message' => "Estado de la mesa actualizado a '{$estado}'",
            'data'    => $mesa,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $mesa = Mesa::find($args['id']);

        if (!$mesa) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }

        if ($mesa->reservas()->whereIn('estado', ['pendiente', 'confirmada'])->count() > 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se puede eliminar una mesa con reservas activas',
            ], 409);
        }

        $mesa->delete();

        return $this->json($response, [
            'success' => true,
            'message' => 'Mesa eliminada correctamente',
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
