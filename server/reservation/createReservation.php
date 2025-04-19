<?php
// createReservation.php: Crear una nueva reserva

require_once '../db/db.php';

if (isset($_POST['resource_id'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
    $resource_id = $_POST['resource_id'];
    $responsible_person = $_POST['responsible_person'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];

    try {
        $db = Database::connect();
        $query = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time) VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':resource_id' => $resource_id,
            ':responsible_person' => $responsible_person,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time
        ]);

        echo "Reserva creada con éxito!";
    } catch (PDOException $e) {
        echo "Error al crear la reserva: " . $e->getMessage();
    }
}
?>
