<?php
require_once '../../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = $_POST['resource_id'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];

    if (!empty($resource_id) && !empty($description)) {
        try {
            $db = Database::connect();
            $query = "INSERT INTO maintenance_requests (resource_id, description, status, priority) VALUES (:resource_id, :description, :status, :priority)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':resource_id' => $resource_id,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority
            ]);
            echo "OK";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>