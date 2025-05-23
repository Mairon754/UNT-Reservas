<?php
// pages/mantenimiento.php - Versión con rutas fijas
// Ya no usamos include de navbar, lo incluimos directamente
require_once '../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener datos del usuario para la navbar
$userName = $_SESSION['name'] ?? 'Usuario';
$userEmail = $_SESSION['email'] ?? '';
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Obtener rol del usuario desde la base de datos
try {
    $db = Database::connect();
    $query = "SELECT r.role_name 
              FROM user_roles ur 
              JOIN roles r ON ur.role_id = r.id 
              WHERE ur.user_id = :user_id
              ORDER BY CASE WHEN r.role_name = 'admin' THEN 0 ELSE 1 END";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si no tiene roles asignados, establecer como usuario por defecto
    if (empty($roles)) {
        $userRole = 'Usuario';
    } else {
        // Usar el primer rol (el más importante según el ORDER BY)
        $userRole = ucfirst($roles[0]);
    }
} catch (PDOException $e) {
    // Si hay un error en la consulta, usar un valor predeterminado
    $userRole = $isAdmin ? 'Administrador' : 'Usuario';
}

// Lógica de eliminación de una solicitud de mantenimiento
if (isset($_GET['delete_id'])) {
    include '../server/maintenance/deleteMaintenance.php'; // Eliminar solicitud de mantenimiento
}

try {
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

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento</title>
<link rel="stylesheet" href="../css/pages.css">
<link rel="stylesheet" href="css/navbar-global.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>
<body>

<?php include '../navbar.php'; ?>
    <div class="container">
        <h2><i class="fas fa-tools"></i> Solicitudes de Mantenimiento</h2>

        <!-- Botón para agregar una fila nueva -->
        <button id="addRowBtn" type="button"><i class="fas fa-plus-circle"></i> Agregar Solicitud</button>

        <!-- Mostrar mensajes de respuesta -->
        <div id="responseMessage"></div>

        <!-- Mostrar la tabla de mantenimiento -->
        <h3><i class="fas fa-clipboard-list"></i> Solicitudes Activas</h3>
        <div class="table-container">
            <form action="../pages/mantenimiento.php" method="POST" id="maintenanceForm">
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
                            // Determinar clases de estilo para estado y prioridad
                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $request['status']));
                            $priorityClass = 'priority-' . strtolower($request['priority']);
                            
                            echo "<tr>";
                            echo "<td>" . $request['resource_name'] . "</td>";
                            echo "<td>" . $request['description'] . "</td>";
                            echo "<td><span class='status " . $statusClass . "'>" . $request['status'] . "</span></td>";
                            echo "<td><span class='priority " . $priorityClass . "'>" . $request['priority'] . "</span></td>";
                            echo "<td class='action-links'>
                                    <a href='../server/maintenance/editMaintenance.php?id=" . $request['id'] . "' class='edit-link'><i class='fas fa-edit'></i> Editar</a>
                                    <a href='../pages/mantenimiento.php?delete_id=" . $request['id'] . "' class='delete-link'><i class='fas fa-trash-alt'></i> Eliminar</a>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        // Si no hay solicitudes, mostrar mensaje
                        if (empty($requests)) {
                            echo "<tr><td colspan='5' style='text-align: center; padding: 20px;'>No hay solicitudes de mantenimiento activas</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>

    <script>
        // Función para el dropdown del avatar
        document.addEventListener('DOMContentLoaded', function() {
            const avatarToggle = document.getElementById('avatar-dropdown-toggle');
            const dropdownMenu = document.getElementById('user-dropdown-menu');

            // Mostrar/ocultar el menú al hacer clic en el avatar
            avatarToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // Cerrar el menú si se hace clic fuera de él
            document.addEventListener('click', function(e) {
                if (!avatarToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });

        // Función para mostrar mensajes al usuario
        function showMessage(message, isError = false) {
            var messageElement = document.getElementById('responseMessage');
            messageElement.innerHTML = message;
            messageElement.className = isError ? 'error-message' : 'success-message';
            
            // Eliminar el mensaje después de 5 segundos
            setTimeout(function() {
                messageElement.innerHTML = '';
                messageElement.className = '';
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
            cell2.innerHTML = `<input type="text" name="description[]" required placeholder="Describe el problema...">`;
            cell3.innerHTML = `
                <select name="status[]" required>
                    <option value="reportado">Reportado</option>
                    <option value="no reportado">No Reportado</option>
                </select>
            `;
            cell4.innerHTML = `<select name="priority[]">
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
            </select>`;
            
            // Acción de guardar y eliminar
            cell5.innerHTML = `<button type="button" class="saveBtn"><i class="fas fa-save"></i> Guardar</button> 
                              <button type="button" class="deleteBtn"><i class="fas fa-times"></i> Cancelar</button>`;

            // Eliminar fila
            newRow.querySelector('.deleteBtn').addEventListener('click', function() {
                table.deleteRow(newRow.rowIndex - 1);
            });

            // Guardar fila
            newRow.querySelector('.saveBtn').addEventListener('click', function() {
                var resource_id = newRow.querySelector('select').value;
                var description = newRow.querySelector('input[name="description[]"]').value;
                var status = newRow.querySelector('select[name="status[]"]').value;
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
</body>
</html>