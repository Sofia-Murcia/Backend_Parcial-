<?php

namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Detalles;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DetallesController
{
    public function store(Request $request, Response $response, array $args): Response
    {
        $pedido = Pedido::find($args['id']);

        if (!$pedido) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        if (in_array($pedido->estado, ['pagado', 'cancelado'])) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se pueden agregar productos a un pedido pagado o cancelado',
            ], 400);
        }

        $body = $request->getParsedBody();

        $productoId     = intval($body['producto_id']     ?? 0);
        $nombreProducto = trim($body['nombre_producto']   ?? '');
        $cantidad       = intval($body['cantidad']        ?? 0);
        $precioUnitario = floatval($body['precio_unitario'] ?? 0);

        if (empty($nombreProducto)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El nombre del producto es obligatorio',
            ], 400);
        }

        if ($cantidad < 1) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La cantidad debe ser mayor a cero',
            ], 400);
        }

        if ($precioUnitario <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El precio debe ser mayor a cero',
            ], 400);
        }

        $subtotalDetalle = $precioUnitario * $cantidad;

        $detalle = Detalles::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $productoId,
            'nombre_producto' => $nombreProducto,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal'        => $subtotalDetalle,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->recalcularTotales($pedido);

        return $this->json($response, [
            'success' => true,
            'message' => 'Producto agregado al pedido',
            'data'    => $detalle,
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $detalle = Detalles::where('id', $args['detalleId'])
            ->where('pedido_id', $args['id'])
            ->first();

        if (!$detalle) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Detalle no encontrado',
            ], 404);
        }

        $body     = $request->getParsedBody();
        $cantidad = intval($body['cantidad'] ?? 0);

        if ($cantidad < 1) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La cantidad debe ser mayor a cero',
            ], 400);
        }

        $detalle->cantidad   = $cantidad;
        $detalle->subtotal   = $detalle->precio_unitario * $cantidad;
        $detalle->updated_at = date('Y-m-d H:i:s');
        $detalle->save();

        $pedido = Pedido::find($args['id']);
        $this->recalcularTotales($pedido);

        return $this->json($response, [
            'success' => true,
            'message' => 'Cantidad actualizada correctamente',
            'data'    => $detalle,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $detalle = Detalles::where('id', $args['detalleId'])
            ->where('pedido_id', $args['id'])
            ->first();

        if (!$detalle) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Detalle no encontrado',
            ], 404);
        }

        $totalDetalles = Detalles::where('pedido_id', $args['id'])->count();
        if ($totalDetalles <= 1) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El pedido debe tener al menos un producto',
            ], 400);
        }

        $detalle->delete();

        $pedido = Pedido::find($args['id']);
        $this->recalcularTotales($pedido);

        return $this->json($response, [
            'success' => true,
            'message' => 'Producto eliminado del pedido',
        ]);
    }

    private function recalcularTotales(Pedido $pedido): void
    {
        $total = Detalles::where('pedido_id', $pedido->id)->sum('subtotal');

        $pedido->subtotal   = $total;
        $pedido->total      = $total;
        $pedido->updated_at = date('Y-m-d H:i:s');
        $pedido->save();
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
