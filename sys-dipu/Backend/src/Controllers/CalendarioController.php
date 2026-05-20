<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Attributes\Route;
use App\Models\CalendarioModel;

class CalendarioController extends Controller
{
    #[Route('/calendario/eventos', 'GET')]
    public function getEvents()
    {
        $model = new CalendarioModel();
        try {
            $events = $model->getAll();
            $this->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            error_log("Error en CalendarioController::getEvents - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en el servidor al obtener los eventos'], 500);
        }
    }

    #[Route('/calendario/eventos', 'POST')]
    public function createEvent()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['title']) || empty($data['date']) || empty($data['category'])) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios (title, date, category)'], 400);
            return;
        }

        $model = new CalendarioModel();
        try {
            $insertId = $model->create($data);
            if ($insertId) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento creado exitosamente',
                    'data' => ['id' => $insertId]
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo guardar el evento'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en CalendarioController::createEvent - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en la base de datos al guardar el evento'], 500);
        }
    }

    #[Route('/calendario/eventos/{id}', 'PUT')]
    public function updateEvent($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de evento inválido'], 400);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['title']) || empty($data['date']) || empty($data['category'])) {
            $this->json(['success' => false, 'error' => 'Faltan campos obligatorios (title, date, category)'], 400);
            return;
        }

        $model = new CalendarioModel();
        try {
            $success = $model->update((int)$id, $data);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento actualizado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo actualizar el evento'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en CalendarioController::updateEvent - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error en la base de datos al actualizar el evento'], 500);
        }
    }

    #[Route('/calendario/eventos/{id}', 'DELETE')]
    public function deleteEvent($id)
    {
        if (empty($id) || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'ID de evento inválido'], 400);
            return;
        }

        $model = new CalendarioModel();
        try {
            $success = $model->delete((int)$id);
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento eliminado exitosamente'
                ]);
            } else {
                $this->json(['success' => false, 'error' => 'No se pudo eliminar el evento'], 500);
            }
        } catch (\Exception $e) {
            error_log("Error en CalendarioController::deleteEvent - " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error al intentar eliminar el evento'], 500);
        }
    }
}
