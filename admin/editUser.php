<?php
// admin/editUser.php
session_start();
require_once '../db/db.php';

// Verificar si el usuario está logueado y tiene permisos de administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');  // Redirigir al login si no está logueado o no es administrador
    exit();
}

$error_message = "";
$success_message = "";
$db = Database::connect();

// Obtener datos del usuario para la navbar
$userName = $_SESSION['name'] ?? 'Usuario';
$userEmail = $_SESSION['email'] ?? '';
$isAdmin = $_SESSION['is_admin'] ?? false;

// Obtener rol del usuario desde la base de datos
try {
    $query = "SELECT role FROM usrs WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    
    $userRole = ucfirst($role);
} catch (PDOException $e) {
    // Si hay un error en la consulta, usar un valor predeterminado
    $userRole = $isAdmin ? 'Administrador' : 'Usuario';
}

// Obtener el ID del usuario a editar
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no se proporciona un ID válido, redirigir a la lista de usuarios
if ($userId <= 0) {
    header('Location: users.php');
    exit();
}

// Cargar datos del usuario a editar
$userToEdit = null;
try {
    // Primero verificamos qué columnas existen en la tabla usrs
    $columnsQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'usrs'";
    $columnsStmt = $db->query($columnsQuery);
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construimos dinámicamente la consulta basándonos en las columnas que existen
    $selectColumns = "id";
    if (in_array('username', $columns)) {
        $selectColumns .= ", username";
    } elseif (in_array('email', $columns)) {
        $selectColumns .= ", email AS username"; // Usamos email como username si existe
    } elseif (in_array('name', $columns)) {
        $selectColumns .= ", name AS username"; // Usamos name como username si existe
    }
    
    if (in_array('role', $columns)) {
        $selectColumns .= ", role";
    }
    
    $query = "SELECT $selectColumns FROM usrs WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $userId]);
    $userToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userToEdit) {
        header('Location: users.php');
        exit();
    }
    
    // Si no existe la columna role, establecemos un valor predeterminado
    if (!isset($userToEdit['role'])) {
        $userToEdit['role'] = 'user';
    }
    
    // Si no hay username, usamos un valor vacío
    if (!isset($userToEdit['username'])) {
        $userToEdit['username'] = '';
    }
} catch (PDOException $e) {
    $error_message = "Error al cargar datos del usuario: " . $e->getMessage();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $newUsername = trim($_POST['username']);
    $newPassword = trim($_POST['password']);
    $newRole = $_POST['role'];
    
    // Validaciones básicas
    if (empty($newUsername)) {
        $error_message = "El nombre de usuario no puede estar vacío";
    } else {
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Verificar qué columnas existen en la tabla usrs
            $columnsQuery = "SELECT column_name FROM information_schema.columns WHERE table_name = 'usrs'";
            $columnsStmt = $db->query($columnsQuery);
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Verificar si el nuevo username ya existe (si se cambió)
            if ($newUsername !== $userToEdit['username']) {
                // Determinar qué columna verificar para el username
                $usernameColumn = 'username';
                if (!in_array('username', $columns)) {
                    if (in_array('email', $columns)) {
                        $usernameColumn = 'email';
                    } elseif (in_array('name', $columns)) {
                        $usernameColumn = 'name';
                    }
                }
                
                $query = "SELECT COUNT(*) FROM usrs WHERE $usernameColumn = :username AND id != :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':username' => $newUsername, ':id' => $userId]);
                
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("El nombre de usuario ya está en uso");
                }
            }
            
            // Construir la consulta de actualización basada en las columnas existentes
            $updateSet = [];
            $params = [':id' => $userId];
            
            // Manejo del username (o su equivalente)
            if (in_array('username', $columns)) {
                $updateSet[] = "username = :username";
                $params[':username'] = $newUsername;
            } elseif (in_array('email', $columns)) {
                $updateSet[] = "email = :email";
                $params[':email'] = $newUsername;
            } elseif (in_array('name', $columns)) {
                $updateSet[] = "name = :name";
                $params[':name'] = $newUsername;
            }
            
            // Manejo del rol si la columna existe
            if (in_array('role', $columns)) {
                $updateSet[] = "role = :role";
                $params[':role'] = $newRole;
            }
            
            // Manejo de la contraseña si se proporcionó
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateSet[] = "password = :password";
                $params[':password'] = $hashedPassword;
            }
            
            // Actualización de timestamp si existe
            if (in_array('updated_at', $columns)) {
                $updateSet[] = "updated_at = CURRENT_TIMESTAMP";
            }
            
            // Si no hay nada que actualizar, lanzar excepción
            if (empty($updateSet)) {
                throw new Exception("No hay campos para actualizar");
            }
            
            // Construir la consulta completa
            $query = "UPDATE usrs SET " . implode(", ", $updateSet) . " WHERE id = :id";
            
            // Ejecutar la actualización
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            // Confirmar la transacción
            $db->commit();
            
            $success_message = "Usuario actualizado con éxito";
            
            // Actualizar los datos cargados para mostrar los valores actualizados
            $userToEdit['username'] = $newUsername;
            if (isset($userToEdit['role'])) {
                $userToEdit['role'] = $newRole;
            }
            
        } catch (Exception $e) {
            // Deshacer la transacción en caso de error
            $db->rollBack();
            $error_message = "Error al actualizar usuario: " . $e->getMessage();
        }
    }
}

// Verificar si es el propio usuario quien se está editando
$isSelfEditing = ($userId == $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Usuario</title>
<link rel="stylesheet" href="../css/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../css/user.css">

    <style>
/* Estilos para la barra de navegación */

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
        <h2><i class="fas fa-user-edit"></i> Editar Usuario</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?= $success_message ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="editUser.php?id=<?= $userId ?>">
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= $userToEdit ? htmlspecialchars($userToEdit['username']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <p class="password-note">Deje este campo en blanco si no desea cambiar la contraseña actual.</p>
                </div>
                
                <div class="form-group">
                    <label for="role">Rol</label>
                    <select id="role" name="role" class="form-control" <?= $isSelfEditing ? 'disabled' : '' ?>>
                        <option value="user" <?= ($userToEdit && $userToEdit['role'] === 'user') ? 'selected' : '' ?>>Usuario</option>
                        <option value="admin" <?= ($userToEdit && $userToEdit['role'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    <?php if ($isSelfEditing && $userToEdit): ?>
                        <p class="password-note">No puedes cambiar tu propio rol.</p>
                        <input type="hidden" name="role" value="<?= $userToEdit['role'] ?>">
                    <?php endif; ?>
                </div>
                
                <div class="actions">
                    <button type="submit" name="update_user" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <footer>
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
    </script>
</body>
</html>