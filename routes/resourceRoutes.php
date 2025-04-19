<?php
// resourceRoutes.php: Maneja las rutas para recursos

require_once 'controllers/resourceController.php';

$resourceController = new ResourceController();

$method = $_SERVER['REQUEST_METHOD']; // Obtener el método HTTP

switch ($method) {
    case 'GET':
        // Obtener todos los recursos
        if (isset($_GET['action']) && $_GET['action'] == 'all') {
            $resources = $resourceController->getAllResources();
            echo json_encode(['resources' => $resources]);
        }
        break;

    case 'POST':
        // Crear un nuevo recurso
        if (isset($_POST['name']) && isset($_POST['type']) && isset($_POST['status'])) {
            $name = $_POST['name'];
            $type = $_POST['type'];
            $status = $_POST['status'];
            $resourceController->createResource($name, $type, $status);
            echo json_encode(['success' => true, 'message' => 'Recurso creado con éxito']);
        }
        break;

    case 'PUT':
        // Actualizar el estado de un recurso
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $resourceId = $_GET['id'];
            $status = $_GET['status'];
            $resourceController->updateResourceStatus($resourceId, $status);
            echo json_encode(['success' => true, 'message' => 'Estado del recurso actualizado']);
        }
        break;

    case 'DELETE':
        // Eliminar un recurso
        if (isset($_GET['id'])) {
            $resourceId = $_GET['id'];
            $resourceController->deleteResource($resourceId);
            echo json_encode(['success' => true, 'message' => 'Recurso eliminado']);
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
?>
