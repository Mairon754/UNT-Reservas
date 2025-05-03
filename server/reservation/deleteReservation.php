<?php
// deleteReservation.php - Controlador para eliminar reservas
require_once '../../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$response = array(
    'success' => false,
    'message' => ''
);

// Verificar si se recibió un ID de reserva
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        $db = Database::connect();
        
        // Eliminar la reserva
        $query = "DELETE FROM reservations WHERE id = :id";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([':id' => $id]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Reserva eliminada correctamente';
        } else {
            $response['message'] = 'Error al eliminar la reserva';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'ID de reserva no proporcionado';
}

// Devolver respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($response);
?>