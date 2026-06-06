<?php

namespace App\Controllers;

use App\Models\Categoria;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CategoriaController
{
    public function index(Request $request, Response $response): Response
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->get();

        return $this->json($response, [
            'success' => true,
            'data'    => $categorias,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $categoria = Categoria::with('productos')->find($args['id']);

        if (!$categoria) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data'    => $categoria,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $nombre      = trim($body['nombre']      ?? '');
        $descripcion = trim($body['descripcion'] ?? '');

        if (empty($nombre)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El nombre de la categoría es obligatorio',
            ], 400);
        }

        $existe = Categoria::where('nombre', $nombre)->first();
        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe una categoría con ese nombre',
            ], 409);
        }

        $categoria = Categoria::create([
            'nombre'      => $nombre,
            'descripcion' => $descripcion ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data'    => $categoria,
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $categoria = Categoria::find($args['id']);

        if (!$categoria) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        $body = $request->getParsedBody();

        $nombre      = trim($body['nombre']      ?? $categoria->nombre);
        $descripcion = $body['descripcion']       ?? $categoria->descripcion;

        if (empty($nombre)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El nombre de la categoría no puede estar vacío',
            ], 400);
        }

        $existe = Categoria::where('nombre', $nombre)
            ->where('id', '!=', $categoria->id)
            ->first();

        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe otra categoría con ese nombre',
            ], 409);
        }

        $categoria->nombre      = $nombre;
        $categoria->descripcion = $descripcion;
        $categoria->updated_at  = date('Y-m-d H:i:s');
        $categoria->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data'    => $categoria,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $categoria = Categoria::find($args['id']);

        if (!$categoria) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        if ($categoria->productos()->count() > 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'No se puede eliminar una categoría que tiene productos asociados',
            ], 409);
        }

        $categoria->delete();

        return $this->json($response, [
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
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
