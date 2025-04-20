<?php
include '../navbar.php';
require_once '../db/db.php';

// Eliminar solicitud si se envía delete_id por GET
if (isset($_GET['delete_id'])) {
    include '../server/maintenance/deleteMaintenance.php';
}

// Obtener listado de recursos para asignar en formulario
try {
    $db = Database::connect();
    $query = "SELECT id, name FROM resources";
    $stmt = $db->query($query);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener recursos: " . $e->getMessage());
}

// Obtener solicitudes de mantenimiento existentes
try {
    $query = "SELECT m.*, r.name AS resource_name FROM maintenance_requests m JOIN resources r ON m.resource_id = r.id ORDER BY m.priority DESC";
    $stmt = $db->query($query);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener mantenimientos: " . $e->getMessage());
}
?>

<h2>Solicitudes de Mantenimiento</h2>
<div id="responseMessage"></div>
<button id="addRowBtn" type="button">Agregar Fila</button>

<table id="maintenanceTable">
    <thead>
        <tr>
            <th>Recurso</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Prioridad</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $req): ?>
        <tr>
            <td><?= htmlspecialchars($req['resource_name']) ?></td>
            <td><?= htmlspecialchars($req['description']) ?></td>
            <td><?= htmlspecialchars($req['status']) ?></td>
            <td><?= htmlspecialchars($req['priority']) ?></td>
            <td>
                <a href="../server/maintenance/editMaintenance.php?id=<?= $req['id'] ?>">Editar</a> |
                <a href="mantenimiento.php?delete_id=<?= $req['id'] ?>">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('addRowBtn').addEventListener('click', function () {
    var table = document.getElementById('maintenanceTable').getElementsByTagName('tbody')[0];
    var newRow = table.insertRow();

    newRow.innerHTML = `
        <td>
            <select name="resource_id" required>
                <?php foreach ($resources as $res): ?>
                    <option value="<?= $res['id'] ?>"><?= $res['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="description" required></td>
        <td><input type="text" name="status" value="reportado" required></td>
        <td><select name="priority">
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
        </select></td>
        <td>
            <button type="button" class="saveBtn">Guardar</button>
            <button type="button" class="deleteBtn">Eliminar</button>
        </td>`;

    newRow.querySelector('.deleteBtn').addEventListener('click', function () {
        table.deleteRow(newRow.rowIndex - 1);
    });

    newRow.querySelector('.saveBtn').addEventListener('click', function () {
        var resource_id = newRow.querySelector('select[name="resource_id"]').value;
        var description = newRow.querySelector('input[name="description"]').value;
        var status = newRow.querySelector('input[name="status"]').value;
        var priority = newRow.querySelector('select[name="priority"]').value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/maintenance/createMaintenance.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Mantenimiento creado con éxito");
                location.reload();
            }
        };
        xhr.send("resource_id=" + resource_id + "&description=" + encodeURIComponent(description) + "&status=" + status + "&priority=" + priority);
    });
});
</script>