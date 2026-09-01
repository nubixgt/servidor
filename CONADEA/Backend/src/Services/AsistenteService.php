<?php
namespace App\Services;

use App\Repositories\AsistenteRepository;
use App\Repositories\UsuarioRepository;
use App\Repositories\HorarioRepository;
use App\Repositories\DirectorioTecnicoRepository;
use App\Entities\Usuario;

class AsistenteService
{
    // Iniciales de día válidas en `horarios_curso.dias` (ver Database/011_horarios_curso.sql).
    private const DIAS_VALIDOS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

    private const ARCHIVO_TAMANO_MAXIMO_BYTES = 15 * 1024 * 1024; // 15 MB

    private const IMAGEN_EXTENSIONES_PERMITIDAS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    // audio/ogg (codec opus) es lo que manda WhatsApp para notas de voz;
    // el resto queda por si algún día se acepta audio ya grabado.
    private const AUDIO_EXTENSIONES_PERMITIDAS = [
        'audio/ogg' => 'ogg',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/amr' => 'amr',
    ];

    private $asistenteRepository;
    private $usuarioRepository;
    private $horarioRepository;
    private $directorioTecnicoRepository;

    public function __construct()
    {
        $this->asistenteRepository = new AsistenteRepository();
        $this->usuarioRepository = new UsuarioRepository();
        $this->horarioRepository = new HorarioRepository();
        $this->directorioTecnicoRepository = new DirectorioTecnicoRepository();
    }

    /**
     * WhatsApp manda el JID siempre con código de país (ej. 502...), pero
     * los usuarios pudieron registrarse en la app con el número local de
     * 8 dígitos. Se prueba en orden: tal cual, sin "502" y con "502".
     */
    private function candidatosTelefono(string $telefonoCrudo): array
    {
        $digitos = preg_replace('/\D/', '', $telefonoCrudo) ?? '';
        $candidatos = [$digitos];

        if (strlen($digitos) > 8) {
            $candidatos[] = substr($digitos, -8);
        }
        if (strlen($digitos) === 8) {
            $candidatos[] = '502' . $digitos;
        }

        return array_unique($candidatos);
    }

    public function resolverUsuarioPorTelefono(string $telefonoCrudo): ?Usuario
    {
        foreach ($this->candidatosTelefono($telefonoCrudo) as $candidato) {
            $usuario = $this->usuarioRepository->findByTelefono($candidato);
            if ($usuario !== null) {
                return $usuario;
            }
        }

        return null;
    }

    /**
     * Para números que aún no tienen cuenta en `usuarios`: si el número está
     * en el directorio de técnicos, devuelve ['nombre' => ...] para saludarlo
     * por su nombre. null si no está en el directorio.
     */
    public function buscarTecnicoEnDirectorio(string $telefonoCrudo): ?array
    {
        foreach ($this->candidatosTelefono($telefonoCrudo) as $candidato) {
            $tecnico = $this->directorioTecnicoRepository->findByTelefono($candidato);
            if ($tecnico !== null) {
                return $tecnico;
            }
        }

        return null;
    }

