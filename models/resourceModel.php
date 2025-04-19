<?php
// resourceModel.php: Handles resource data operations

class ResourceModel {
    private static $db;

    public static function connect() {
        if (self::$db == null) {
            self::$db = new PDO('pgsql:host=localhost;dbname=untgestion', 'user', 'password');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$db;
    }

    public static function getAllResources() {
        $stmt = self::connect()->prepare("SELECT * FROM resources");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createResource($name, $type, $status) {
        $stmt = self::connect()->prepare("INSERT INTO resources (name, type, status) VALUES (:name, :type, :status)");
        return $stmt->execute([':name' => $name, ':type' => $type, ':status' => $status]);
    }

    public static function updateResourceStatus($resourceId, $status) {
        $stmt = self::connect()->prepare("UPDATE resources SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $resourceId]);
    }

    public static function deleteResource($resourceId) {
        $stmt = self::connect()->prepare("DELETE FROM resources WHERE id = :id");
        return $stmt->execute([':id' => $resourceId]);
    }
}
?>
