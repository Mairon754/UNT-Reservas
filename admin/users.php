<?php
// admin/users.php
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

// Eliminar usuario
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Verificar que no se está eliminando a sí mismo
    if ($delete_id == $_SESSION['user_id']) {
        $error_message = "No puedes eliminar tu propio usuario";
    } else {
        try {
            $query = "DELETE FROM usrs WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $delete_id]);

            $success_message = "Usuario eliminado con éxito";
        } catch (PDOException $e) {
            $error_message = "Error al eliminar usuario: " . $e->getMessage();
        }
    }
}

// Cambiar rol de usuario (hacer administrador)
if (isset($_GET['make_admin_id'])) {
    $user_id = $_GET['make_admin_id'];
    
    try {
        // Obtener ID del rol de administrador
        $query = "SELECT id FROM roles WHERE role_name = 'admin'";
        $stmt = $db->query($query);
        $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_role) {
            $admin_role_id = $admin_role['id'];
            
            // Comprobar si ya tiene el rol de administrador
            $query = "SELECT * FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':role_id' => $admin_role_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "El usuario ya es administrador";
            } else {
                // Asignar rol de administrador en la tabla user_roles
                $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':role_id' => $admin_role_id
                ]);
                
                // IMPORTANTE: Actualizar también el campo role en la tabla usrs
                $query = "UPDATE usrs SET role = 'admin' WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
                
                $success_message = "Usuario promovido a administrador con éxito";
            }
        } else {
            $error_message = "No se encontró el rol de administrador en la base de datos";
        }
    } catch (PDOException $e) {
        $error_message = "Error al cambiar el rol del usuario: " . $e->getMessage();
    }
}

// Quitar rol de administrador
if (isset($_GET['remove_admin_id'])) {
    $user_id = $_GET['remove_admin_id'];
    
    // Evitar quitar privilegios de admin a sí mismo
    if ($user_id == $_SESSION['user_id']) {
        $error_message = "No puedes quitar tus propios privilegios de administrador";
    } else {
        try {
            // Obtener ID del rol de administrador
            $query = "SELECT id FROM roles WHERE role_name = 'admin'";
            $stmt = $db->query($query);
            $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin_role) {
                $admin_role_id = $admin_role['id'];
                
                // Quitar rol de administrador de la tabla user_roles
                $query = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':role_id' => $admin_role_id
                ]);
                
                // IMPORTANTE: Actualizar también el campo role en la tabla usrs
                $query = "UPDATE usrs SET role = 'user' WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
                
                // Asegurarse de que el usuario tiene al menos el rol de usuario normal
                $query = "SELECT id FROM roles WHERE role_name = 'user'";
                $stmt = $db->query($query);
                $user_role = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_role) {
                    $user_role_id = $user_role['id'];
                    
                    // Asignar rol de usuario normal si no lo tiene
                    $query = "INSERT INTO user_roles (user_id, role_id) 
                             SELECT :user_id, :role_id 
                             WHERE NOT EXISTS (
                                 SELECT 1 FROM user_roles 
                                 WHERE user_id = :user_id2 AND role_id = :role_id2
                             )";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':role_id' => $user_role_id,
                        ':user_id2' => $user_id,
                        ':role_id2' => $user_role_id
                    ]);
                }
                
                $success_message = "Privilegios de administrador removidos con éxito";
            } else {
                $error_message = "No se encontró el rol de administrador en la base de datos";
            }
        } catch (PDOException $e) {
            $error_message = "Error al cambiar el rol del usuario: " . $e->getMessage();
        }
    }
}

// Obtener la lista de usuarios con sus roles
try {
    $query = "SELECT u.id, u.name, u.email, 
              CASE WHEN admin.role_id IS NOT NULL THEN TRUE ELSE FALSE END AS is_admin
              FROM usrs u
              LEFT JOIN (
                  SELECT ur.user_id, ur.role_id 
                  FROM user_roles ur
                  JOIN roles r ON ur.role_id = r.id
                  WHERE r.role_name = 'admin'
              ) admin ON u.id = admin.user_id
              ORDER BY u.name";
    
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al obtener usuarios: " . $e->getMessage();
}

// Obtener el rol del usuario actual para mostrarlo en el navbar
$userRole = "Usuario";
$userName = $_SESSION['name'] ?? 'Usuario';
$userEmail = $_SESSION['email'] ?? '';
$isAdmin = $_SESSION['is_admin'] ?? false;

try {
    $query = "SELECT r.role_name 
              FROM user_roles ur 
              JOIN roles r ON ur.role_id = r.id 
              WHERE ur.user_id = :user_id
              ORDER BY CASE WHEN r.role_name = 'admin' THEN 0 ELSE 1 END";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($roles)) {
        $userRole = ucfirst($roles[0]);
    }
} catch (PDOException $e) {
    // Si hay un error, usar un valor predeterminado
    $userRole = $isAdmin ? 'Administrador' : 'Usuario';
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
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

        /* Estilos para los badges de rol */
        .admin-badge, .user-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }
        
        .admin-badge {
            background-color: #e1f5fe;
            color: #0288d1;
        }
        
        .user-badge {
            background-color: #e0e0e0;
            color: #616161;
        }

        /* Estilos para los botones de acción */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .edit-link {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }
        
        .edit-link:hover {
            background-color: rgba(46, 204, 113, 0.2);
        }
        
        .delete-link {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .delete-link:hover {
            background-color: rgba(231, 76, 60, 0.2);
        }
        
        .admin-link {
            background-color: rgba(52, 152, 219, 0.1);
            color: #2980b9;
        }
        
        .admin-link:hover {
            background-color: rgba(52, 152, 219, 0.2);
        }
        
        .remove-admin-link {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }
        
        .remove-admin-link:hover {
            background-color: rgba(243, 156, 18, 0.2);
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
            .table-container {
                overflow-x: auto;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-link {
                font-size: 12px;
                padding: 5px 10px;
            }
        }
        
        @media (max-width: 576px) {
            h2 {
                font-size: 24px;
            }
            
            h3 {
                font-size: 20px;
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
        <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?= $success_message ?></div>
        <?php endif; ?>

        <h3><i class="fas fa-user-friends"></i> Usuarios Registrados</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-badge"><i class="fas fa-user-shield"></i> Administrador</span>
                                <?php else: ?>
                                    <span class="user-badge"><i class="fas fa-user"></i> Usuario</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="editUser.php?id=<?= $user['id'] ?>" class="action-link edit-link">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?delete_id=<?= $user['id'] ?>" 
                                       onclick="return confirm('¿Estás seguro de eliminar este usuario?')" 
                                       class="action-link delete-link">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </a>
                                       
                                    <?php if (!$user['is_admin']): ?>
                                        <a href="users.php?make_admin_id=<?= $user['id'] ?>" 
                                           onclick="return confirm('¿Hacer administrador a este usuario?')" 
                                           class="action-link admin-link">
                                            <i class="fas fa-user-plus"></i> Hacer Admin
                                        </a>
                                    <?php else: ?>
                                        <a href="users.php?remove_admin_id=<?= $user['id'] ?>" 
                                           onclick="return confirm('¿Quitar privilegios de administrador?')" 
                                           class="action-link remove-admin-link">
                                            <i class="fas fa-user-minus"></i> Quitar Admin
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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