<?php
// maintenanceRoutes.php: Maneja las rutas para mantenimiento

require_once 'controllers/maintenanceController.php';

$maintenanceController = new MaintenanceController();

$method = $_SERVER['REQUEST_METHOD']; // Obtener el método HTTP

switch ($method) {
    case 'GET':
        // Obtener todas las solicitudes de mantenimiento
        if (isset($_GET['action']) && $_GET['action'] == 'all') {
            $maintenanceRequests = $maintenanceController->getAllMaintenanceRequests();
            echo json_encode(['maintenance' => $maintenanceRequests]);
        }
        break;

    case 'POST':
        // Crear una nueva solicitud de mantenimiento
        if (isset($_POST['resource_id']) && isset($_POST['description']) && isset($_POST['priority'])) {
            $resourceId = $_POST['resource_id'];
            $description = $_POST['description'];
            $priority = $_POST['priority'];
            $maintenanceController->createMaintenanceRequest($resourceId, $description, $priority);
            echo json_encode(['success' => true, 'message' => 'Solicitud de mantenimiento creada']);
        }
        break;

    case 'PUT':
        // Actualizar el estado de una solicitud de mantenimiento
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $maintenanceId = $_GET['id'];
            $status = $_GET['status'];
            $maintenanceController->updateMaintenanceRequestStatus($maintenanceId, $status);
            echo json_encode(['success' => true, 'message' => 'Estado de mantenimiento actualizado']);
        }
        break;

    case 'DELETE':
        // Eliminar una solicitud de mantenimiento
        if (isset($_GET['id'])) {
            $maintenanceId = $_GET['id'];
            $maintenanceController->deleteMaintenanceRequest($maintenanceId);
            echo json_encode(['success' => true, 'message' => 'Solicitud de mantenimiento eliminada']);
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
?>
