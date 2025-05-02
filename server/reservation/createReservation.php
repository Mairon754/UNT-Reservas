<?php
// server/reservation/createReservation.php
require_once '../../db/db.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Verificar si los datos fueron enviados
if (isset($_POST['resource_id'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
    // Obtener los datos
    $resource_id = $_POST['resource_id'];
    $responsible_person = $_POST['responsible_person'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $end_date = $_POST['end_date'] ?? $reservation_date;
    $end_time = $_POST['end_time'] ?? $reservation_time;
    $observations = $_POST['observations'] ?? '';

    try {
        // Conectar a la base de datos
        $db = Database::connect();
        
        // Verificar si el recurso ya está reservado en esa fecha y hora
        $checkQuery = "SELECT COUNT(*) FROM reservations 
                      WHERE resource_id = :resource_id 
                      AND reservation_date = :reservation_date 
                      AND reservation_time = :reservation_time";
        
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([
            ':resource_id' => $resource_id,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time
        ]);
        
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            // El recurso ya está reservado en esta fecha y hora
            $errorMsg = "El recurso ya está reservado en la fecha y hora seleccionada.";
            header('Location: ../../pages/reservas.php?error=' . urlencode($errorMsg));
            exit();
        }
        
        // Si no hay conflicto, insertar la nueva reserva
        $insertQuery = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time, observations) 
                       VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time, :observations)";
        
        $insertStmt = $db->prepare($insertQuery);
        $result = $insertStmt->execute([
            ':resource_id' => $resource_id,
            ':responsible_person' => $responsible_person,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time,
            ':observations' => $observations
        ]);
        
        if ($result) {
            // Redirigir a la página de reservas con un mensaje de éxito
            header('Location: ../../pages/reservas.php?success=1');
            exit();
        } else {
            // Error al guardar la reserva
            $errorMsg = "Error al guardar la reserva. Por favor, inténtelo de nuevo.";
            header('Location: ../../pages/reservas.php?error=' . urlencode($errorMsg));
            exit();
        }
    } catch (PDOException $e) {
        // Error de base de datos
        $errorMsg = "Error en la base de datos: " . $e->getMessage();
        header('Location: ../../pages/reservas.php?error=' . urlencode($errorMsg));
        exit();
    }
} else {
    // Datos incompletos
    $errorMsg = "Faltan datos requeridos para la reserva.";
    header('Location: ../../pages/reservas.php?error=' . urlencode($errorMsg));
    exit();
}