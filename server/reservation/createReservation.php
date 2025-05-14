<?php
/**
 * Script para crear nuevas reservas con notificaciones
 */

// Requerir las dependencias necesarias
require_once '../../db/db.php';
require_once '../../models/reserva.php';
require_once '../notification/NotificationService.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirigir si no hay sesión activa
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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
            $reservaModel = new Reserva();
            
            // Crear la reserva usando el modelo actualizado
            $result = $reservaModel->createReserva($resource_id, $responsible_person, $reservation_date, $reservation_time, $user_id, $observations);
            
            if ($result) {
                // Obtener el ID de la reserva creada
                $db = Database::connect();
                $reservationId = $db->lastInsertId();
                
                // Obtener información sobre el recurso reservado
                $query = "SELECT name FROM resources WHERE id = :resource_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':resource_id' => $resource_id]);
                $resource = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Crear datos de la reserva para la notificación
                $reservationData = [
                    'id' => $reservationId,
                    'resource_id' => $resource_id,
                    'resource_name' => $resource['name'],
                    'responsible_person' => $responsible_person,
                    'reservation_date' => $reservation_date,
                    'reservation_time' => $reservation_time,
                    'end_date' => $end_date,
                    'end_time' => $end_time,
                    'observations' => $observations,
                    'user_id' => $user_id,
                    'created_by' => $user_id // Añadir created_by
                ];
                
                // Enviar notificación
                $notificationService = new NotificationService();
                $notificationService->sendReservationNotification($user_id, $reservationData);
                
                // Redireccionar a la página de reservas con mensaje de éxito
                header('Location: ../../pages/reservas.php?success=1');
                exit();
            } else {
                // Error al crear la reserva
                $error = "Error al crear la reserva o el recurso ya está reservado";
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