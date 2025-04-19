<?php
// maintenanceModel.php: Handles maintenance request operations

class MaintenanceModel {
    private static $db;

    public static function connect() {
        if (self::$db == null) {
            self::$db = new PDO('pgsql:host=localhost;dbname=untgestion', 'user', 'password');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$db;
    }

    public static function getAllRequests() {
        $stmt = self::connect()->prepare("SELECT * FROM maintenance_requests");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createRequest($resourceId, $description, $priority) {
        $stmt = self::connect()->prepare("INSERT INTO maintenance_requests (resource_id, description, priority) VALUES (:resource_id, :description, :priority)");
        return $stmt->execute([':resource_id' => $resourceId, ':description' => $description, ':priority' => $priority]);
    }

    public static function updateStatus($maintenanceId, $status) {
        $stmt = self::connect()->prepare("UPDATE maintenance_requests SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $maintenanceId]);
    }

    public static function deleteRequest($maintenanceId) {
        $stmt = self::connect()->prepare("DELETE FROM maintenance_requests WHERE id = :id");
        return $stmt->execute([':id' => $maintenanceId]);
    }
}
?>
