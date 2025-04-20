<?php
require_once '../../db/db.php';

try {
    $db = Database::connect();
    $query = "SELECT * FROM resources";
    $stmt = $db->query($query);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al obtener recursos: " . $e->getMessage();
    die();
}
?>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resources as $resource): ?>
        <tr>
            <td><?= htmlspecialchars($resource['name']) ?></td>
            <td><?= htmlspecialchars($resource['type']) ?></td>
            <td><?= htmlspecialchars($resource['status']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

