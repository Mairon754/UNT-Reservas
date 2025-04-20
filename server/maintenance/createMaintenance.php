<?php
require_once '../../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $db = Database::connect();
        $query = "DELETE FROM maintenance_requests WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        header("Location: ../../pages/mantenimiento.php");
    } catch (PDOException $e) {
        echo "Error al eliminar: " . $e->getMessage();
    }
}
?>
