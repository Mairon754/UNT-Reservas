<?php
/**
 * Script para crear nuevas reservas - Versión PostgreSQL
 * Este archivo debe ubicarse en ../server/reservation/createReservation.php
 */

// Requerir las dependencias necesarias
require_once '../../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirigir si no hay sesión activa
    header('Location: ../../login.php');
    exit();
}

// Si se reciben datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que todos los campos requeridos están presentes
    if (isset($_POST['resource_id'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
        
        // Obtener los datos del formulario
        $resource_id = $_POST['resource_id'];
        $responsible_person = $_POST['responsible_person'];
        $reservation_date = $_POST['reservation_date'];
        $reservation_time = $_POST['reservation_time'];
        
        // Campos opcionales - verificar y establecer valores predeterminados si es necesario
        $end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : $reservation_date;
        $end_time = isset($_POST['end_time']) && !empty($_POST['end_time']) ? $_POST['end_time'] : $reservation_time;
        $observations = isset($_POST['observations']) ? $_POST['observations'] : '';
        
        try {
            // Conectar a la base de datos
            $db = Database::connect();
            
            // Verificar si los campos end_date y end_time existen en la tabla (PostgreSQL)
            $query = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'reservations' AND column_name = 'end_date'";
            $stmt = $db->query($query);
            $endDateExists = $stmt->rowCount() > 0;
            
            $query = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'reservations' AND column_name = 'end_time'";
            $stmt = $db->query($query);
            $endTimeExists = $stmt->rowCount() > 0;
            
            // Si los campos no existen, alteramos la tabla para crearlos (PostgreSQL)
            if (!$endDateExists) {
                $db->exec("ALTER TABLE reservations ADD COLUMN end_date DATE");
            }
            
            if (!$endTimeExists) {
                $db->exec("ALTER TABLE reservations ADD COLUMN end_time TIME");
            }
            
            // Verificar si hay conflictos con otras reservas
            $query = "SELECT COUNT(*) FROM reservations 
                      WHERE resource_id = :resource_id 
                      AND reservation_date = :reservation_date
                      AND ((reservation_time <= :end_time AND 
                           (end_time >= :reservation_time OR end_time IS NULL)))";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':resource_id' => $resource_id,
                ':reservation_date' => $reservation_date,
                ':reservation_time' => $reservation_time,
                ':end_time' => $end_time
            ]);
            
            $conflictCount = $stmt->fetchColumn();
            
            if ($conflictCount > 0) {
                // Hay conflicto con otra reserva
                $error = "El recurso ya está reservado para esa fecha y hora";
                header("Location: ../../pages/reservas.php?error=" . urlencode($error));
                exit();
            }
            
            // Si no hay conflictos, crear la reserva
            // Verificamos primero si tenemos que incluir las columnas nuevas
            if ($endDateExists && $endTimeExists) {
                $query = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time, end_date, end_time, observations, created_at) 
                         VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time, :end_date, :end_time, :observations, NOW())";
                
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':resource_id' => $resource_id,
                    ':responsible_person' => $responsible_person,
                    ':reservation_date' => $reservation_date,
                    ':reservation_time' => $reservation_time,
                    ':end_date' => $end_date,
                    ':end_time' => $end_time,
                    ':observations' => $observations
                ]);
            } else {
                // Fallback a la versión sin end_date/end_time si por alguna razón no se pudieron crear
                $query = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time, observations, created_at) 
                         VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time, :observations, NOW())";
                
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':resource_id' => $resource_id,
                    ':responsible_person' => $responsible_person,
                    ':reservation_date' => $reservation_date,
                    ':reservation_time' => $reservation_time,
                    ':observations' => $observations
                ]);
            }
            
            if ($result) {
                // Redireccionar a la página de reservas con mensaje de éxito
                header('Location: ../../pages/reservas.php?success=1');
                exit();
            } else {
                // Error al crear la reserva
                $error = "Error al crear la reserva";
                header("Location: ../../pages/reservas.php?error=" . urlencode($error));
                exit();
            }
            
        } catch (PDOException $e) {
            // Error de base de datos
            $error = "Error en la base de datos: " . $e->getMessage();
            header("Location: ../../pages/reservas.php?error=" . urlencode($error));
            exit();
        }
    } else {
        // Si faltan campos requeridos
        $error = "Todos los campos marcados son obligatorios";
        header("Location: ../../pages/reservas.php?error=" . urlencode($error));
        exit();
    }
} else {
    // Si se accede directamente sin enviar el formulario
    header('Location: ../../pages/reservas.php');
    exit();
}
?>