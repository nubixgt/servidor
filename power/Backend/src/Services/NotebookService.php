<?php
namespace App\Services;

use App\Repositories\NotebookRepository;
use App\DTOs\NotebookEntryDTO;
use App\Support\NotebookCategories;

class NotebookService
{
    private NotebookRepository $repo;

    public function __construct()
    {
        $this->repo = new NotebookRepository();
    }

    /** The whole notebook (retired included) + category labels + counts. */
    public function listForUser(int $userId): array
    {
        $entries = array_map(fn($e) => $e->toArray(), $this->repo->findAllByUser($userId));

        $counts = ['vigentes' => 0, 'propuestas' => 0, 'retiradas' => 0];
        foreach ($entries as $e) {
            if ($e['status'] === 'vigente') {
                $counts['vigentes']++;
            } elseif ($e['status'] === 'propuesta') {
                $counts['propuestas']++;
            } elseif ($e['status'] === 'retirada') {
                $counts['retiradas']++;
            }
        }

        return [
            'entries' => $entries,
            'categories' => NotebookCategories::MAP,
            'counts' => $counts,
        ];
    }

    /**
     * Persist notes the AI coach extracted from a chat turn. Each entry is
     * PaginaLocal-shaped ({text, category, quote, ...}). Returns how many were stored.
     */
    public function importChatNotes(int $userId, array $entries): int
    {
        return count($this->importarNotasDelChat($userId, $entries));
    }

