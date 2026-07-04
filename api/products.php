<?php
/**
 * api/products.php — CRUD de Productos
 * -----------------------------------------------------------------
 * Este archivo NUNCA se llama directamente desde el navegador.
 * Solo index.php lo incluye, después de validar el JWT.
 * Cada función corresponde a un método HTTP.
 */

// Conexión PDO (reutilizable en todo el archivo)
function obtenerConexion()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=ejemplotokens;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos.',
                'errors'  => ['db' => $e->getMessage()],
            ]));
        }
    }
    return $pdo;
}

// ----------------------------------------------------------------
// GET — Listar todos los productos o buscar por id
// ----------------------------------------------------------------
function obtenerProductos()
{
    $pdo = obtenerConexion();
    $id  = $_GET['id'] ?? null;

    try {
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id');
            $stmt->execute(['id' => (int)$id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => "Producto con id $id no encontrado.",
                ]);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Producto encontrado.',
                'data'    => $producto,
            ]);
        } else {
            $stmt     = $pdo->query('SELECT * FROM productos ORDER BY id DESC');
            $productos = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => count($productos) . ' producto(s) encontrado(s).',
                'data'    => $productos,
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener productos.']);
    }
}

// ----------------------------------------------------------------
// POST — Crear nuevo producto
// ----------------------------------------------------------------
function crearProducto()
{
    $pdo   = obtenerConexion();
    $datos = json_decode(file_get_contents('php://input'), true) ?? [];

    // Validaciones del servidor
    $errores = validarCampos($datos, false);
    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Revisa los campos marcados.',
            'errors'  => $errores,
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO productos (codigo, producto, precio, cantidad)
             VALUES (:codigo, :producto, :precio, :cantidad)'
        );
        $stmt->execute([
            'codigo'   => trim($datos['codigo']),
            'producto' => trim($datos['producto']),
            'precio'   => (float)$datos['precio'],
            'cantidad' => (int)$datos['cantidad'],
        ]);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data'    => ['id' => (int)$pdo->lastInsertId()],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear el producto.']);
    }
}

// ----------------------------------------------------------------
// PUT — Actualizar producto existente
// ----------------------------------------------------------------
function actualizarProducto($datos)
{
    $pdo = obtenerConexion();
    $id  = $_GET['id'] ?? $datos['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Se requiere el id del producto a actualizar.',
        ]);
        return;
    }

    $errores = validarCampos($datos, true);
    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Revisa los campos marcados.',
            'errors'  => $errores,
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'UPDATE productos
             SET codigo = :codigo, producto = :producto,
                 precio = :precio, cantidad = :cantidad
             WHERE id = :id'
        );
        $stmt->execute([
            'codigo'   => trim($datos['codigo']),
            'producto' => trim($datos['producto']),
            'precio'   => (float)$datos['precio'],
            'cantidad' => (int)$datos['cantidad'],
            'id'       => (int)$id,
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "No se encontró el producto con id $id.",
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto.']);
    }
}

// ----------------------------------------------------------------
// DELETE — Eliminar producto
// ----------------------------------------------------------------
function eliminarProducto()
{
    $pdo = obtenerConexion();
    $id  = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Se requiere el id del producto a eliminar.',
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM productos WHERE id = :id');
        $stmt->execute(['id' => (int)$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "No se encontró el producto con id $id.",
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Producto con id $id eliminado correctamente.",
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto.']);
    }
}

// ----------------------------------------------------------------
// Validación compartida (usada por POST y PUT)
// ----------------------------------------------------------------
function validarCampos($datos, $esEdicion)
{
    $errores = [];

    if (empty($datos['codigo']) || trim($datos['codigo']) === '') {
        $errores['codigo'] = 'El código es obligatorio.';
    } elseif (strlen($datos['codigo']) > 20) {
        $errores['codigo'] = 'El código no puede superar 20 caracteres.';
    }

    if (empty($datos['producto']) || trim($datos['producto']) === '') {
        $errores['producto'] = 'El nombre del producto es obligatorio.';
    } elseif (strlen($datos['producto']) > 100) {
        $errores['producto'] = 'El nombre no puede superar 100 caracteres.';
    }

    if (!isset($datos['precio']) || (float)$datos['precio'] <= 0) {
        $errores['precio'] = 'El precio debe ser mayor a 0.';
    }

    $cantidad = isset($datos['cantidad']) ? (int)$datos['cantidad'] : -1;
    if (!$esEdicion && $cantidad < 1) {
        $errores['cantidad'] = 'Un producto nuevo debe tener al menos 1 unidad.';
    } elseif ($esEdicion && $cantidad < 0) {
        $errores['cantidad'] = 'La cantidad no puede ser negativa.';
    }

    return $errores;
}