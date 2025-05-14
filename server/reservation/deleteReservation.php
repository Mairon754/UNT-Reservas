<?php
// deleteReservation.php - Controlador para eliminar reservas
require_once '../../db/db.php';
require_once '../../models/reserva.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$response = array(
    'success' => false,
    'message' => ''
);

// Agregar logs para depuración
error_log("deleteReservation.php ejecutado con user_id: $user_id");

// Verificar si se recibió un ID de reserva
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    error_log("ID de reserva recibido: $id");
    
    try {
        $reservaModel = new Reserva();
        
        // Obtener la reserva para verificar si existe
        $reservation = $reservaModel->getReservaById($id);
        if (!$reservation) {
            $response['message'] = 'La reserva no existe o ya fue eliminada';
            error_log("Reserva no encontrada: $id");
        } else {
            error_log("Reserva encontrada, verificando permisos. User ID en DB: " . (isset($reservation['user_id']) ? $reservation['user_id'] : 'NULL') . 
                      ", Created By: " . (isset($reservation['created_by']) ? $reservation['created_by'] : 'NULL'));
            
            // Verificar si el usuario es el creador de la reserva
            // Primero intentamos con user_id
            $isOwner = false;
            if (isset($reservation['user_id']) && $reservation['user_id'] == $user_id) {
                $isOwner = true;
            }
            // Luego intentamos con created_by si existe
            if (!$isOwner && isset($reservation['created_by']) && $reservation['created_by'] == $user_id) {
                $isOwner = true;
            }
            
            if (!$isOwner) {
                $response['message'] = 'No tienes permiso para eliminar esta reserva';
                error_log("El usuario $user_id no es el propietario de la reserva $id");
            } else {
                // Eliminar la reserva directamente con una consulta SQL
                $db = Database::connect();
                $query = "DELETE FROM reservations WHERE id = :id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([':id' => $id]);
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Reserva eliminada correctamente';
                    error_log("Reserva $id eliminada correctamente");
                } else {
                    $response['message'] = 'Error al eliminar la reserva';
                    error_log("Error al eliminar la reserva $id");
                }
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
        error_log("Error PDO en deleteReservation: " . $e->getMessage());
    } catch (Exception $e) {
        $response['message'] = 'Error general: ' . $e->getMessage();
        error_log("Error general en deleteReservation: " . $e->getMessage());
    }
} else {
    $response['message'] = 'ID de reserva no proporcionado';
    error_log("ID de reserva no proporcionado en deleteReservation.php");
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>