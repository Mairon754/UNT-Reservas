<?php
// Ajustar las rutas relativas
include '../navbar.php';  // Subir un directorio para acceder a navbar.php
require_once '../db/db.php';  // Subir un directorio para acceder a db.php


// Lógica de eliminación de una reserva
if (isset($_GET['delete_id'])) {
    include '../server/reservation/deleteReservation.php'; // Eliminar reserva
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

// Obtener las reservas existentes
try {
    $query = "SELECT r.id, res.name AS resource_name, r.responsible_person, r.reservation_date, r.reservation_time
              FROM reservations r
              JOIN resources res ON r.resource_id = res.id";  // Consulta para obtener todas las reservas
    $stmt = $db->query($query);  // Ejecutar la consulta
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Obtener todos los resultados en un arreglo
} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}
?>

<h2>Gestión de Reservas</h2>

<!-- Botón para agregar una fila nueva -->
<button id="addRowBtn" type="button">Agregar Fila</button>

<!-- Mostrar la tabla de reservas -->
<h3>Reservas Activas</h3>
<div id="responseMessage"></div>
<form action="reservas.php" method="POST" id="reservationForm">
    <table id="reservationsTable">
        <thead>
            <tr>
                <th>Recurso</th>
                <th>Responsable</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar las reservas existentes en la base de datos
            foreach ($reservations as $reservation) {
                echo "<tr>";
                echo "<td>" . $reservation['resource_name'] . "</td>";
                echo "<td>" . $reservation['responsible_person'] . "</td>";
                echo "<td>" . $reservation['reservation_date'] . "</td>";
                echo "<td>" . $reservation['reservation_time'] . "</td>";
                echo "<td>
                        <a href='../server/reservation/editReservation.php?id=" . $reservation['id'] . "'>Editar</a> | 
                        <a href='reservas.php?delete_id=" . $reservation['id'] . "'>Eliminar</a>
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
        var table = document.getElementById('reservationsTable').getElementsByTagName('tbody')[0];
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
        
        // Campos de entrada para responsable, fecha y hora
        cell2.innerHTML = `<input type="text" name="responsible_person[]" required>`;
        cell3.innerHTML = `<input type="date" name="reservation_date[]" required>`;
        cell4.innerHTML = `<input type="time" name="reservation_time[]" required>`;
        
        // Acción de editar y eliminar (por ahora vacío para insertar)
        cell5.innerHTML = `<button type="button" class="saveBtn">Guardar</button> 
                           <button type="button" class="deleteBtn">Eliminar</button>`;

        // Eliminar fila
        newRow.querySelector('.deleteBtn').addEventListener('click', function() {
            table.deleteRow(newRow.rowIndex - 1); // Restamos 1 porque el rowIndex es relativo a toda la tabla
        });

        // Guardar fila
        newRow.querySelector('.saveBtn').addEventListener('click', function() {
            var resource_id = newRow.querySelector('select').value;
            var responsible_person = newRow.querySelector('input[name="responsible_person[]"]').value;
            var reservation_date = newRow.querySelector('input[name="reservation_date[]"]').value;
            var reservation_time = newRow.querySelector('input[name="reservation_time[]"]').value;
            
            // Validar que los campos no estén vacíos
            if (!responsible_person || !reservation_date || !reservation_time) {
                showMessage("Por favor completa todos los campos", true);
                return;
            }
            
            // Deshabilitar el botón mientras se procesa
            var saveBtn = newRow.querySelector('.saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';
            
            // Enviar datos al servidor para guardar en la base de datos
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../server/reservation/createReservation.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Guardar';
                    
                    if (xhr.status == 200) {
                        showMessage("Reserva creada con éxito!");
                        // Recargar la página después de 1 segundo para mostrar la nueva reserva
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage("Error al guardar la reserva: " + xhr.responseText, true);
                    }
                }
            };
            xhr.send("resource_id=" + encodeURIComponent(resource_id) + 
                     "&responsible_person=" + encodeURIComponent(responsible_person) + 
                     "&reservation_date=" + encodeURIComponent(reservation_date) + 
                     "&reservation_time=" + encodeURIComponent(reservation_time));
        });
    });
</script>
