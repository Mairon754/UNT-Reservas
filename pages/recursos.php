<?php
// pages/recursos.php - Versión con rutas fijas
// Ya no usamos include de navbar, lo incluimos directamente
require_once '../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Verificar si el usuario es administrador
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../pages/dashboard.php');
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

// Eliminar recurso si se envía delete_id por GET
if (isset($_GET['delete_id'])) {
    include '../server/task/deleteResource.php';
}

// Obtener listado de recursos
try {
    $query = "SELECT * FROM resources";
    $stmt = $db->query($query);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener recursos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Recursos</title>
    <link rel="stylesheet" href="../css/styles.css">
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
        
        /* Estilos para la tabla de recursos */
        .container {
            padding: 20px;
        }
        
        h2 {
            margin-bottom: 20px;
            color: #003366;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #003366;
            ;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        button {
            padding: 8px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .saveBtn {
            background-color: #4CAF50;
        }
        
        .deleteBtn {
            background-color: #f44336;
            margin-left: 5px;
        }
        
        input, select {
            padding: 6px 10px;
            margin: 4px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .footer {
            background-color: #003366;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 30px;
        }
        
        #responseMessage {
            margin: 10px 0;
            padding: 0;
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
                        <select name="status" data-id="<?= $resource['id'] ?>" class="resource-status" required>
                            <option value="disponible" <?= $resource['status'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                            <option value="no disponible" <?= $resource['status'] == 'no disponible' ? 'selected' : '' ?>>No Disponible</option>
                        </select>
                    </td>
                    <td>
                        <a href="../server/task/editResource.php?id=<?= $resource['id'] ?>">Editar</a> |
                        <a href="../pages/recursos.php?delete_id=<?= $resource['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este recurso?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
            
            // Actualizar estado de recursos cuando cambian
            document.querySelectorAll('.resource-status').forEach(function(select) {
                select.addEventListener('change', function() {
                    const resourceId = this.getAttribute('data-id');
                    const newStatus = this.value;
                    
                    // Enviar actualización mediante AJAX
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "../server/task/updateResourceStatus.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState == 4) {
                            if (xhr.status == 200) {
                                showMessage("Estado actualizado correctamente");
                            } else {
                                showMessage("Error al actualizar el estado", true);
                            }
                        }
                    };
                    xhr.send("id=" + resourceId + "&status=" + newStatus);
                });
            });
        });

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
            var table = document.getElementById('resourcesTable').getElementsByTagName('tbody')[0];
            var newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="name" required></td>
                <td><input type="text" name="type" required></td>
                <td>
                    <select name="status" required>
                        <option value="disponible">Disponible</option>
                        <option value="no disponible">No Disponible</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="saveBtn">Guardar</button>
                    <button type="button" class="deleteBtn">Eliminar</button>
                </td>`;
                
            newRow.querySelector('.deleteBtn').addEventListener('click', function() {
                table.deleteRow(newRow.rowIndex - 1);
            });
                
            newRow.querySelector('.saveBtn').addEventListener('click', function() {
                var name = newRow.querySelector('input[name="name"]').value;
                var type = newRow.querySelector('input[name="type"]').value;
                var status = newRow.querySelector('select[name="status"]').value;
                
                if (!name || !type) {
                    showMessage("Por favor complete todos los campos", true);
                    return;
                }
                
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../server/task/createResource.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4) {
                        if (xhr.status == 200) {
                            showMessage("Recurso creado con éxito");
                            location.reload();
                        } else {
                            showMessage("Error al crear el recurso: " + xhr.responseText, true);
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
</body>
</html>