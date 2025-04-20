<?php
require_once '../../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    if (!empty($name) && !empty($type) && !empty($status)) {
        try {
            $db = Database::connect();
            $query = "INSERT INTO resources (name, type, status) VALUES (:name, :type, :status)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':status' => $status
            ]);
            echo "OK";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
