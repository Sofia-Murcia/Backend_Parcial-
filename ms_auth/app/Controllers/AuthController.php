<?php

namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $identificador = trim($body['usuario']  ?? $body['correo'] ?? '');
        $contrasena    = trim($body['contrasena'] ?? '');

        if (empty($identificador) || empty($contrasena)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Usuario/correo y contraseña son obligatorios',
            ], 400);
        }

        $usuario = Usuario::where(function ($query) use ($identificador) {
            $query->where('usuario', $identificador)
                  ->orWhere('correo', $identificador);
        })->where('estado', 'activo')->first();

        if (!$usuario || $usuario->contrasena !== $contrasena) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $token = bin2hex(random_bytes(32));

        $usuario->token         = $token;
        $usuario->sesion_activa = true;
        $usuario->updated_at    = now();
        $usuario->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data'    => [
                'token'  => $token,
                'usuario' => [
                    'id'     => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'correo' => $usuario->correo,
                    'usuario'=> $usuario->usuario,
                    'rol'    => $usuario->rol,
                ],
            ],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $usuario = $request->getAttribute('usuario');

        $usuario->token         = null;
        $usuario->sesion_activa = false;
        $usuario->updated_at    = now();
        $usuario->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function validate(Request $request, Response $response): Response
    {
        $usuario = $request->getAttribute('usuario');

        return $this->json($response, [
            'success' => true,
            'message' => 'Token válido',
            'data'    => [
                'id'     => $usuario->id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'usuario'=> $usuario->usuario,
                'rol'    => $usuario->rol,
            ],
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

function now(): string
{
    return date('Y-m-d H:i:s');
}
