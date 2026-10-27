<?php
namespace App\Utils;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";

        // Las fechas del sistema se guardan en hora de Guatemala (UTC-6, sin horario de verano)
        $options = $config['options'] + [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '-06:00'",
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // In a real API, log this.
            error_log($e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    /** Filas de una consulta. */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** Primera fila o null. */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Primera columna de la primera fila o null. */
    public function scalar(string $sql, array $params = [])
    {
        $row = $this->run($sql, $params)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : $row[0];
    }

    /** Ejecuta INSERT/UPDATE/DELETE y devuelve las filas afectadas. */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /** Ejecuta un INSERT y devuelve el id generado. */
    public function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach (array_values($params) as $i => $value) {
            $type = match (true) {
                $value === null => PDO::PARAM_NULL,
                is_bool($value) => PDO::PARAM_INT,
                is_int($value) => PDO::PARAM_INT,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($i + 1, is_bool($value) ? (int) $value : $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public function transaction(callable $fn)
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Contexto que leen los triggers de auditoría (quién y desde dónde hace el cambio). */
    public function setAuditContext(?int $usuarioId, ?string $usuario, ?string $ip, ?string $requestId): void
    {
        $this->run('SET @pam_usuario_id = ?, @pam_usuario = ?, @pam_ip = ?, @pam_request_id = ?', [$usuarioId, $usuario, $ip, $requestId]);
    }
}
