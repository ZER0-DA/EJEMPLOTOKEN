<?php
namespace Casti\Ejemplotokens;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * AuthService
 * -----------------------------------------------------------------
 * Centraliza toda la lógica de autenticación JWT.
 * Ningún otro archivo debe importar firebase/php-jwt directamente:
 * todo pasa por aquí.
 */
class AuthService
{
    /** @var string */
    private $secretKey;

    /** @var string */
    private $algorithm;

    /** @var int */
    private $expiracion;

    public function __construct()
    {
        // Carga las constantes definidas en config.php
        $this->secretKey  = JWT_SECRET_KEY;
        $this->algorithm  = JWT_ALGORITHM;
        $this->expiracion = JWT_EXPIRACION;
    }

    /**
     * Genera un token JWT para el usuario autenticado.
     *
     * @param  int    $userId   ID del usuario en la BD
     * @param  string $username Nombre de usuario
     * @return string           Token JWT firmado
     */
    public function generarToken($userId, $username)
    {
        $ahora = time();

        $payload = [
            'iss' => 'localhost',          // Emisor (issuer)
            'aud' => 'localhost',          // Audiencia (audience)
            'iat' => $ahora,               // Emitido en (issued at)
            'nbf' => $ahora,               // No válido antes de (not before)
            'exp' => $ahora + $this->expiracion, // Expiración
            'data' => [
                'id'       => $userId,
                'username' => $username,
            ],
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Valida el token JWT que viene en el header Authorization.
     * Lanza una excepción si el token es inválido o ha expirado.
     *
     * @return object Payload decodificado del token
     * @throws \Exception Si el token falta, expiró o es inválido
     */
    public function validarToken()
    {
        // El token debe venir en el header: Authorization: Bearer <token>
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader)) {
            throw new \Exception('Token no proporcionado.', 401);
        }

        // Separar "Bearer " del token real
        $partes = explode(' ', $authHeader);
        if (count($partes) !== 2 || strtolower($partes[0]) !== 'bearer') {
            throw new \Exception('Formato de token inválido. Usa: Bearer <token>', 401);
        }

        $token = $partes[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('El token ha expirado. Inicia sesión nuevamente.', 401);
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Token inválido: firma incorrecta.', 401);
        } catch (\Exception $e) {
            throw new \Exception('Token inválido: ' . $e->getMessage(), 401);
        }
    }
}