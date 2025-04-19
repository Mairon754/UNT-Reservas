<?php
// listReservations.php: Mostrar las reservas disponibles

require_once '../db/db.php';

try {
    $db = Database::connect();  // Conectar a la base de datos
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

<h2>Reservas Disponibles</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Recurso</th>
        <th>Responsable</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Acciones</th> <!-- Columna para acciones como editar y eliminar -->
    </tr>
    <?php
    foreach ($reservations as $reservation) {
        echo "<tr>";
        echo "<td>" . $reservation['id'] . "</td>";
        echo "<td><input type='text' id='resource_name-" . $reservation['id'] . "' value='" . $reservation['resource_name'] . "' /></td>";
        echo "<td><input type='text' id='responsible_person-" . $reservation['id'] . "' value='" . $reservation['responsible_person'] . "' /></td>";
        echo "<td><input type='date' id='reservation_date-" . $reservation['id'] . "' value='" . $reservation['reservation_date'] . "' /></td>";
        echo "<td><input type='time' id='reservation_time-" . $reservation['id'] . "' value='" . $reservation['reservation_time'] . "' /></td>";
        echo "<td>
                <button onclick='editReservation(" . $reservation['id'] . ")'>Editar</button>
                <button onclick='deleteReservation(" . $reservation['id'] . ")'>Eliminar</button>
              </td>"; // Botones de editar y eliminar
        echo "</tr>";
    }
    ?>
</table>

<!-- Formulario para agregar una nueva reserva -->
<h3>Agregar Nueva Reserva</h3>
<form id="addReservationForm">
    <label for="resource_id">Recurso:</label>
    <select id="resource_id" name="resource_id">
        <option value="1">Aula 101</option>
        <option value="2">Proyector EPSON</option>
        <option value="3">Laptop HP</option>
    </select>
    <br>

    <label for="responsible_person">Persona Responsable:</label>
    <input type="text" id="responsible_person" name="responsible_person" required>
    <br>

    <label for="reservation_date">Fecha:</label>
    <input type="date" id="reservation_date" name="reservation_date" required>
    <br>

    <label for="reservation_time">Hora:</label>
    <input type="time" id="reservation_time" name="reservation_time" required>
    <br>

    <button type="submit">Crear Reserva</button>
</form>

<script>
    // Función para editar una reserva directamente en la tabla
    function editReservation(id) {
        var resource_name = document.getElementById('resource_name-' + id).value;
        var responsible_person = document.getElementById('responsible_person-' + id).value;
        var reservation_date = document.getElementById('reservation_date-' + id).value;
        var reservation_time = document.getElementById('reservation_time-' + id).value;

        // Enviar los datos editados al servidor para actualizar la reserva
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/task/editReservation.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Reserva actualizada correctamente!");
                location.reload();  // Recargar la página después de la edición
            }
        };
        xhr.send("id=" + id + "&resource_name=" + resource_name + "&responsible_person=" + responsible_person + "&reservation_date=" + reservation_date + "&reservation_time=" + reservation_time);
    }

    // Función para eliminar una reserva
    function deleteReservation(id) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "../server/task/deleteReservation.php?id=" + id, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Reserva eliminada correctamente!");
                location.reload();  // Recargar la página después de eliminar
            }
        };
        xhr.send();
    }

    // Función para agregar una nueva reserva
    document.getElementById("addReservationForm").addEventListener("submit", function(event) {
        event.preventDefault();  // Evitar que el formulario se envíe de forma convencional

        var resource_id = document.getElementById("resource_id").value;
        var responsible_person = document.getElementById("responsible_person").value;
        var reservation_date = document.getElementById("reservation_date").value;
        var reservation_time = document.getElementById("reservation_time").value;

        // Enviar los datos al servidor para crear una nueva reserva
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "../server/task/createReservation.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Nueva reserva creada correctamente!");
                location.reload();  // Recargar la página después de agregar la nueva reserva
            }
        };
        xhr.send("resource_id=" + resource_id + "&responsible_person=" + responsible_person + "&reservation_date=" + reservation_date + "&reservation_time=" + reservation_time);
    });
</script>
