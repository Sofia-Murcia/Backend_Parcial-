<?php
namespace App\Pedidos\Controllers;

use App\Pedidos\Models\Pedido;
use App\Pedidos\Models\Detalles;
use Exception;

class PedidoController
{
    public function getPedidos($filtros = [])
    {
        $query = Pedido::with('detalles');
        if (!empty($filtros['estado'])) $query->where('estado', $filtros['estado']);
        return $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->get();
    }

    public function getPedido($id)
    {
        $pedido = Pedido::with('detalles')->find($id);
        if (empty($pedido)) throw new Exception("El pedido $id no existe", 1);
        return $pedido;
    }

    public function crearPedido($data)
    {
        $mesaId    = intval($data['mesa_id']  ?? 0);
        $productos = $data['productos']       ?? [];
        $fecha     = $data['fecha']           ?? date('Y-m-d');
        $hora      = $data['hora']            ?? date('H:i:s');

        if ($mesaId <= 0) throw new Exception('El ID de mesa es obligatorio', 400);
        if (empty($productos)) throw new Exception('El pedido debe tener al menos un producto', 400);

        foreach ($productos as $item) {
            if (empty($item['nombre_producto']) || intval($item['cantidad'] ?? 0) < 1) {
                throw new Exception('Cada producto debe tener nombre y cantidad mayor a cero', 400);
            }
        }

        $subtotal = 0;
        foreach ($productos as $item) {
            $subtotal += floatval($item['precio_unitario'] ?? 0) * intval($item['cantidad']);
        }

        $pedido = new Pedido();
        $pedido->mesa_id    = $mesaId;
        $pedido->fecha      = $fecha;
        $pedido->hora       = $hora;
        $pedido->subtotal   = $subtotal;
        $pedido->total      = $subtotal;
        $pedido->estado     = 'pendiente';
        $pedido->created_at = date('Y-m-d H:i:s');
        $pedido->updated_at = date('Y-m-d H:i:s');
        $pedido->save();

        foreach ($productos as $item) {
            $cantidad   = intval($item['cantidad']);
            $precio     = floatval($item['precio_unitario'] ?? 0);
            $detalle = new Detalles();
            $detalle->pedido_id       = $pedido->id;
            $detalle->producto_id     = intval($item['producto_id'] ?? 0);
            $detalle->nombre_producto = trim($item['nombre_producto']);
            $detalle->cantidad        = $cantidad;
            $detalle->precio_unitario = $precio;
            $detalle->subtotal        = $precio * $cantidad;
            $detalle->created_at      = date('Y-m-d H:i:s');
            $detalle->updated_at      = date('Y-m-d H:i:s');
            $detalle->save();
        }

        return $pedido->load('detalles');
    }

    public function cambiarEstado($id, $estado)
    {
        $estados = ['pendiente','en_preparacion','entregado','pagado','cancelado'];
        if (!in_array($estado, $estados)) throw new Exception('Estado inválido', 400);
        $pedido = $this->getPedido($id);
        $pedido->estado     = $estado;
        $pedido->updated_at = date('Y-m-d H:i:s');
        $pedido->save();
        return $pedido->load('detalles');
    }

    public function agregarDetalle($pedidoId, $data)
    {
        $pedido = $this->getPedido($pedidoId);
        if (in_array($pedido->estado, ['pagado','cancelado'])) throw new Exception('No se pueden agregar productos a este pedido', 400);

        $nombre   = trim($data['nombre_producto']    ?? '');
        $cantidad = intval($data['cantidad']         ?? 0);
        $precio   = floatval($data['precio_unitario'] ?? 0);

        if (empty($nombre)) throw new Exception('El nombre del producto es obligatorio', 400);
        if ($cantidad < 1)  throw new Exception('La cantidad debe ser mayor a cero', 400);
        if ($precio <= 0)   throw new Exception('El precio debe ser mayor a cero', 400);

        $detalle = new Detalles();
        $detalle->pedido_id       = $pedidoId;
        $detalle->producto_id     = intval($data['producto_id'] ?? 0);
        $detalle->nombre_producto = $nombre;
        $detalle->cantidad        = $cantidad;
        $detalle->precio_unitario = $precio;
        $detalle->subtotal        = $precio * $cantidad;
        $detalle->created_at      = date('Y-m-d H:i:s');
        $detalle->updated_at      = date('Y-m-d H:i:s');
        $detalle->save();

        $this->recalcularTotales($pedido);
        return $detalle;
    }

    public function modificarDetalle($pedidoId, $detalleId, $data)
    {
        $detalle = Detalles::where('id', $detalleId)->where('pedido_id', $pedidoId)->first();
        if (!$detalle) throw new Exception('Detalle no encontrado', 1);

        $cantidad = intval($data['cantidad'] ?? 0);
        if ($cantidad < 1) throw new Exception('La cantidad debe ser mayor a cero', 400);

        $detalle->cantidad   = $cantidad;
        $detalle->subtotal   = $detalle->precio_unitario * $cantidad;
        $detalle->updated_at = date('Y-m-d H:i:s');
        $detalle->save();

        $pedido = $this->getPedido($pedidoId);
        $this->recalcularTotales($pedido);
        return $detalle;
    }

    public function eliminarDetalle($pedidoId, $detalleId)
    {
        $detalle = Detalles::where('id', $detalleId)->where('pedido_id', $pedidoId)->first();
        if (!$detalle) throw new Exception('Detalle no encontrado', 1);

        $total = Detalles::where('pedido_id', $pedidoId)->count();
        if ($total <= 1) throw new Exception('El pedido debe tener al menos un producto', 400);

        $detalle->delete();
        $pedido = $this->getPedido($pedidoId);
        $this->recalcularTotales($pedido);
    }

    private function recalcularTotales(Pedido $pedido)
    {
        $total = Detalles::where('pedido_id', $pedido->id)->sum('subtotal');
        $pedido->subtotal   = $total;
        $pedido->total      = $total;
        $pedido->updated_at = date('Y-m-d H:i:s');
        $pedido->save();
    }
}

