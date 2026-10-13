<?php
namespace Models;

use Core\Database;

class UsuarioModel {
    public static function findByUsuario($usuario) {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, e.nombre AS equipo_nombre FROM usuarios u LEFT JOIN equipos e ON e.id = u.equipo_id WHERE u.usuario = ?'
        );
        $stmt->execute([$usuario]);
        return $stmt->fetch() ?: null;
    }

    public static function tocarAcceso($id) {
        Database::connection()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$id]);
    }

    public static function existe($usuario, $exceptoId = 0) {
        $stmt = Database::connection()->prepare('SELECT 1 FROM usuarios WHERE usuario = ? AND id <> ?');
        $stmt->execute([$usuario, $exceptoId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function crearEncargado($usuario, $passwordHash, $equipoId) {
        Database::connection()->prepare("INSERT INTO usuarios (usuario, password_hash, rol, equipo_id) VALUES (?, ?, 'encargado', ?)")
            ->execute([$usuario, $passwordHash, $equipoId]);
    }

    public static function usuarioDeEquipo($equipoId) {
        $stmt = Database::connection()->prepare("SELECT usuario FROM usuarios WHERE equipo_id = ? AND rol = 'encargado' LIMIT 1");
        $stmt->execute([$equipoId]);
        return $stmt->fetchColumn() ?: null;
    }
}
