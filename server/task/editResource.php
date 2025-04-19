<?php
// editResource.php: Actualizar un recurso

require_once '../db/db.php';

if (isset($_POST['id'], $_POST['name'], $_POST['type'], $_POST['status'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    try {
        $db = Database::connect();
        $query = "UPDATE resources SET name = :name, type = :type, status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':name' => $name, ':type' => $type, ':status' => $status, ':id' => $id]);

        echo "Recurso actualizado con éxito!";
    } catch (PDOException $e) {
        echo "Error al actualizar el recurso: " . $e->getMessage();
    }
}
?>
