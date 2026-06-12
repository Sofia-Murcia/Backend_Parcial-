<?php
namespace App\Auth\Presentation\Repositories;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Auth\Controllers\AuthController;
use Exception;

class AuthRepository
{
    public function login(Request $request, Response $response)
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);

            $controller = new AuthController();
            $resultado  = $controller->login($data);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data'    => $resultado,
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $ex) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $ex->getMessage(),
            ]));

           $code = $ex->getCode();
            $httpCode = ($code >= 400 && $code <= 599) ? $code : 500;

            return $response->withStatus($httpCode)->withHeader('Content-Type', 'application/json');
        }
    }

    public function logout(Request $request, Response $response)
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $token      = str_replace('Bearer ', '', $authHeader);

            $controller = new AuthController();
            $controller->logout($token);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $ex) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $ex->getMessage(),
            ]));

            $code = $ex->getCode() >= 400 ? $ex->getCode() : 400;

            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }

    public function validate(Request $request, Response $response)
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $token      = str_replace('Bearer ', '', $authHeader);

            $controller = new AuthController();
            $usuario    = $controller->validarToken($token);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Token válido',
                'data'    => [
                    'id'      => $usuario->id,
                    'nombre'  => $usuario->nombre,
                    'correo'  => $usuario->correo,
                    'usuario' => $usuario->usuario,
                    'rol'     => $usuario->rol,
                ],
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (Exception $ex) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $ex->getMessage(),
            ]));

            $code = $ex->getCode();
$httpCode = ($code >= 400 && $code <= 599) ? $code : 500;

            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
