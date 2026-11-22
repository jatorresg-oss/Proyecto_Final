<?php

namespace App\Controllers;

use App\Models\Usuario;
use InvalidArgumentException;

/**
 * Lógica de negocio de autenticación.
 *
 * Token simple (no JWT), almacenado en la tabla usuarios junto con el indicador
 * de sesión activa. Contraseñas cifradas con bcrypt mediante password_hash().
 */
class AuthController
{
    /**
     * Inicia sesión y devuelve el token junto con los datos públicos del usuario.
     *
     * @param string $identifier Usuario o correo electrónico.
     */
    public function login(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            throw new InvalidArgumentException(
                'Debe ingresar usuario o correo y contraseña.',
                400
            );
        }

        $user = Usuario::where('usuario', $identifier)
            ->orWhere('correo', $identifier)
            ->first();

        if (!$user || !password_verify($password, $user->contrasena)) {
            throw new InvalidArgumentException('Credenciales incorrectas.', 401);
        }

        if ($user->estado !== 'activo') {
            throw new InvalidArgumentException(
                'El usuario está inactivo. Contacte al administrador.',
                403
            );
        }

        // Se invalida cualquier token anterior y se marca la sesión como activa.
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->sesion_activa = true;
        $user->save();

        return [
            'token' => $token,
            'user' => self::publicUser($user),
        ];
    }

    /** Cierra la sesión invalidando el token en base de datos. */
    public function logout(int $userId): void
    {
        $user = Usuario::find($userId);

        if (!$user) {
            return;
        }

        $user->token = null;
        $user->sesion_activa = false;
        $user->save();
    }

    /**
     * Devuelve los datos públicos del usuario autenticado.
     * El middleware ya validó token, sesión activa y estado del usuario.
     */
    public function validate(Usuario $user): array
    {
        return self::publicUser($user);
    }

    /** Datos que se envían al frontend: nunca contrasena ni token. */
    public static function publicUser(Usuario $user): array
    {
        return [
            'id' => (int) $user->id,
            'nombre' => $user->nombre,
            'correo' => $user->correo,
            'usuario' => $user->usuario,
            'rol' => $user->rol,
        ];
    }
}
