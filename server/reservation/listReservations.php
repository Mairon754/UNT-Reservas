<?php
// listReservations.php: Mostrar todas las reservas activas

require_once '../db/db.php';

try {
    $db = Database::connect();
    $query = "SELECT r.id, res.name AS resource_name, r.responsible_person, r.reservation_date, r.reservation_time
              FROM reservations r
              JOIN resources res ON r.resource_id = res.id";  // Consulta para obtener todas las reservas
    $stmt = $db->query($query);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}

// Mostrar las reservas existentes
foreach ($reservations as $reservation) {
    echo "<tr>";
    echo "<td>" . $reservation['resource_name'] . "</td>";
    echo "<td>" . $reservation['responsible_person'] . "</td>";
    echo "<td>" . $reservation['reservation_date'] . "</td>";
    echo "<td>" . $reservation['reservation_time'] . "</td>";
    echo "<td>
            <a href='editReservation.php?id=" . $reservation['id'] . "'>Editar</a> | 
            <a href='reservas.php?delete_id=" . $reservation['id'] . "'>Eliminar</a>
          </td>";
    echo "</tr>";
}
?>
