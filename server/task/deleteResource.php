<?php
// deleteResource.php: Eliminar un recurso

require_once '../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $db = Database::connect();
        $query = "DELETE FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);

        echo "Recurso eliminado con éxito!";
    } catch (PDOException $e) {
        echo "Error al eliminar el recurso: " . $e->getMessage();
    }
}
?>
