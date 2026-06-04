<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Detalles;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PedidoController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Pedido::with('detalles');

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $pedidos = $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->get();

        return $this->json($response, [
            'success' => true,
            'data'    => $pedidos,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::with('detalles')->find($args['id']);

        if (!$pedido) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data'    => $pedido,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $mesaId   = intval($body['mesa_id'] ?? 0);
        $fecha    = trim($body['fecha']     ?? date('Y-m-d'));
        $hora     = trim($body['hora']      ?? date('H:i:s'));
        $productos = $body['productos']     ?? [];

        if ($mesaId <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El ID de mesa es obligatorio',
            ], 400);
        }

        if (empty($productos) || !is_array($productos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El pedido debe tener al menos un producto',
            ], 400);
        }

        foreach ($productos as $item) {
            if (empty($item['nombre_producto']) || intval($item['cantidad'] ?? 0) < 1) {
                return $this->json($response, [
                    'success' => false,
                    'message' => 'Cada producto debe tener nombre y cantidad mayor a cero',
                ], 400);
            }
        }

        $subtotal = 0;
        foreach ($productos as $item) {
            $subtotal += floatval($item['precio_unitario'] ?? 0) * intval($item['cantidad']);
        }

        $pedido = Pedido::create([
            'mesa_id'    => $mesaId,
            'fecha'      => $fecha,
            'hora'       => $hora,
            'subtotal'   => $subtotal,
            'total'      => $subtotal,
            'estado'     => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($productos as $item) {
            $cantidad      = intval($item['cantidad']);
            $precioUnit    = floatval($item['precio_unitario'] ?? 0);
            $subtotalItem  = $precioUnit * $cantidad;

            Detalles::create([
                'pedido_id'       => $pedido->id,
                'producto_id'     => intval($item['producto_id'] ?? 0),
                'nombre_producto' => trim($item['nombre_producto']),
                'cantidad'        => $cantidad,
                'precio_unitario' => $precioUnit,
                'subtotal'        => $subtotalItem,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->json($response, [
            'success' => true,
            'message' => 'Pedido creado correctamente',
            'data'    => $pedido->load('detalles'),
        ], 201);
    }

    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find($args['id']);

        if (!$pedido) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        $body   = $request->getParsedBody();
        $estado = trim($body['estado'] ?? '');

        $estadosValidos = ['pendiente', 'en_preparacion', 'entregado', 'pagado', 'cancelado'];

        if (empty($estado) || !in_array($estado, $estadosValidos)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido. Use: pendiente, en_preparacion, entregado, pagado, cancelado',
            ], 400);
        }

        $pedido->estado     = $estado;
        $pedido->updated_at = date('Y-m-d H:i:s');
        $pedido->save();

        return $this->json($response, [
            'success' => true,
            'message' => "Estado del pedido actualizado a '{$estado}'",
            'data'    => $pedido->load('detalles'),
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
