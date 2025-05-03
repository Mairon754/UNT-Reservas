<?php
/**
 * Script para obtener todas las reservas y devolverlas en formato JSON
 * Este archivo debe ubicarse en ../server/reservation/getReservations.php
 */

// Requerir las dependencias necesarias
require_once '../../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    // Devolver error si no hay sesión activa
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

try {
    // Conectar a la base de datos
    $db = Database::connect();
    
    // Consulta SQL para obtener todas las reservas con el nombre del recurso
    $query = "SELECT r.*, res.name as resource_name 
              FROM reservations r 
              JOIN resources res ON r.resource_id = res.id 
              ORDER BY r.reservation_date, r.reservation_time";
    
    $stmt = $db->query($query);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver las reservas en formato JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $reservations]);
    
} catch (PDOException $e) {
    // Devolver error en caso de fallo en la base de datos
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al obtener las reservas: ' . $e->getMessage()]);
}
?>