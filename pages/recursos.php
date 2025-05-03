<?php
include '../navbar.php';
require_once '../db/db.php';

// Eliminar recurso si se envía delete_id por GET
if (isset($_GET['delete_id'])) {
    include '../server/task/deleteResource.php';
}

// Obtener listado de recursos
try {
    $db = Database::connect();
    $query = "SELECT * FROM resources";
    $stmt = $db->query($query);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener recursos: " . $e->getMessage());
}
?>

<h2>Gestión de Recursos</h2>
<div id="responseMessage" class="content"></div>
<button id="addRowBtn" type="button">Agregar Recursos</button>

<table id="resourcesTable">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resources as $resource): ?>
        <tr>
            <td><?= htmlspecialchars($resource['name']) ?></td>
            <td><?= htmlspecialchars($resource['type']) ?></td>
            <td>
                <select name="status" required>
                    <option value="disponible">Disponible</option>
                    <option value="no disponible">No Disponible</option>
                </select>
            </td>
            <td>
                <a href="../server/task/editResource.php?id=<?= $resource['id'] ?>">Editar</a> |
                <a href="recursos.php?delete_id=<?= $resource['id'] ?>">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('addRowBtn').addEventListener('click', function () {
    var table = document.getElementById('resourcesTable').getElementsByTagName('tbody')[0];
    var newRow = table.insertRow();

    newRow.innerHTML = `
        <td><input type="text" name="name" required></td>
        <td><input type="text" name="type" required></td>
        <td><input type="text" name="status" value="disponible" required></td>
        <td>
            <button type="button" class="saveBtn">Guardar</button>
            <button type="button" class="deleteBtn">Eliminar</button>
        </td>`;

    newRow.querySelector('.deleteBtn').addEventListener('click', function () {
        table.deleteRow(newRow.rowIndex - 1);
    });

    newRow.querySelector('.saveBtn').addEventListener('click', function () {
        var name = newRow.querySelector('input[name="name"]').value;
        var type = newRow.querySelector('input[name="type"]').value;
        var status = newRow.querySelector('input[name="status"]').value;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/task/createResource.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    alert("Recurso creado con éxito");
                    location.reload();
                } else {
                    alert("Error al crear el recurso");
                }
            }
        };
        xhr.send("name=" + encodeURIComponent(name) + "&type=" + encodeURIComponent(type) + "&status=" + encodeURIComponent(status));
    });
});
</script>
<footer class="footer">
    <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
</footer>
