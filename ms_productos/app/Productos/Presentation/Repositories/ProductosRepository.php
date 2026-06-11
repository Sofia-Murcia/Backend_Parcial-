<?php
namespace App\Productos\Presentation\Repositories;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Productos\Controllers\ProductoController;
use Exception;

class ProductosRepository
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

    public function allCategorias(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $controller = new ProductoController();
            return $this->json($response, ['success' => true, 'data' => $controller->getCategorias()]);
        } catch (Exception $ex) {
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $ex->getCode() >= 400 ? $ex->getCode() : 400);
        }
    }

    public function all(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $controller = new ProductoController();
            return $this->json($response, ['success' => true, 'data' => $controller->getProductos($request->getQueryParams())]);
        } catch (Exception $ex) {
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $ex->getCode() >= 400 ? $ex->getCode() : 400);
        }
    }

    public function detail(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new ProductoController();
            return $this->json($response, ['success' => true, 'data' => $controller->getProducto($args['id'])]);
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
            $controller = new ProductoController();
            $producto = $controller->crearProducto($data);
            return $this->json($response, ['success' => true, 'message' => 'Producto creado', 'data' => $producto], 201);
        } catch (Exception $ex) {
            $code = $ex->getCode() >= 400 ? $ex->getCode() : 400;
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new ProductoController();
            $producto = $controller->modificarProducto($args['id'], $data);
            return $this->json($response, ['success' => true, 'message' => 'Producto actualizado', 'data' => $producto]);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new ProductoController();
            $controller->borrarProducto($args['id']);
            return $this->json($response, ['success' => true, 'message' => 'Producto eliminado']);
        } catch (Exception $ex) {
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $this->json($response, ['success' => false, 'message' => $ex->getMessage()], $code);
        }
    }
}
