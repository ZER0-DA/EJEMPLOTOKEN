<?php
/**
 * index.php — Front Controller
 * -----------------------------------------------------------------
 * Punto de entrada ÚNICO de la API.
 * Todo request pasa primero por aquí:
 *   1. Carga dependencias
 *   2. Valida el token JWT
 *   3. Si el token es válido → deriva al controlador (products.php)
 *   4. Si el token es inválido/ausente → responde 401 y para todo
 *
 * Arquitectura Stateless: el servidor no guarda sesión,
 * cada petición debe traer su propio token.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Responder preflight de CORS (navegadores lo envían antes del request real)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cargar configuración y autoload de Composer
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Casti\Ejemplotokens\AuthService;

// ----------------------------------------------------------------
// VALIDACIÓN DEL TOKEN — si falla, para aquí, no llega al CRUD
// ----------------------------------------------------------------
$authService = new AuthService();

try {
    $tokenDecodificado = $authService->validarToken();
} catch (\Exception $e) {
    http_response_code($e->getCode() ?: 401);
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data'    => null,
    ]));
}

// ----------------------------------------------------------------
// TOKEN VÁLIDO → enrutar según el método HTTP
// ----------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'];

// Leer body JSON si viene (para PUT)
$bodyJson = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($metodo) {
    case 'GET':
        require_once __DIR__ . '/api/products.php';
        obtenerProductos();
        break;

    case 'POST':
        require_once __DIR__ . '/api/products.php';
        crearProducto();
        break;

    case 'PUT':
        require_once __DIR__ . '/api/products.php';
        actualizarProducto($bodyJson);
        break;

    case 'DELETE':
        require_once __DIR__ . '/api/products.php';
        eliminarProducto();
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => "Método '$metodo' no permitido.",
        ]);
        break;
}