    /**
     * Las mismas, devolviendo cuáles (Beta 3.0, F9): el chat las enseña con «Quitar», porque
     * lo que el atleta pidió anotar se anota sin pedirle un clic (regla del cuaderno), pero
     * tiene que poder deshacerlo ahí mismo.
     *
     * @return list<array{id:int,text:string,category:string}>
     */
    public function importarNotasDelChat(int $userId, array $entries): array
    {
        $creadas = [];
        foreach ($entries as $e) {
            if (!is_array($e)) {
                continue;
            }
            $text = trim((string) ($e['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $categoria = NotebookCategories::normalize((string) ($e['category'] ?? 'otro'));
            $id = $this->repo->createFromChat($userId, $text, $categoria, trim((string) ($e['quote'] ?? '')));
            $creadas[] = ['id' => $id, 'text' => $text, 'category' => $categoria];
        }
        return $creadas;
    }

    /**
     * «Reordenar cuaderno» (Beta 3.0, F9): la PROPUESTA del modelo, ya comprobada en el
     * cerebro. No guarda nada; cada cambio lo acepta el atleta con `aplicarReorden`.
     *
     * @return array{ok:bool,message?:string,propuesta?:array}
     */
    public function proponerReorden(int $userId, array $respuestas = []): array
    {
        $vigentes = array_values(array_filter(
            array_map(fn($e) => $e->toArray(), $this->repo->findAllByUser($userId)),
            fn($e) => $e['status'] === 'vigente'
        ));
        if (count($vigentes) < 2) {
            return ['ok' => true, 'propuesta' => ['preguntas' => [], 'acciones' => [], 'avisos' => [],
                'aviso' => 'Tu cuaderno tiene muy pocas anotaciones para revisarlo.']];
        }
        $res = (new PythonBridgeService())->notebookReorganize([
            'user_id' => $userId,
            'profile' => (new ProfileService())->getForUser($userId),
            'entries' => $vigentes,
            // Lo que respondió a las preguntas del coach (la segunda vuelta). Lo limpia el cerebro.
            'answers' => array_values(array_slice(array_filter($respuestas, 'is_array'), 0, 4)),
        ]);
        if (!$res['ok']) {
            return ['ok' => false, 'message' => $res['data']['message'] ?? 'No se pudo ordenar el cuaderno ahora.'];
        }
        return ['ok' => true, 'propuesta' => $res['data']['data'] ?? ['acciones' => []]];
    }

    /**
     * Aplica UNA acción aceptada. Nada se borra: las originales se retiran con su motivo y,
     * si las sustituye una nueva, `replaced_by` dice cuál. «Pasar al perfil» NO escribe el
     * perfil: la pantalla dice dónde va y el atleta lo copia; aceptar sólo la retira.
     *
     * @return array{ok:bool,message?:string,nueva?:int}
     */
    public function aplicarReorden(int $userId, array $accion): array
    {
        $tipo = (string) ($accion['accion'] ?? '');
        $ids = array_values(array_unique(array_map('intval', (array) ($accion['ids'] ?? []))));
        if (!in_array($tipo, ['reescribir', 'fusionar', 'al_perfil', 'retirar'], true) || !$ids) {
            return ['ok' => false, 'message' => 'No entiendo ese cambio.'];
        }
        $esPreferencia = false;
        foreach ($ids as $id) {
            $e = $this->repo->findByIdForUser($userId, $id);
            if (!$e || $e->status !== 'vigente') {
                return ['ok' => false, 'message' => 'Una de esas anotaciones ya no está en uso. Vuelve a reordenar.'];
            }
            $esPreferencia = $esPreferencia || $e->category === 'preferencia';
        }
        $motivo = mb_substr(trim((string) ($accion['motivo'] ?? '')), 0, 180);
        $nueva = null;
        if (in_array($tipo, ['reescribir', 'fusionar'], true)) {
            $texto = trim((string) ($accion['texto'] ?? ''));
            if ($texto === '') {
                return ['ok' => false, 'message' => 'Falta el texto nuevo.'];
            }
            // Una preferencia alimenta su plan: sigue siéndolo aunque la pantalla diga otra cosa.
            $categoria = $esPreferencia ? 'preferencia' : NotebookCategories::normalize((string) ($accion['categoria'] ?? 'otro'));
            $nueva = $this->repo->createReorden($userId, mb_substr($texto, 0, 2000), $categoria);
        }
        $razon = [
            'reescribir' => 'Reescrita al reordenar',
            'fusionar' => 'Fusionada al reordenar',
            'al_perfil' => 'Pasada al perfil' . (!empty($accion['donde']) ? ' (' . $accion['donde'] . ')' : ''),
            'retirar' => 'Ya no aplica',
        ][$tipo] . ($motivo !== '' ? ': ' . $motivo : '');
        foreach ($ids as $id) {
            $this->repo->retireFor($userId, $id, $razon, $nueva);
        }
        return ['ok' => true, 'nueva' => $nueva];
    }

    /** @return array{ok:bool,message?:string,entry?:array} */
    public function addForUser(int $userId, NotebookEntryDTO $dto): array
    {
        $text = $dto->hasText() ? $dto->text() : '';
        if ($text === null || $text === '') {
            return ['ok' => false, 'message' => 'Una anotación vacía no dice nada.'];
        }

        $category = $dto->hasCategory() ? $dto->category() : 'otro';
        $status = $dto->hasStatus() ? $dto->status() : 'vigente';

        $entry = $this->repo->create($userId, $text, $category, $status);
        return ['ok' => true, 'entry' => $entry->toArray()];
    }

    /** @return array{ok:bool,code?:int,message?:string,entry?:array} */
    public function patchForUser(int $userId, int $id, NotebookEntryDTO $dto): array
    {
        if ($dto->isEmptyPatch()) {
            return ['ok' => false, 'code' => 400, 'message' => 'No hay nada que cambiar.'];
        }

        $existing = $this->repo->findByIdForUser($userId, $id);
        if (!$existing) {
            return ['ok' => false, 'code' => 404, 'message' => 'Esa anotación no existe.'];
        }

        $fields = [];

        if ($dto->hasText()) {
            if ($dto->text() === '') {
                return ['ok' => false, 'code' => 400, 'message' => 'Una anotación vacía no dice nada.'];
            }
            $fields['text'] = $dto->text();
        }

        if ($dto->hasCategory()) {
            $fields['category'] = $dto->category();
        }

        if ($dto->hasStatus()) {
            $fields['status'] = $dto->status();
            if ($dto->status() === 'retirada') {
                $fields['retired_at'] = date('Y-m-d H:i:s');
                $fields['retired_reason'] = $dto->hasRetiredReason() ? $dto->retiredReason() : '';
            } else {
                $fields['retired_at'] = null;
                $fields['retired_reason'] = '';
            }
        } elseif ($dto->hasRetiredReason() && $existing->status === 'retirada') {
            $fields['retired_reason'] = $dto->retiredReason();
        }

        $entry = $this->repo->update($userId, $id, $fields);
        return ['ok' => true, 'entry' => $entry->toArray()];
    }

    /**
     * «Borrar» RETIRA (decisión del responsable, 2026-10-11): la anotación deja de llegar al
     * plan y al chat, y se conserva con su motivo. Sólo una que ya está retirada se borra de
     * verdad, y sólo si el atleta lo pide expresamente (`$paraSiempre`): sus datos son suyos.
     *
     * @return 'retirada'|'borrada'|null  null si no existe o no es suya
     */
    public function deleteForUser(int $userId, int $id, bool $paraSiempre = false): ?string
    {
        $e = $this->repo->findByIdForUser($userId, $id);
        if (!$e) {
            return null;
        }
        if ($e->status === 'retirada') {
            if (!$paraSiempre) {
                return 'retirada';
            }
            return $this->repo->delete($userId, $id) ? 'borrada' : null;
        }
        $this->repo->retireFor($userId, $id, $e->status === 'propuesta' ? 'Descartada por ti' : 'Borrada por ti');
        return 'retirada';
    }
}
