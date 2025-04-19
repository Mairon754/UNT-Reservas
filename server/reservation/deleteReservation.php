<?php
// deleteReservation.php: Eliminar una reserva

require_once '../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $db = Database::connect();
        $query = "DELETE FROM reservations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);

        echo "Reserva eliminada con éxito!";
    } catch (PDOException $e) {
        echo "Error al eliminar la reserva: " . $e->getMessage();
    }
}
?>
