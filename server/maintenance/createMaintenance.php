<?php
// server/maintenance/createMaintenance.php

require_once '../../db/db.php';
require_once '../notification/NotificationService.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "No autorizado";
    exit();
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método no permitido";
    exit();
}

// Verificar campos requeridos
if (!isset($_POST['resource_id']) || !isset($_POST['description']) || !isset($_POST['status']) || !isset($_POST['priority'])) {
    http_response_code(400);
    echo "Faltan datos requeridos";
    exit();
}

// Obtener datos del formulario
$resource_id = $_POST['resource_id'];
$description = $_POST['description'];
$status = $_POST['status'];
$priority = $_POST['priority'];

try {
    // Conectar a la base de datos
    $db = Database::connect();
    
    // Verificar si la tabla tiene el campo user_id
    $query = "SELECT column_name FROM information_schema.columns 
              WHERE table_name = 'maintenance_requests' AND column_name = 'user_id'";
    $stmt = $db->query($query);
    $userIdExists = $stmt->rowCount() > 0;
    
    // Preparar la consulta según los campos disponibles
    if ($userIdExists) {
        $query = "INSERT INTO maintenance_requests 
                 (resource_id, description, status, priority, created_at, updated_at, user_id) 
                 VALUES (:resource_id, :description, :status, :priority, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :user_id)";
        
        $params = [
            ':resource_id' => $resource_id,
            ':description' => $description,
            ':status' => $status,
            ':priority' => $priority,
            ':user_id' => $_SESSION['user_id']
        ];
    } else {
        $query = "INSERT INTO maintenance_requests 
                 (resource_id, description, status, priority, created_at, updated_at) 
                 VALUES (:resource_id, :description, :status, :priority, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $params = [
            ':resource_id' => $resource_id,
            ':description' => $description,
            ':status' => $status,
            ':priority' => $priority
        ];
    }
    
    // Insertar la solicitud de mantenimiento
    $stmt = $db->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Obtener el ID de la solicitud creada
        $maintenanceId = $db->lastInsertId();
        
        // Obtener datos del recurso para la notificación
        $query = "SELECT name FROM resources WHERE id = :resource_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':resource_id' => $resource_id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Datos de mantenimiento para la notificación
        $maintenanceData = [
            'id' => $maintenanceId,
            'resource_id' => $resource_id,
            'resource_name' => $resource['name'],
            'description' => $description,
            'status' => $status,
            'priority' => $priority
        ];
        
        // Verificar si tenemos que enviar notificaciones a usuarios con reservas en este recurso
        $notificationService = new NotificationService();
        
        // Buscar usuarios con reservas para este recurso (si la tabla tiene campo user_id)
        $query = "SELECT column_name FROM information_schema.columns 
                 WHERE table_name = 'reservations' AND column_name = 'user_id'";
        $stmt = $db->query($query);
        $reservationHasUserId = $stmt->rowCount() > 0;
        
        if ($reservationHasUserId) {
            $query = "SELECT DISTINCT user_id 
                     FROM reservations 
                     WHERE resource_id = :resource_id 
                     AND reservation_date >= CURRENT_DATE";
            $stmt = $db->prepare($query);
            $stmt->execute([':resource_id' => $resource_id]);
            $affectedUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Enviar notificaciones a usuarios afectados
            foreach ($affectedUsers as $userId) {
                $notificationService->sendMaintenanceNotification($userId, $maintenanceData);
            }
        }
        
        // También notificar al usuario que creó la solicitud
        $notificationService->sendMaintenanceNotification($_SESSION['user_id'], $maintenanceData);
        
        // Respuesta exitosa
        http_response_code(200);
        echo "Solicitud de mantenimiento creada con éxito";
    } else {
        http_response_code(500);
        echo "Error al crear la solicitud de mantenimiento";
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error en la base de datos: " . $e->getMessage();
}
?>