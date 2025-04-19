<?php
include '../navbar.php';
// recursos.php: Página para gestionar los recursos (CRUD)

require_once '../server/task/listResource.php'; // Mostrar recursos

// Agregar un recurso
if (isset($_POST['name'], $_POST['type'], $_POST['status'])) {
    require_once '../server/task/createResource.php'; // Crear recurso
}

// Eliminar un recurso (si se pasa un ID por GET)
if (isset($_GET['delete_id'])) {
    require_once '../server/task/deleteResource.php'; // Eliminar recurso
}
?>

<!-- Mostrar recursos -->
<h3>Recursos Disponibles</h3>
<?php include '../server/task/editResource.php'; ?>
