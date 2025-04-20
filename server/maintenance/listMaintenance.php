<?php
// listMaintenance.php: Mostrar todas las solicitudes de mantenimiento activas

require_once '../db/db.php';

try {
    $db = Database::connect();
    $query = "SELECT m.id, r.name AS resource_name, m.description, m.status, m.priority
              FROM maintenance_requests m
              JOIN resources r ON m.resource_id = r.id";  // Consulta para obtener todas las solicitudes de mantenimiento
    $stmt = $db->query($query);
    $maintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}

// Mostrar las solicitudes de mantenimiento existentes
foreach ($maintenanceRequests as $request) {
    echo "<tr>";
    echo "<td>" . $request['resource_name'] . "</td>";
    echo "<td>" . $request['description'] . "</td>";
    echo "<td>" . $request['status'] . "</td>";
    echo "<td>" . $request['priority'] . "</td>";
    echo "<td>
            <a href='editMaintenance.php?id=" . $request['id'] . "'>Editar</a> | 
            <a href='mantenimiento.php?delete_id=" . $request['id'] . "'>Eliminar</a>
          </td>";
    echo "</tr>";
}
?>
