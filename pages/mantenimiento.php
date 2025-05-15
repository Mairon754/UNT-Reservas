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
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos para la barra de navegación */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #003366;
            color: white;
            padding: 0px 20px;
            box-shadow: 0 2px 4px rgba(12, 58, 241, 0.76);
            min-height: 70px;
        }

        .nav-links {
            display: flex;
            flex: 1; /* Esto hará que ocupe todo el espacio disponible */
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            height: 70px;        /* Misma altura que navbar min-height */
            display: flex;
            align-items: center;  /* Centra el texto verticalmente */
            transition: background-color 0.3s;
            font-weight: bold;
        }

        .nav-links a:hover {
            background-color: #555;
        }

        /* Perfil de usuario en la navbar */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .user-avatar {
            width: 55px;
            height: 55px;
            background-color: #3498db;
            border-radius: 60%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            font-size: 22px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            margin-right: 10px;
        }

        .user-welcome {
            font-size: 22px;
            color: white;
            font-weight: bold;
            margin-right: 5px;
        }

        .dropdown-menu {
            position: absolute;
            top: 45px;
            right: 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-width: 200px;
            z-index: 1000;
            display: none; /* Inicialmente oculto */
        }

        .dropdown-menu.show {
            display: block; /* Se muestra cuando tiene la clase 'show' */
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background-color: #3498db;
        }

        .dropdown-header strong {
            display: block;
            font-size: 16px;
            color: #ffe;
            font-weight: bold;
        }

        .dropdown-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #fff;
            font-weight: bold;
        }

        .user-role {
            color: #e8f4fc;
            font-weight: 500;
            margin-top: 5px;
            background-color: #2580b3;
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
            font-size: 12px;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #ffe;
            margin: 0;
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .logo-image {
            width: 100px;
            height: auto;
        }
        
        .logo {
            margin-right: 20px;
        }

        /* Nuevos estilos para el contenido principal */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        /* Estilos para el contenedor principal */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Estilos para títulos */
        h2 {
            color: #003366;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 28px;
            position: relative;
            padding-bottom: 10px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 3px;
            background-color: #3498db;
        }
        
        h3 {
            color: #003366;
            margin: 25px 0 15px;
            font-weight: 600;
            font-size: 22px;
        }
        
        /* Estilos para botones */
        button, .btn {
            padding: 10px 18px;
            background-color: #003366;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        button i, .btn i {
            font-size: 16px;
        }
        
        button:hover, .btn:hover {
            background-color: #0051a0;
            transform: translateY(-2px);
        }
        
        #addRowBtn {
            background-color: #2ecc71;
            margin-bottom: 25px;
        }
        
        #addRowBtn:hover {
            background-color: #27ae60;
        }
        
        .saveBtn {
            background-color: #2ecc71;
            padding: 8px 15px;
            border-radius: 4px;
        }
        
        .saveBtn:hover {
            background-color: #27ae60;
        }
        
        .deleteBtn {
            background-color: #e74c3c;
            padding: 8px 15px;
            border-radius: 4px;
        }
        
        .deleteBtn:hover {
            background-color: #c0392b;
        }
        
        /* Estilos para la tabla */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #003366;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        /* Estilos para las acciones en la tabla */
        .action-links {
            display: flex;
            gap: 10px;
        }
        
        .action-links a {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-link {
            color: #003366;
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .edit-link:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }
        
        .delete-link {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }
        
        .delete-link:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }
        
        /* Estilos para indicadores de estado y prioridad */
        .status, .priority {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-reportado {
            background-color: #e1f5fe;
            color: #0288d1;
        }
        
        .status-no-reportado {
            background-color: #ffe0b2;
            color: #ef6c00;
        }
        
        .priority-alta {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .priority-media {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .priority-baja {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Estilos para formularios e inputs */
        input, select {
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        /* Estilos para mensajes de respuesta */
        #responseMessage {
            padding: 0;
            margin: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 8px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Estilos para el footer */
        .footer {
            background-color: #003366;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Media queries para responsividad */
        @media (max-width: 992px) {
            .container {
                padding: 0 15px;
            }
        }
        
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
        }
        
        @media (max-width: 576px) {
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar con rutas fijas -->
    <div class="navbar">
        <div class="nav-links">
            <div class="logo">
                <img src="../ass/logoUNT.png" class="logo-image">
            </div>
            <a href="../pages/dashboard.php">Dashboard</a>
            <?php if ($isAdmin): ?>
            <a href="../pages/recursos.php">Recursos</a>
            <?php endif; ?>
            <a href="../pages/reservas.php">Reservas</a>
            <a href="../pages/mantenimiento.php">Mantenimiento</a>
            <?php if ($isAdmin): ?>
            <a href="../admin/users.php">Usuarios</a>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <span class="user-welcome">Bienvenido, <?php echo $userName; ?>!</span>
        </div>
        <div class="user-profile">
            <div class="user-avatar" id="avatar-dropdown-toggle">
                <span><?= substr($userName, 0, 1) ?></span>
            </div>
            <div class="dropdown-menu" id="user-dropdown-menu">
                <div class="dropdown-header">
                    <strong><?= htmlspecialchars($userName) ?></strong>
                    <p><?= htmlspecialchars($userEmail) ?></p>
                    <p class="user-role"><?= htmlspecialchars($userRole) ?></p>
                </div>
                <div class="dropdown-divider"></div>
                <a href="../pages/profile.php">Mi Perfil</a>
                <a href="../pages/settings.php">Configuración</a>
                <a href="../pages/logout.php">Cerrar sesión</a>
            </div>
        </div>
    </div>

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