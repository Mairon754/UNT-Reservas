<?php
// createMaintenance.php: Solicitar mantenimiento para un recurso

require_once '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_id = $_POST['resource_id'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];

    // Insertar la solicitud de mantenimiento
    $db = Database::connect();
    $query = "INSERT INTO maintenance_requests (resource_id, description, priority) 
              VALUES (:resource_id, :description, :priority)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':resource_id' => $resource_id,
        ':description' => $description,
        ':priority' => $priority
    ]);

    echo "Solicitud de mantenimiento registrada!";
}
?>

<form action="createMaintenance.php" method="POST">
    <label for="resource_id">Recurso:</label>
    <select id="resource_id" name="resource_id">
        <!-- Agregar opciones dinámicamente desde la base de datos -->
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
