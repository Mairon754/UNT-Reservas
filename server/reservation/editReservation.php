<?php
// editReservation.php: Actualizar una reserva

require_once '../db/db.php';

if (isset($_POST['id'], $_POST['resource_name'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
    $id = $_POST['id'];
    $resource_name = $_POST['resource_name'];
    $responsible_person = $_POST['responsible_person'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];

    try {
        $db = Database::connect();
        $query = "UPDATE reservations SET resource_name = :resource_name, responsible_person = :responsible_person, reservation_date = :reservation_date, reservation_time = :reservation_time WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':resource_name' => $resource_name,
            ':responsible_person' => $responsible_person,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time,
            ':id' => $id
        ]);

        echo "Reserva actualizada con éxito!";
    } catch (PDOException $e) {
        echo "Error al actualizar la reserva: " . $e->getMessage();
    }
}
?>
