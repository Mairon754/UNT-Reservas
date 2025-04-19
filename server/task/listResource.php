<?php
// listResources.php: Mostrar los recursos disponibles

require_once '../db/db.php';

try {
    $db = Database::connect();  // Conectar a la base de datos
    $query = "SELECT * FROM resources";  // Consulta para obtener todos los recursos
    $stmt = $db->query($query);  // Ejecutar la consulta
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Obtener todos los resultados en un arreglo
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}

?>

<h2>Recursos Disponibles</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Acciones</th> <!-- Columna para acciones como editar y eliminar -->
    </tr>
    <?php
    foreach ($resources as $resource) {
        echo "<tr>";
        echo "<td>" . $resource['id'] . "</td>";
        echo "<td><input type='text' id='name-" . $resource['id'] . "' value='" . $resource['name'] . "' /></td>";
        echo "<td><input type='text' id='type-" . $resource['id'] . "' value='" . $resource['type'] . "' /></td>";
        echo "<td><input type='text' id='status-" . $resource['id'] . "' value='" . $resource['status'] . "' /></td>";
        echo "<td>
                <button onclick='editResource(" . $resource['id'] . ")'>Editar</button>
                <button onclick='deleteResource(" . $resource['id'] . ")'>Eliminar</button>
              </td>"; // Botones de editar y eliminar
        echo "</tr>";
    }
    ?>
</table>

<!-- Formulario para agregar un nuevo recurso -->
<h3>Agregar Nuevo Recurso</h3>
<form id="addResourceForm">
    <label for="newName">Nombre del Recurso:</label>
    <input type="text" id="newName" name="name" required>
    <br>

    <label for="newType">Tipo de Recurso:</label>
    <input type="text" id="newType" name="type" required>
    <br>

    <label for="newStatus">Estado:</label>
    <input type="text" id="newStatus" name="status" required>
    <br>

    <button type="submit">Crear Recurso</button>
</form>

<script>
    // Función para editar un recurso directamente en la tabla
    function editResource(id) {
        var name = document.getElementById('name-' + id).value;
        var type = document.getElementById('type-' + id).value;
        var status = document.getElementById('status-' + id).value;

        // Enviar los datos editados al servidor para actualizar el recurso
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/task/editResource.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Recurso actualizado correctamente!");
                location.reload();  // Recargar la página después de la edición
            }
        };
        xhr.send("id=" + id + "&name=" + name + "&type=" + type + "&status=" + status);
    }

    // Función para eliminar un recurso
    function deleteResource(id) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "../server/task/deleteResource.php?id=" + id, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Recurso eliminado correctamente!");
                location.reload();  // Recargar la página después de eliminar
            }
        };
        xhr.send();
    }

    // Función para agregar un nuevo recurso
    document.getElementById("addResourceForm").addEventListener("submit", function(event) {
        event.preventDefault();  // Evitar que el formulario se envíe de forma convencional

        var name = document.getElementById("newName").value;
        var type = document.getElementById("newType").value;
        var status = document.getElementById("newStatus").value;

        // Enviar los datos al servidor para crear un nuevo recurso
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/task/createResource.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Nuevo recurso creado correctamente!");
                location.reload();  // Recargar la página después de agregar el nuevo recurso
            }
        };
        xhr.send("name=" + name + "&type=" + type + "&status=" + status);
    });
</script>
