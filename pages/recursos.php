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
        
        /* Estilos para estados de recursos */
        .status-select {
            padding: 8px 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .status-select option {
            padding: 8px;
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
        <h2><i class="fas fa-boxes"></i> Gestión de Recursos</h2>
        <div id="responseMessage"></div>
        <button id="addRowBtn" type="button"><i class="fas fa-plus-circle"></i> Agregar Recurso</button>
        
        <div class="table-container">
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
                        <td><span class="resource-type"><?= htmlspecialchars($resource['type']) ?></span></td>
                        <td>
                            <select name="status" data-id="<?= $resource['id'] ?>" class="resource-status status-select" required>
                                <option value="disponible" <?= $resource['status'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="no disponible" <?= $resource['status'] == 'no disponible' ? 'selected' : '' ?>>No Disponible</option>
                            </select>
                        </td>
                        <td class="action-links">
                            <a href="../server/task/editResource.php?id=<?= $resource['id'] ?>" class="edit-link">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="../pages/recursos.php?delete_id=<?= $resource['id'] ?>" class="delete-link" 
                               onclick="return confirm('¿Estás seguro de eliminar este recurso?')">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resources)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">No hay recursos disponibles</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
            messageElement.className = isError ? 'error-message' : 'success-message';
            
            // Eliminar el mensaje después de 5 segundos
            setTimeout(function() {
                messageElement.innerHTML = '';
                messageElement.className = '';
            }, 5000);
        }

        // Agregar una fila nueva a la tabla
        document.getElementById('addRowBtn').addEventListener('click', function() {
            var table = document.getElementById('resourcesTable').getElementsByTagName('tbody')[0];
            var newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="name" placeholder="Nombre del recurso" required></td>
                <td><input type="text" name="type" placeholder="Tipo de recurso" required></td>
                <td>
                    <select name="status" class="status-select" required>
                        <option value="disponible">Disponible</option>
                        <option value="no disponible">No Disponible</option>
                    </select>
                </td>
                <td class="action-links">
                    <button type="button" class="saveBtn"><i class="fas fa-save"></i> Guardar</button>
                    <button type="button" class="deleteBtn"><i class="fas fa-times"></i> Cancelar</button>
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