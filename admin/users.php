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
                // Asignar rol de administrador
                $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':role_id' => $admin_role_id
                ]);
                
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
                
                // Comprobar si tiene el rol de administrador
                $query = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':role_id' => $admin_role_id
                ]);
                
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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #e9e9e9;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-link {
            padding: 4px 8px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-size: 0.9em;
        }
        
        .edit-link {
            background-color: #4CAF50;
        }
        
        .delete-link {
            background-color: #f44336;
        }
        
        .admin-link {
            background-color: #2196F3;
        }
        
        .remove-admin-link {
            background-color: #FF9800;
        }
        
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .admin-badge {
            background-color: #2196F3;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .user-badge {
            background-color: #9E9E9E;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <?php include_once '../navbar.php'; ?>
    
    <div class="container">
        <h2>Gestión de Usuarios</h2>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>

        <h3>Usuarios Registrados</h3>

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
                                <span class="admin-badge">Administrador</span>
                            <?php else: ?>
                                <span class="user-badge">Usuario</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="editUser.php?id=<?= $user['id'] ?>" class="action-link edit-link">Editar</a>
                            
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete_id=<?= $user['id'] ?>" 
                                   onclick="return confirm('¿Estás seguro de eliminar este usuario?')" 
                                   class="action-link delete-link">Eliminar</a>
                                   
                                <?php if (!$user['is_admin']): ?>
                                    <a href="users.php?make_admin_id=<?= $user['id'] ?>" 
                                       onclick="return confirm('¿Hacer administrador a este usuario?')" 
                                       class="action-link admin-link">Hacer Admin</a>
                                <?php else: ?>
                                    <a href="users.php?remove_admin_id=<?= $user['id'] ?>" 
                                       onclick="return confirm('¿Quitar privilegios de administrador?')" 
                                       class="action-link remove-admin-link">Quitar Admin</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>&copy; 2025 Sistema de Gestión</p>
    </footer>
</body>
</html>