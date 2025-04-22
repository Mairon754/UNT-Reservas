<?php
// Ajustar las rutas relativas
include '../navbar.php';  // Subir un directorio para acceder a navbar.php
require_once '../db/db.php';  // Subir un directorio para acceder a db.php

// Lógica de eliminación de una solicitud de mantenimiento
if (isset($_GET['delete_id'])) {
    include '../server/maintenance/deleteMaintenance.php'; // Eliminar solicitud de mantenimiento
}

try {
    $db = Database::connect();  // Conectar a la base de datos
    $query = "SELECT id, name FROM resources";  // Consulta para obtener los recursos
    $stmt = $db->query($query);  // Ejecutar la consulta
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Obtener los resultados en un arreglo
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}

// Obtener las solicitudes de mantenimiento existentes
try {
    $query = "SELECT m.id, r.name AS resource_name, m.description, m.status, m.priority
              FROM maintenance_requests m
              JOIN resources r ON m.resource_id = r.id";  // Consulta para obtener todas las solicitudes de mantenimiento
    $stmt = $db->query($query);  // Ejecutar la consulta
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Obtener todos los resultados en un arreglo
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}
?>

<h2>Solicitudes de Mantenimiento</h2>

<!-- Botón para agregar una fila nueva -->
<button id="addRowBtn" type="button">Agregar Fila</button>

<!-- Mostrar la tabla de mantenimiento -->
<h3>Solicitudes Activas</h3>
<div id="responseMessage" class="content"></div>
<form action="mantenimiento.php" method="POST" id="maintenanceForm">
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
            <?php
            // Mostrar las solicitudes de mantenimiento existentes en la base de datos
            foreach ($requests as $request) {
                echo "<tr>";
                echo "<td>" . $request['resource_name'] . "</td>";
                echo "<td>" . $request['description'] . "</td>";
                echo "<td>" . $request['status'] . "</td>";
                echo "<td>" . $request['priority'] . "</td>";
                echo "<td>
                        <a href='../server/maintenance/editMaintenance.php?id=" . $request['id'] . "'>Editar</a> | 
                        <a href='mantenimiento.php?delete_id=" . $request['id'] . "'>Eliminar</a>
                    </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</form>

<script>
    // Función para mostrar mensajes al usuario
    function showMessage(message, isError = false) {
        var messageElement = document.getElementById('responseMessage');
        messageElement.innerHTML = message;
        messageElement.style.padding = '10px';
        messageElement.style.margin = '10px 0';
        messageElement.style.backgroundColor = isError ? '#ffcccc' : '#ccffcc';
        messageElement.style.border = '1px solid ' + (isError ? '#ff0000' : '#00cc00');
        messageElement.style.borderRadius = '5px';
        
        // Eliminar el mensaje después de 5 segundos
        setTimeout(function() {
            messageElement.innerHTML = '';
            messageElement.style.padding = '0';
            messageElement.style.margin = '0';
            messageElement.style.backgroundColor = 'transparent';
            messageElement.style.border = 'none';
        }, 5000);
    }

    // Agregar una fila nueva a la tabla
    document.getElementById('addRowBtn').addEventListener('click', function() {
        var table = document.getElementById('maintenanceTable').getElementsByTagName('tbody')[0];
        var newRow = table.insertRow();
        
        // Crear celdas en la nueva fila
        var cell1 = newRow.insertCell(0);
        var cell2 = newRow.insertCell(1);
        var cell3 = newRow.insertCell(2);
        var cell4 = newRow.insertCell(3);
        var cell5 = newRow.insertCell(4);

        // Agregar un select para el recurso
        cell1.innerHTML = `
            <select name="resource_id[]" required>
                <?php foreach ($resources as $resource) { ?>
                    <option value="<?= $resource['id'] ?>"><?= $resource['name'] ?></option>
                <?php } ?>
            </select>
        `;
        
        // Campos de entrada para descripción, estado y prioridad
        cell2.innerHTML = `<input type="text" name="description[]" required>`;
        cell3.innerHTML = `<input type="text" name="status[]" value="reportado" required>`;
        cell4.innerHTML = `<select name="priority[]">
            <option value="baja">Baja</option>
            <option value="media">Media</option>
            <option value="alta">Alta</option>
        </select>`;
        
        // Acción de guardar y eliminar
        cell5.innerHTML = `<button type="button" class="saveBtn">Guardar</button> 
                           <button type="button" class="deleteBtn">Eliminar</button>`;

        // Eliminar fila
        newRow.querySelector('.deleteBtn').addEventListener('click', function() {
            table.deleteRow(newRow.rowIndex - 1);
        });

        // Guardar fila
        newRow.querySelector('.saveBtn').addEventListener('click', function() {
            var resource_id = newRow.querySelector('select').value;
            var description = newRow.querySelector('input[name="description[]"]').value;
            var status = newRow.querySelector('input[name="status[]"]').value;
            var priority = newRow.querySelector('select[name="priority[]"]').value;
            
            // Validar que los campos no estén vacíos
            if (!description || !status || !priority) {
                showMessage("Por favor completa todos los campos", true);
                return;
            }
            
            // Enviar los datos al servidor para guardar en la base de datos
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../server/maintenance/createMaintenance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        showMessage("Solicitud de mantenimiento creada con éxito!");
                        location.reload();  // Recargar la página después de agregar la solicitud
                    } else {
                        showMessage("Error al guardar la solicitud: " + xhr.responseText, true);
                    }
                }
            };
            xhr.send("resource_id=" + resource_id + "&description=" + encodeURIComponent(description) + 
                     "&status=" + status + "&priority=" + priority);
        });
    });
</script>
