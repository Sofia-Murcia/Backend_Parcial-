<?php
namespace App\Auth\Controllers;

use App\Auth\Models\Usuario;
use Exception;

class AuthController
{
    public function login($data)
    {
        $identificador = trim($data['usuario'] ?? $data['correo'] ?? '');
        $contrasena    = trim($data['contrasena'] ?? '');

        if (empty($identificador) || empty($contrasena)) {
            throw new Exception('Usuario/correo y contraseña son obligatorios', 400);
        }

        $usuario = Usuario::where(function ($query) use ($identificador) {
            $query->where('usuario', $identificador)
                  ->orWhere('correo', $identificador);
        })->where('estado', 'activo')->first();

        if (!$usuario || $usuario->contrasena !== $contrasena) {
            throw new Exception('Credenciales incorrectas', 401);
        }

        $token = bin2hex(random_bytes(32));

        $usuario->token         = $token;
        $usuario->sesion_activa = true;
        $usuario->updated_at    = date('Y-m-d H:i:s');
        $usuario->save();

        return [
            'token'   => $token,
            'usuario' => [
                'id'      => $usuario->id,
                'nombre'  => $usuario->nombre,
                'correo'  => $usuario->correo,
                'usuario' => $usuario->usuario,
                'rol'     => $usuario->rol,
            ],
        ];
    }

    public function logout($token)
    {
        $usuario = $this->validarToken($token);
        $usuario->token         = null;
        $usuario->sesion_activa = false;
        $usuario->updated_at    = date('Y-m-d H:i:s');
        $usuario->save();
    }

    public function validarToken($token)
    {
        if (empty($token)) {
            throw new Exception('Token no proporcionado', 401);
        }

        $usuario = Usuario::where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            throw new Exception('Sesión inválida o expirada', 401);
        }

        return $usuario;
    }
}
