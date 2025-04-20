<?php
// deleteMaintenance.php: Eliminar una solicitud de mantenimiento

require_once __DIR__ . '/../../db/db.php';


if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    try {
        // Conectar a la base de datos
        $db = Database::connect();

        // Consulta SQL para eliminar la solicitud
        $query = "DELETE FROM maintenance_requests WHERE id = :id";
        
        $stmt = $db->prepare($query); // Preparar la consulta SQL
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            // Redirigir a la página de mantenimiento para actualizar la lista
            header("Location: /project-UNT-reservas/pages/mantenimiento.php?msg=deleted");
        } else {
            // Mensaje de error si no se pudo eliminar
            header("Location: ../../pages/mantenimiento.php?error=delete_failed");
        }
        exit();
    } catch (PDOException $e) {
        header("Location: ../../pages/mantenimiento.php?error=" . urlencode("Error al eliminar: " . $e->getMessage()));
        exit();
    }
}
?>
