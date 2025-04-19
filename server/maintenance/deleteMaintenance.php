<?php
// deleteMaintenance.php: Eliminar una solicitud de mantenimiento

require_once '../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Eliminar la solicitud de mantenimiento
    $db = Database::connect();
    $query = "DELETE FROM maintenance_requests WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);

    echo "Solicitud de mantenimiento eliminada con éxito!";
}
?>
