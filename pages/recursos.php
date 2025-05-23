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
    <link rel="stylesheet" href="css/navbar-global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/recursosAndReservas.css">
    <script src="js/navbar-mobile.js"></script>
    <style>
        /* Estilos para la barra de navegación */

    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

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