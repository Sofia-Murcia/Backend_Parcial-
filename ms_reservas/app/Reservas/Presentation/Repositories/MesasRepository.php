<?php
namespace App\Reservas\Presentation\Repositories;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Reservas\Controllers\MesaController;
use Exception;

class MesasRepository
{
    private function validateToken(Request $request)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token      = str_replace('Bearer ', '', $authHeader);
        if (empty($token)) throw new Exception('Token no proporcionado', 401);

        $usuario = \Illuminate\Database\Capsule\Manager::connection('auth')
            ->table('usuarios')
            ->where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) throw new Exception('Sesión inválida o expirada', 401);
        return $usuario;
    }

    public function all(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $controller = new MesaController();
            $mesas = $controller->getMesas();
            $response->getBody()->write(json_encode(['success' => true, 'data' => $mesas]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() >= 400 ? $ex->getCode() : 400;
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }

    public function detail(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new MesaController();
            $mesa = $controller->getMesa($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $mesa]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }

    public function create(Request $request, Response $response)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new MesaController();
            $mesa = $controller->crearMesa($data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Mesa creada', 'data' => $mesa]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() >= 400 ? $ex->getCode() : 400;
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            $controller = new MesaController();
            $mesa = $controller->modificarMesa($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Mesa actualizada', 'data' => $mesa]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }

    public function cambiarEstado(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $data   = json_decode($request->getBody()->getContents(), true);
            $estado = $data['estado'] ?? '';
            $controller = new MesaController();
            $mesa = $controller->cambiarEstado($args['id'], $estado);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Estado actualizado', 'data' => $mesa]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }
}
