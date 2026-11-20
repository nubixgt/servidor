<?php
namespace App\Services;

use App\Core\HttpException;
use App\Repositories\BeneficiarioRepository;
use App\Repositories\SeguimientoRepository;
use App\Repositories\UsuarioRepository;
use App\Utils\Database;

/** Comentario + imagen adjunta de cada beneficiario. */
class SeguimientoService
{
    private const COMENTARIO_MAX = 2000;
    private const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private BeneficiarioRepository $beneficiarios;
    private SeguimientoRepository $seguimientos;
    private array $config;

    public function __construct()
    {
        $this->beneficiarios = new BeneficiarioRepository();
        $this->seguimientos = new SeguimientoRepository();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * @param array|null  $archivo  entrada de $_FILES['imagen'] (null si no se envió)
     * @param string|null $telefono teléfono a agregar (solo si la fila no tiene uno)
     */
    public function guardar(int $beneficiarioId, int $usuarioId, ?string $comentario, ?array $archivo, ?string $telefono = null): array
    {
        $this->exigirBeneficiario($beneficiarioId);
        $usuario = (new UsuarioRepository())->findById($usuarioId);
        if ($usuario === null || !$usuario->activo) {
            throw new HttpException('Sesión inválida', 401);
        }

        $comentario = $comentario === null ? null : trim($comentario);
        if ($comentario === '') {
            $comentario = null;
        }
        if ($comentario !== null && mb_strlen($comentario) > self::COMENTARIO_MAX) {
            throw new HttpException('El comentario no puede pasar de ' . self::COMENTARIO_MAX . ' caracteres', 422);
        }

        $telefono = $this->normalizarTelefono($telefono);
        if ($telefono !== null && $this->beneficiarios->telefono($beneficiarioId) !== null) {
            throw new HttpException('Este beneficiario ya tiene un teléfono registrado', 409);
        }

        $anterior = $this->seguimientos->porBeneficiario($beneficiarioId);
        $imagen = $this->guardarImagen($archivo);

        // Teléfono y seguimiento se guardan juntos o no se guarda nada
        $pdo = Database::getInstance()->getConnection();
        $pdo->beginTransaction();
        try {
            if ($telefono !== null && !$this->beneficiarios->agregarTelefono($beneficiarioId, $telefono, $usuarioId)) {
                throw new HttpException('Este beneficiario ya tiene un teléfono registrado', 409);
            }
            $this->seguimientos->guardar($beneficiarioId, $comentario, $imagen, $usuarioId);
            $this->seguimientos->limpiarSiVacio($beneficiarioId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            if ($imagen) {
                @unlink($this->ruta($imagen['archivo']));
            }
            throw $e;
        }

        if ($imagen && $anterior && $anterior['imagen_archivo']) {
            @unlink($this->ruta($anterior['imagen_archivo']));
        }

        return $this->respuesta($beneficiarioId);
    }

    public function quitarImagen(int $beneficiarioId, int $usuarioId): array
    {
        $this->exigirBeneficiario($beneficiarioId);
        $actual = $this->seguimientos->porBeneficiario($beneficiarioId);
        if (!$actual || !$actual['imagen_archivo']) {
            throw new HttpException('Esta fila no tiene imagen adjunta', 404);
        }

        $this->seguimientos->quitarImagen($beneficiarioId, $usuarioId);
        $this->seguimientos->limpiarSiVacio($beneficiarioId);
        @unlink($this->ruta($actual['imagen_archivo']));

        return $this->respuesta($beneficiarioId);
    }

    /** @return array{ruta: string, mime: string, bytes: int, nombre: string} */
    public function imagen(int $beneficiarioId): array
    {
        $s = $this->seguimientos->porBeneficiario($beneficiarioId);
        if (!$s || !$s['imagen_archivo']) {
            throw new HttpException('Esta fila no tiene imagen adjunta', 404);
        }
        $ruta = $this->ruta($s['imagen_archivo']);
        if (!is_file($ruta)) {
            throw new HttpException('No se encontró el archivo de la imagen', 404);
        }
        return ['ruta' => $ruta, 'mime' => $s['imagen_mime'], 'bytes' => (int) $s['imagen_bytes'], 'nombre' => $s['imagen_nombre']];
    }

    /** Lo que el frontend necesita para refrescar la fila: seguimiento y teléfono vigente. */
    private function respuesta(int $beneficiarioId): array
    {
        return ['seguimiento' => $this->detalle($beneficiarioId), 'telefono' => $this->beneficiarios->telefono($beneficiarioId)];
    }

    private function detalle(int $beneficiarioId): array
    {
        $s = $this->seguimientos->porBeneficiario($beneficiarioId);
        return [
            'comentario' => $s['comentario'] ?? null,
            'tiene_imagen' => (bool) ($s['imagen_archivo'] ?? false),
            'actualizado_en' => $s['actualizado_en'] ?? null,
            'actualizado_por' => $s['actualizado_por'] ?? null,
        ];
    }

    /** Deja solo los dígitos. null si viene vacío; 422 si no son 8 dígitos. */
    private function normalizarTelefono(?string $valor): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);
        if ($digitos === '') {
            return null;
        }
        if (strlen($digitos) !== 8) {
            throw new HttpException('El teléfono debe tener 8 dígitos', 422);
        }
        return $digitos;
    }

    private function exigirBeneficiario(int $id): void
    {
        if (!$this->beneficiarios->existe($id)) {
            throw new HttpException('Beneficiario no encontrado', 404);
        }
    }

    private function ruta(string $archivo): string
    {
        return rtrim($this->config['uploads_dir'], '/\\') . '/' . basename($archivo);
    }

    /** Valida y mueve la imagen subida. Devuelve null si no se envió ninguna. */
    private function guardarImagen(?array $f): ?array
    {
        if ($f === null || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $maxMb = $this->config['max_imagen_mb'];
        match ($f['error']) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => throw new HttpException("La imagen supera el máximo de $maxMb MB", 413),
            default => throw new HttpException('No se pudo recibir la imagen, intenta de nuevo', 400),
        };
        if (!is_uploaded_file($f['tmp_name'])) {
            throw new HttpException('Archivo inválido', 400);
        }
        if ($f['size'] > $maxMb * 1024 * 1024) {
            throw new HttpException("La imagen supera el máximo de $maxMb MB", 413);
        }

        // El tipo se decide por el contenido real, no por el nombre ni por lo que declare el navegador
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::TIPOS[$mime]) || @getimagesize($f['tmp_name']) === false) {
            throw new HttpException('Solo se aceptan imágenes JPG, PNG o WebP', 415);
        }

        $dir = rtrim($this->config['uploads_dir'], '/\\');
        $this->prepararCarpeta($dir);

        $archivo = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        if (!move_uploaded_file($f['tmp_name'], "$dir/$archivo")) {
            throw new HttpException('No se pudo guardar la imagen en el servidor', 500);
        }

        $nombre = preg_replace('/[\x00-\x1f\x7f]/u', '', basename(str_replace('\\', '/', (string) $f['name'])));
        return [
            'archivo' => $archivo,
            'nombre' => mb_substr($nombre === '' ? $archivo : $nombre, 0, 255),
            'mime' => $mime,
            'bytes' => (int) $f['size'],
        ];
    }

    /** Crea la carpeta de adjuntos con un .htaccess que impide verla por URL. */
    private function prepararCarpeta(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new HttpException('No se pudo crear la carpeta de adjuntos', 500);
        }
        $ht = dirname($dir) . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
        }
    }
}
