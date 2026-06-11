<?php
namespace App\Productos\Controllers;

use App\Productos\Models\Producto;
use App\Productos\Models\Categoria;
use Exception;

class ProductoController
{
    public function getProductos($filtros = [])
    {
        $query = Producto::with('categoria');
        if (!empty($filtros['categoria_id'])) $query->where('categoria_id', $filtros['categoria_id']);
        return $query->get();
    }

    public function getProducto($id)
    {
        $producto = Producto::with('categoria')->find($id);
        if (empty($producto)) throw new Exception("El producto $id no existe", 1);
        return $producto;
    }

    public function getCategorias()
    {
        return Categoria::all();
    }

    public function crearProducto($data)
    {
        $nombre      = trim($data['nombre']       ?? '');
        $precio      = floatval($data['precio']   ?? 0);
        $categoriaId = intval($data['categoria_id'] ?? 0);
        $descripcion = trim($data['descripcion']  ?? '');
        $disponible  = isset($data['disponible']) ? (bool)$data['disponible'] : true;

        if (empty($nombre))    throw new Exception('El nombre es obligatorio', 400);
        if ($precio <= 0)      throw new Exception('El precio debe ser mayor a cero', 400);
        if ($categoriaId <= 0) throw new Exception('La categoría es obligatoria', 400);
        if (Producto::where('nombre', $nombre)->exists()) throw new Exception('Ya existe un producto con ese nombre', 409);

        $producto = new Producto();
        $producto->nombre       = $nombre;
        $producto->descripcion  = $descripcion ?: null;
        $producto->precio       = $precio;
        $producto->disponible   = $disponible;
        $producto->categoria_id = $categoriaId;
        $producto->created_at   = date('Y-m-d H:i:s');
        $producto->updated_at   = date('Y-m-d H:i:s');
        $producto->save();
        return $producto->load('categoria');
    }

    public function modificarProducto($id, $data)
    {
        $producto = $this->getProducto($id);

        if (!empty($data['nombre']))       $producto->nombre       = $data['nombre'];
        if (isset($data['precio']))        $producto->precio       = floatval($data['precio']);
        if (isset($data['categoria_id']))  $producto->categoria_id = intval($data['categoria_id']);
        if (isset($data['descripcion']))   $producto->descripcion  = $data['descripcion'];
        if (isset($data['disponible']))    $producto->disponible   = (bool)$data['disponible'];

        if (isset($data['precio']) && floatval($data['precio']) <= 0) throw new Exception('El precio debe ser mayor a cero', 400);

        $producto->updated_at = date('Y-m-d H:i:s');
        $producto->save();
        return $producto->load('categoria');
    }

    public function borrarProducto($id)
    {
        $producto = $this->getProducto($id);
        $producto->delete();
    }
}
