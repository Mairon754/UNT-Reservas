<?php
// deleteReservation.php: Eliminar una reserva

require_once __DIR__ . '/../../db/db.php';


if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    try {
        // Conectar a la base de datos
        $db = Database::connect();

        // Consulta SQL para eliminar la reserva
        $query = "DELETE FROM reservations WHERE id = :id";
        
        $stmt = $db->prepare($query); // Preparar la consulta SQL
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            // Redirigir a la página de reservas para actualizar la lista
            header("Location: /project-UNT-reservas/pages/reservas.php?msg=deleted");
        } else {
            // Mensaje de error si no se pudo eliminar
            header("Location: ../pages/reservas.php?error=delete_failed");
        }
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/reservas.php?error=" . urlencode("Error al eliminar: " . $e->getMessage()));
        exit();
    }
}
?>