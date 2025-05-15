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

        /* Estilos para formularios */
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #003366;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        select.form-control {
            background-color: white;
            cursor: pointer;
        }
        
        .password-note {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Estilos para botones */
        .btn {
            padding: 12px 22px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: #003366;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0051a0;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        /* Estilos para mensajes de respuesta */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        /* Estilos para el footer */
        footer {
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
            .actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            h2 {
                font-size: 24px;
            }
            
            h3 {
                font-size: 20px;
            }
            
            .form-container {
                padding: 15px;
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