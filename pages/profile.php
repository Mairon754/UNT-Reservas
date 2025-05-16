<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../db/db.php';
$db = Database::connect();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');  // Redirigir al login si no hay sesión
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Obtener datos del usuario
try {
    $query = "SELECT * FROM usrs WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Usuario no encontrado");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // Validaciones básicas
    if (empty($name) || empty($email)) {
        $error = "Todos los campos son obligatorios";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico es inválido";
    } else {
        try {
            // Verificar si el email ya está en uso por otro usuario
            $checkEmail = "SELECT id FROM usrs WHERE email = :email AND id != :id";
            $stmt = $db->prepare($checkEmail);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "El correo electrónico ya está en uso por otro usuario";
            } else {
                // Actualizar datos
                $updateQuery = "UPDATE usrs SET name = :name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($updateQuery);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    $success = "Perfil actualizado exitosamente";
                    // Actualizar datos de sesión
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Recargar datos del usuario
                    $query = "SELECT * FROM usrs WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error al actualizar el perfil";
                }
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Variables para navbar.php
$userName = $user['name'];
$userEmail = $user['email'];
$userRole = ucfirst($user['role']);
$isAdmin = ($user['role'] === 'admin');
$basePath = '..';

// Incluir el header y la navbar
require_once '../navbar.php';
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/profile.css">

<div class="container" class="content">
    <div class="profile-container">
        <h1>Mi Perfil</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <span><?= substr($user['name'], 0, 1) ?></span>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="user-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                </div>
            </div>
            
            <div class="profile-details">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Nombre completo</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Cuenta creada</label>
                        <p class="form-control-static"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))) ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Última actualización</label>
                        <p class="form-control-static"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['updated_at']))) ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Guardar cambios</button>
                        <a href="../pages/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>

</style>
</body>
<footer class="footer">
    <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>
</html>