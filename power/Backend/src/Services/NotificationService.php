<?php
namespace App\Services;

use App\Repositories\NotificationRepository;

class NotificationService
{
    private const MAX_TITULO = 120;
    private const MAX_CUERPO = 1000;

    private NotificationRepository $repo;

    public function __construct()
    {
        $this->repo = new NotificationRepository();
    }

    /** @return array<int,array<string,mixed>> */
    public function inboxFor(int $userId): array
    {
        return $this->repo->inboxFor($userId);
    }

    /** @return array<int,array<string,mixed>> */
    public function allSent(): array
    {
        return $this->repo->allSent();
    }

    /**
     * Manda un aviso a todos los atletas.
     *
     * Se recorta a lo que la columna aguanta en vez de rechazar el envio: un
     * administrador que acaba de escribir un texto largo prefiere que salga
     * recortado a perderlo por un error de validacion. El limite del formulario
     * ya se lo dice antes de llegar aqui.
     *
     * @param array<string,mixed> $body
     * @return array{ok:bool, message?:string, notification?:array<string,mixed>}
     */
    public function send(int $authorId, array $body): array
    {
        $titulo = trim((string) ($body['title'] ?? ''));
        $cuerpo = trim((string) ($body['body'] ?? ''));

        if ($titulo === '') {
            return ['ok' => false, 'message' => 'El aviso necesita un título.'];
        }
        if ($cuerpo === '') {
            return ['ok' => false, 'message' => 'El aviso necesita un mensaje.'];
        }

        return [
            'ok' => true,
            'notification' => $this->repo->create(
                $authorId,
                mb_substr($titulo, 0, self::MAX_TITULO),
                mb_substr($cuerpo, 0, self::MAX_CUERPO)
            ),
        ];
    }

    /** Lo retira para todos. Solo el administrador. */
    public function deleteForEveryone(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * Lo quita de la bandeja de este atleta y de nadie mas.
     *
     * No comprueba que el aviso exista: descartar algo que el administrador
     * acaba de retirar no es un error para el atleta -- queria dejar de verlo,
     * y ya no lo ve.
     */
    public function dismissFor(int $userId, int $id): void
    {
        $this->repo->dismiss($userId, $id);
    }
}
