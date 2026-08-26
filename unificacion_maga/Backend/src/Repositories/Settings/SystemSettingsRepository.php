<?php
namespace App\Repositories\Settings;

use App\Utils\Database;
use PDO;

class SystemSettingsRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT `key`, `value`, `description` FROM maga_settings");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Table might not exist yet (migration pending)
            return [];
        }
    }

    public function getByKey($key)
    {
        $stmt = $this->db->prepare("SELECT `value` FROM maga_settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    }

    public function update($key, $value)
    {
        $stmt = $this->db->prepare("UPDATE maga_settings SET `value` = ? WHERE `key` = ?");
        return $stmt->execute([$value, $key]);
    }

    public function updateMultiple(array $settings)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE maga_settings SET `value` = ? WHERE `key` = ?");
            foreach ($settings as $key => $value) {
                $stmt->execute([$value, $key]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
