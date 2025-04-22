<?php
// admin/users.php
session_start();
require_once '../db/db.php';
$db = Database::connect();
// Verificar si el usuario está logueado y tiene permisos de administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');  // Redirigir al login si no está logueado o no es administrador
    exit();
}

$error_message = "";
$success_message = "";

// Eliminar usuario
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $db = Database::connect();
        $query = "DELETE FROM usrs WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $delete_id]);

        $success_message = "Usuario eliminado con éxito";
    } catch (PDOException $e) {
        $error_message = "Error al eliminar usuario: " . $e->getMessage();
    }
}

// Obtener la lista de usuarios
try {
    $db = Database::connect();
    $query = "SELECT id, name, email, role FROM usrs";
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
</head>
<body>
    <header>
        <h2>Gestión de Usuarios</h2>
    </header>
    
    <div class="container">
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
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <a href="editUser.php?id=<?= $user['id'] ?>">Editar</a> |
                            <a href="users.php?delete_id=<?= $user['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
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
