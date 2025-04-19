<?php
// listMaintenance.php: Listar las solicitudes de mantenimiento

require_once '../db/db.php';;

// Obtener todas las solicitudes de mantenimiento pendientes
$db = Database::connect();
$query = "SELECT m.id, res.name AS resource_name, m.description, m.status, m.priority, m.created_at
          FROM maintenance_requests m
          JOIN resources res ON m.resource_id = res.id
          WHERE m.status IN ('reportado', 'en proceso')";  // Solo mostrar mantenimientos pendientes
$stmt = $db->query($query);
$maintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Solicitudes de Mantenimiento Pendientes</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Recurso</th><th>Descripción</th><th>Estado</th><th>Prioridad</th><th>Fecha</th><th>Acciones</th></tr>";

foreach ($maintenanceRequests as $maintenance) {
    echo "<tr>";
    echo "<td>" . $maintenance['id'] . "</td>";
    echo "<td>" . $maintenance['resource_name'] . "</td>";
    echo "<td>" . $maintenance['description'] . "</td>";
    echo "<td>" . $maintenance['status'] . "</td>";
    echo "<td>" . $maintenance['priority'] . "</td>";
    echo "<td>" . $maintenance['created_at'] . "</td>";
    echo "<td><a href='editMaintenance.php?id=" . $maintenance['id'] . "'>Editar</a> | <a href='deleteMaintenance.php?id=" . $maintenance['id'] . "'>Eliminar</a></td>";
    echo "</tr>";
}

echo "</table>";
?>
