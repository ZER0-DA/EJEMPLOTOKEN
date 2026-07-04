<?php
/**
 * login.php
 * -----------------------------------------------------------------
 * Punto de autenticación de la API.
 * - Primera vez: crea el usuario admin con password_hash() (BCRYPT).
 * - Siguientes veces: valida credenciales con password_verify()
 *   y emite un token JWT si son correctas.
 *
 * NUNCA se guarda la contraseña en texto plano en la BD.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Cargar config y autoload de Composer
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Casti\Ejemplotokens\AuthService;

// Conexión a la base de datos (PDO simple, sin la clase DB del otro lab)
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
    ]));
}

// ----------------------------------------------------------------
// CREAR USUARIO ADMIN (solo si no existe todavía)
// Esto se ejecuta una única vez para registrar el admin con BCRYPT.
// Después de crearlo puedes comentar este bloque si quieres.
// ----------------------------------------------------------------
$stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE username = :u');
$stmt->execute(['u' => 'admin']);
$existe = (int) $stmt->fetchColumn();

if (!$existe) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $ins  = $pdo->prepare('INSERT INTO usuarios (username, password) VALUES (:u, :p)');
    $ins->execute(['u' => 'admin', 'p' => $hash]);
}
// ----------------------------------------------------------------

// Solo aceptamos POST para el login real
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'success' => false,
        'message' => 'Método no permitido. Usa POST.',
    ]));
}

// Leer credenciales (acepta JSON o form-data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => 'Usuario y contraseña son obligatorios.',
    ]));
}

// Buscar usuario en la BD
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE username = :u LIMIT 1');
$stmt->execute(['u' => $username]);
$usuario = $stmt->fetch();

// Verificar contraseña con password_verify() (compara texto plano vs hash BCRYPT)
if (!$usuario || !password_verify($password, $usuario['password'])) {
    http_response_code(401);
    die(json_encode([
        'success' => false,
        'message' => 'Credenciales incorrectas.',
    ]));
}

// Credenciales correctas → generar y devolver el token JWT
$authService = new AuthService();
$token = $authService->generarToken($usuario['id'], $usuario['username']);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login exitoso.',
    'token'   => $token,
]);