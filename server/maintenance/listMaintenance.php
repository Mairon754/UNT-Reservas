<?php
require_once '../../db/db.php';

try {
    $db = Database::connect();
    $query = "SELECT m.*, r.name AS resource_name FROM maintenance_requests m JOIN resources r ON m.resource_id = r.id ORDER BY m.created_at DESC";
    $stmt = $db->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al obtener mantenimiento: " . $e->getMessage();
    die();
}
?>

<table>
    <thead>
        <tr>
            <th>Recurso</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Prioridad</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['resource_name']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['priority']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