    public function usuarioComoArray(Usuario $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombre_completo' => $usuario->nombreCompleto,
            'rol' => $usuario->rolNombre,
        ];
    }

    public function obtenerProgreso(Usuario $usuario): array
    {
        $cursosRaw = $this->asistenteRepository->obtenerCursosConProgreso($usuario->id);

        $totalLeccionesGeneral = 0;
        $completadasGeneral = 0;
        $aprobados = 0;
        $enProgreso = 0;
        $pendientes = 0;

        $cursos = array_map(function (array $c) use ($usuario, &$totalLeccionesGeneral, &$completadasGeneral, &$aprobados, &$enProgreso, &$pendientes) {
            $totalLeccionesGeneral += $c['total_lecciones'];
            $completadasGeneral += $c['completadas'];

            if ($c['aprobado']) {
                $estado = 'aprobado';
                $aprobados++;
            } elseif ($c['completadas'] > 0) {
                $estado = 'en_progreso';
                $enProgreso++;
            } else {
                $estado = 'pendiente';
                $pendientes++;
            }

            $porcentaje = $c['aprobado']
                ? 100
                : ($c['total_lecciones'] > 0 ? (int) round($c['completadas'] / $c['total_lecciones'] * 100) : 0);

            $proximaLeccion = $estado === 'en_progreso'
                ? $this->asistenteRepository->primeraLeccionPendiente($usuario->id, $c['id'])
                : null;

            return [
                'titulo' => $c['titulo'],
                'estado' => $estado,
                'porcentaje' => $porcentaje,
                'proxima_leccion' => $proximaLeccion,
            ];
        }, $cursosRaw);

        return [
            'cursos' => $cursos,
            'resumen' => [
                'total_cursos' => count($cursos),
                'aprobados' => $aprobados,
                'en_progreso' => $enProgreso,
                'pendientes' => $pendientes,
                'porcentaje_general' => $totalLeccionesGeneral > 0
                    ? (int) round($completadasGeneral / $totalLeccionesGeneral * 100)
                    : 0,
            ],
        ];
    }

    public function cursosDisponibles(): array
    {
        return $this->horarioRepository->cursosDisponibles();
    }

    public function horariosDeUsuario(Usuario $usuario): array
    {
        return $this->horarioRepository->obtenerPorUsuario($usuario->id);
    }

    /** @param string $dias ej. "L,M,X,V" — se valida contra DIAS_VALIDOS */
    public function guardarHorario(Usuario $usuario, int $cursoId, string $dias, string $hora, int $duracionMinutos): void
    {
        $diasNormalizados = $this->normalizarDias($dias);
        if ($diasNormalizados === '') {
            throw new \Exception('Los días deben ser una combinación de L, M, X, J, V, S, D.');
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            throw new \Exception('La hora debe tener formato HH:MM (24 horas).');
        }
        if ($duracionMinutos < 5 || $duracionMinutos > 180) {
            throw new \Exception('La duración debe estar entre 5 y 180 minutos.');
        }

        $this->horarioRepository->guardar($usuario->id, $cursoId, $diasNormalizados, $hora . ':00', $duracionMinutos);
    }

    public function posponerHorario(Usuario $usuario, int $cursoId, int $minutos): bool
    {
        return $this->horarioRepository->posponer($usuario->id, $cursoId, $minutos);
    }

    public function actualizarActivoHorario(Usuario $usuario, int $cursoId, bool $activo): bool
    {
        return $this->horarioRepository->actualizarActivo($usuario->id, $cursoId, $activo);
    }

    /** Usado por whatsapp-bot/lib/recordatorios.js, no depende de un usuario puntual. */
    public function horariosDebidos(): array
    {
        return $this->horarioRepository->obtenerDebidos();
    }

    public function marcarHorarioNotificado(Usuario $usuario, int $cursoId): void
    {
        $this->horarioRepository->marcarNotificado($usuario->id, $cursoId);
    }

    private function normalizarDias(string $dias): string
    {
        $letras = array_unique(array_filter(array_map(
            fn($d) => strtoupper(trim($d)),
            preg_split('/[,\s]+/', $dias)
        )));
        $validas = array_values(array_intersect($letras, self::DIAS_VALIDOS));
        return implode(',', $validas);
    }

    public function registrarConsultaTexto(Usuario $usuario, string $mensaje): array
    {
        if (trim($mensaje) === '') {
            throw new \Exception('El mensaje no puede estar vacío.');
        }
        $id = $this->asistenteRepository->crearConsulta($usuario->id, 'texto', null, $mensaje);
        return ['id' => $id];
    }

    public function registrarConsultaUbicacion(Usuario $usuario, float $lat, float $lng, ?string $mensaje): array
    {
        $contenido = "{$lat},{$lng}";
        $id = $this->asistenteRepository->crearConsulta($usuario->id, 'ubicacion', $contenido, $mensaje);
        return ['id' => $id];
    }

    /**
     * @param array $archivo Entrada cruda de $_FILES['archivo']
     */
    public function registrarConsultaArchivo(Usuario $usuario, string $tipo, array $archivo, ?string $mensaje): array
    {
        if (!in_array($tipo, ['imagen', 'audio'], true)) {
            throw new \Exception('Tipo de consulta inválido.');
        }
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \Exception('Ocurrió un problema al recibir el archivo.');
        }
        if ($archivo['size'] > self::ARCHIVO_TAMANO_MAXIMO_BYTES) {
            throw new \Exception('El archivo no puede pesar más de 15 MB.');
        }

        $rutaRelativa = $tipo === 'imagen'
            ? $this->guardarImagen($usuario->id, $archivo)
            : $this->guardarAudio($usuario->id, $archivo);

        $id = $this->asistenteRepository->crearConsulta($usuario->id, $tipo, $rutaRelativa, $mensaje);
        return ['id' => $id];
    }

    private function guardarImagen(int $usuarioId, array $archivo): string
    {
        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false) {
            throw new \Exception('El archivo no es una imagen válida.');
        }
        $extension = self::IMAGEN_EXTENSIONES_PERMITIDAS[$info['mime']] ?? null;
        if ($extension === null) {
            throw new \Exception('Formato de imagen no soportado.');
        }
        return $this->mover($usuarioId, $archivo, $extension);
    }

    private function guardarAudio(int $usuarioId, array $archivo): string
    {
        $mime = @mime_content_type($archivo['tmp_name']);
        // WhatsApp suele mandar "audio/ogg; codecs=opus" — se compara solo el tipo base.
        $mimeBase = trim(explode(';', $mime ?: '')[0]);
        $extension = self::AUDIO_EXTENSIONES_PERMITIDAS[$mimeBase] ?? null;
        if ($extension === null) {
            throw new \Exception('Formato de audio no soportado.');
        }
        return $this->mover($usuarioId, $archivo, $extension);
    }

    private function mover(int $usuarioId, array $archivo, string $extension): string
    {
        $directorio = __DIR__ . '/../../uploads/consultas/' . $usuarioId;
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new \Exception('No se pudo crear la carpeta de subida.');
        }

        $nombreArchivo = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $rutaAbsoluta = $directorio . '/' . $nombreArchivo;

        $subido = is_uploaded_file($archivo['tmp_name'])
            ? move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)
            : rename($archivo['tmp_name'], $rutaAbsoluta);

        if (!$subido) {
            throw new \Exception('No se pudo guardar el archivo.');
        }

        return "uploads/consultas/{$usuarioId}/{$nombreArchivo}";
    }
}
