<?php
namespace App\Pedidos\Presentation\Repositories;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Pedidos\Controllers\PedidoController;
use Exception;

class PedidosRepository
{
    private function validateToken(Request $request)
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (empty($token)) throw new Exception('Token no proporcionado', 401);
        $usuario = \Illuminate\Database\Capsule\Manager::connection('auth')
            ->table('usuarios')->where('token', $token)->where('sesion_activa', true)->where('estado', 'activo')->first();
        if (!$usuario) throw new Exception('Sesión inválida o expirada', 401);
        return $usuario;
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    public function all(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $controller = new PedidoController();
            return $this->json($response, ['success' => true, 'data' => $controller->getPedidos($request->getQueryParams())]);
        } catch (Exception $ex) {
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $ex->getCode() >= 400 ? $ex->getCode() : 400);
        }
    }

    public function detail(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new PedidoController();
            return $this->json($response, ['success' => true, 'data' => $controller->getPedido($args['id'])]);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new PedidoController();
            $pedido = $controller->crearPedido($data);
            return $this->json($response, ['success' => true, 'message' => 'Pedido creado', 'data' => $pedido], 201);
        } catch (Exception $ex) {
            $code = $ex->getCode() >= 400 ? $ex->getCode() : 400;
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function cambiarEstado(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data   = json_decode($request->getBody()->getContents(), true);
            $controller = new PedidoController();
            $pedido = $controller->cambiarEstado($args['id'], $data['estado'] ?? '');
            return $this->json($response, ['success' => true, 'message' => 'Estado actualizado', 'data' => $pedido]);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function addDetalle(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new PedidoController();
            $detalle = $controller->agregarDetalle($args['id'], $data);
            return $this->json($response, ['success' => true, 'message' => 'Producto agregado', 'data' => $detalle], 201);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function updateDetalle(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new PedidoController();
            $detalle = $controller->modificarDetalle($args['id'], $args['detalleId'], $data);
            return $this->json($response, ['success' => true, 'message' => 'Cantidad actualizada', 'data' => $detalle]);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function deleteDetalle(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new PedidoController();
            $controller->eliminarDetalle($args['id'], $args['detalleId']);
            return $this->json($response, ['success' => true, 'message' => 'Producto eliminado del pedido']);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }
}
