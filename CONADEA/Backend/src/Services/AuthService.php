<?php
namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Repositories\RolRepository;
use App\Repositories\DepartamentoRepository;
use App\Repositories\MunicipioRepository;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Entities\Usuario;
use App\Utils\JwtUtils;

class AuthService
{
    private const TOKEN_TTL_SECONDS = 60 * 60 * 24 * 7; // 7 días

    private $usuarioRepository;
    private $rolRepository;
    private $departamentoRepository;
    private $municipioRepository;

    public function __construct()
    {
        $this->usuarioRepository = new UsuarioRepository();
        $this->rolRepository = new RolRepository();
        $this->departamentoRepository = new DepartamentoRepository();
        $this->municipioRepository = new MunicipioRepository();
    }

    public function register(RegisterDTO $dto): array
    {
        $this->validateRegister($dto);

        $rol = $this->rolRepository->findByNombre('Usuario');
        if (!$rol) {
            throw new \Exception('El rol "Usuario" no está configurado. Ejecuta Database/002_data_inicial.sql.');
        }

        $entity = new Usuario(
            null,
            $dto->nombreCompleto,
            $dto->usuario,
            password_hash($dto->password, PASSWORD_BCRYPT),
            $dto->telefono,
            $dto->departamentoId,
            $dto->municipioId,
            $rol->id,
            $rol->nombre,
            true
        );

        $id = $this->usuarioRepository->create($entity);
        $entity->id = $id;

        return $this->buildAuthResponse($entity);
    }

    public function login(LoginDTO $dto): array
    {
        if ($dto->usuario === '' || $dto->password === '') {
            throw new \Exception('Usuario y contraseña son obligatorios.');
        }

        $usuario = $this->usuarioRepository->findByUsuario($dto->usuario);

        if (!$usuario || !password_verify($dto->password, $usuario->passwordHash)) {
            throw new \Exception('Usuario o contraseña incorrectos.');
        }

        if (!$usuario->activo) {
            throw new \Exception('Esta cuenta está inactiva. Contacta al administrador.');
        }

        return $this->buildAuthResponse($usuario);
    }

    private function validateRegister(RegisterDTO $dto): void
    {
        if (mb_strlen($dto->nombreCompleto) < 3) {
            throw new \Exception('El nombre completo es obligatorio.');
        }

        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $dto->usuario)) {
            throw new \Exception('El usuario debe tener entre 3 y 50 caracteres (letras, números, "." o "_").');
        }

        if (mb_strlen($dto->password) < 6) {
            throw new \Exception('La contraseña debe tener al menos 6 caracteres.');
        }

        if (!preg_match('/^\+?[0-9]{8,15}$/', $dto->telefono)) {
            throw new \Exception('Ingresa un número de teléfono válido (con el que usarás WhatsApp).');
        }

        if (!$this->departamentoRepository->existsById($dto->departamentoId)) {
            throw new \Exception('Selecciona un departamento válido.');
        }

        if (!$this->municipioRepository->existsByIdAndDepartamento($dto->municipioId, $dto->departamentoId)) {
            throw new \Exception('Selecciona un municipio válido para el departamento elegido.');
        }

        if ($this->usuarioRepository->findByUsuario($dto->usuario)) {
            throw new \Exception('Ese usuario ya está registrado.');
        }

        if ($this->usuarioRepository->findByTelefono($dto->telefono)) {
            throw new \Exception('Ese número de teléfono ya está registrado.');
        }
    }

    private function buildAuthResponse(Usuario $usuario): array
    {
        $payload = [
            'sub' => $usuario->id,
            'usuario' => $usuario->usuario,
            'role' => $usuario->rolNombre,
            'privileges' => [],
            'exp' => time() + self::TOKEN_TTL_SECONDS,
        ];

        return [
            'token' => JwtUtils::generate($payload),
            'usuario' => [
                'id' => $usuario->id,
                'nombre_completo' => $usuario->nombreCompleto,
                'usuario' => $usuario->usuario,
                'telefono' => $usuario->telefono,
                'departamento_id' => $usuario->departamentoId,
                'municipio_id' => $usuario->municipioId,
                'rol' => $usuario->rolNombre,
            ],
        ];
    }
}
