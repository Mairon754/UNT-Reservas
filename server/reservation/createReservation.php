<?php
// createReservation.php: Crear una nueva reserva

require_once '../../db/db.php';  // Asegúrate de que la ruta sea correcta

// Verificar si los datos fueron enviados
if (isset($_POST['resource_id'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
    // Obtener los datos
    $resource_id = $_POST['resource_id'];
    $responsible_person = $_POST['responsible_person'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];

    try {
        // Conectar a la base de datos
        $db = Database::connect();

        // Consulta SQL para insertar la nueva reserva
        $query = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time) 
                  VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time)";
        
        $stmt = $db->prepare($query); // Preparar la consulta SQL
        $result = $stmt->execute([
            ':resource_id' => $resource_id,
            ':responsible_person' => $responsible_person,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time
        ]);

        if ($result) {
            echo "Reserva creada con éxito";
        } else {
            http_response_code(500);
            echo "Error al guardar en la base de datos";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Error al crear la reserva: " . $e->getMessage();
    }
} else {
    http_response_code(400);
    echo "Faltan datos requeridos";
}