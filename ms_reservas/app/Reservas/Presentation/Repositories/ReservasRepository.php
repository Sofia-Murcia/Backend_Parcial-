<?php
namespace App\Reservas\Presentation\Repositories;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Reservas\Controllers\ReservaController;
use Exception;

class ReservasRepository
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
            $filtros = $request->getQueryParams();
            $controller = new ReservaController();
            $reservas = $controller->getReservas($filtros);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $reservas]));
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
            $controller = new ReservaController();
            $reserva = $controller->getReserva($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'data' => $reserva]));
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
            $controller = new ReservaController();
            $reserva = $controller->crearReserva($data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Reserva creada', 'data' => $reserva]));
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
            $controller = new ReservaController();
            $reserva = $controller->modificarReserva($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Reserva actualizada', 'data' => $reserva]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }

    public function cancelar(Request $request, Response $response, $args)
    {
        try {
            $this->validateToken($request);
            $controller = new ReservaController();
            $reserva = $controller->cancelarReserva($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Reserva cancelada', 'data' => $reserva]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => $ex->getMessage()]));
            $code = $ex->getCode() == 1 ? 404 : ($ex->getCode() >= 400 ? $ex->getCode() : 400);
            return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
        }
    }
}
