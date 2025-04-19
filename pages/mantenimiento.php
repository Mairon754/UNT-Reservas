<?php

include '../navbar.php';
// mantenimiento.php: Página para gestionar las solicitudes de mantenimiento (CRUD)

require_once '../server/maintenance/listMaintenance.php'; // Mostrar mantenimiento

// Crear una solicitud de mantenimiento
if (isset($_POST['resource_id'], $_POST['description'], $_POST['priority'])) {
    require_once '../server/maintenance/createMaintenance.php'; // Crear solicitud de mantenimiento
}

// Eliminar una solicitud de mantenimiento
if (isset($_GET['delete_id'])) {
    require_once '../server/maintenance/deleteMaintenance.php'; // Eliminar solicitud de mantenimiento
}
?>

<h2>Gestión de Mantenimiento</h2>

<!-- Formulario para crear una nueva solicitud de mantenimiento -->
<h3>Crear Solicitud de Mantenimiento</h3>
<form action="mantenimiento.php" method="POST">
    <label for="resource_id">Recurso:</label>
    <select id="resource_id" name="resource_id">
        <option value="1">Aula 101</option>
        <option value="2">Proyector EPSON</option>
        <option value="3">Laptop HP</option>
    </select>
    <br>

    <label for="description">Descripción:</label>
    <textarea id="description" name="description" required></textarea>
    <br>

    <label for="priority">Prioridad:</label>
    <select id="priority" name="priority" required>
        <option value="alta">Alta</option>
        <option value="media">Media</option>
        <option value="baja">Baja</option>
    </select>
    <br>

    <button type="submit">Solicitar Mantenimiento</button>
</form>

<!-- Mostrar solicitudes de mantenimiento -->
<h3>Solicitudes de Mantenimiento Pendientes</h3>
<?php include '../server/maintenance/listMaintenance.php'; ?>
