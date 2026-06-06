<?php

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductoController
{
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $query = Producto::with('categoria');

        // Filtrar por categoría
        if (!empty($params['categoria_id'])) {
            $query->where('categoria_id', intval($params['categoria_id']));
        }

        // Filtrar por disponibilidad
        if (isset($params['disponible'])) {
            $query->where('disponible', filter_var($params['disponible'], FILTER_VALIDATE_BOOLEAN));
        }

        $productos = $query->orderBy('nombre')->get();

        return $this->json($response, [
            'success' => true,
            'data'    => $productos,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::with('categoria')->find($args['id']);

        if (!$producto) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data'    => $producto,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $nombre      = trim($body['nombre']       ?? '');
        $descripcion = trim($body['descripcion']  ?? '');
        $precio      = floatval($body['precio']   ?? 0);
        $disponible  = isset($body['disponible'])
            ? filter_var($body['disponible'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $categoriaId = intval($body['categoria_id'] ?? 0);

        // Validaciones
        if (empty($nombre)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El nombre del producto es obligatorio',
            ], 400);
        }

        if ($precio <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El precio debe ser mayor a cero',
            ], 400);
        }

        if ($categoriaId <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Debe indicar una categoría válida',
            ], 400);
        }

        $categoria = Categoria::find($categoriaId);
        if (!$categoria) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La categoría indicada no existe',
            ], 404);
        }

        $existe = Producto::where('nombre', $nombre)->first();
        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe un producto con ese nombre',
            ], 409);
        }

        $producto = Producto::create([
            'nombre'      => $nombre,
            'descripcion' => $descripcion ?: null,
            'precio'      => $precio,
            'disponible'  => $disponible,
            'categoria_id'=> $categoriaId,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Producto creado correctamente',
            'data'    => $producto->load('categoria'),
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find($args['id']);

        if (!$producto) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $body = $request->getParsedBody();

        $nombre      = trim($body['nombre']       ?? $producto->nombre);
        $descripcion = $body['descripcion']        ?? $producto->descripcion;
        $precio      = isset($body['precio'])
            ? floatval($body['precio'])
            : $producto->precio;
        $disponible  = isset($body['disponible'])
            ? filter_var($body['disponible'], FILTER_VALIDATE_BOOLEAN)
            : $producto->disponible;
        $categoriaId = isset($body['categoria_id'])
            ? intval($body['categoria_id'])
            : $producto->categoria_id;

        // Validaciones
        if (empty($nombre)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El nombre del producto no puede estar vacío',
            ], 400);
        }

        if ($precio <= 0) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El precio debe ser mayor a cero',
            ], 400);
        }

        $categoria = Categoria::find($categoriaId);
        if (!$categoria) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La categoría indicada no existe',
            ], 404);
        }

        $existe = Producto::where('nombre', $nombre)
            ->where('id', '!=', $producto->id)
            ->first();

        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe otro producto con ese nombre',
            ], 409);
        }

        $producto->nombre       = $nombre;
        $producto->descripcion  = $descripcion;
        $producto->precio       = $precio;
        $producto->disponible   = $disponible;
        $producto->categoria_id = $categoriaId;
        $producto->updated_at   = date('Y-m-d H:i:s');
        $producto->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'data'    => $producto->load('categoria'),
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find($args['id']);

        if (!$producto) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $producto->delete();

        return $this->json($response, [
            'success' => true,
            'message' => 'Producto eliminado correctamente',
        ]);
    }

    public function cambiarDisponibilidad(Request $request, Response $response, array $args): Response
    {
        $producto = Producto::find($args['id']);

        if (!$producto) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $body       = $request->getParsedBody();
        $disponible = filter_var($body['disponible'] ?? !$producto->disponible, FILTER_VALIDATE_BOOLEAN);

        $producto->disponible  = $disponible;
        $producto->updated_at  = date('Y-m-d H:i:s');
        $producto->save();

        $estado = $disponible ? 'disponible' : 'no disponible';

        return $this->json($response, [
            'success' => true,
            'message' => "Producto marcado como {$estado}",
            'data'    => $producto->load('categoria'),
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
