<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Obtener datos del usuario
try {
    $query = "SELECT * FROM users WHERE id = :id";
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
            $checkEmail = "SELECT id FROM users WHERE email = :email AND id != :id";
            $stmt = $db->prepare($checkEmail);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "El correo electrónico ya está en uso por otro usuario";
            } else {
                // Actualizar datos
                $updateQuery = "UPDATE users SET name = :name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
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
                    $query = "SELECT * FROM users WHERE id = :id";
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
                        <a href="../dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-container h1 {
    margin-bottom: 30px;
    color: #333;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.profile-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.profile-header {
    display: flex;
    align-items: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: bold;
    margin-right: 20px;
}

.profile-info h2 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.user-role {
    color: #3498db;
    font-weight: 500;
    margin-top: 5px;
    font-size: 16px;
}

.profile-details {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.form-control-static {
    padding: 10px 0;
    font-size: 16px;
    color: #666;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
}

.btn {
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    transition: background-color 0.3s;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-secondary:hover {
    background-color: #5a6268;
}
</style>
</body>
</html>