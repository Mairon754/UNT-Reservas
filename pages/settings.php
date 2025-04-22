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

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validaciones
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Todos los campos son obligatorios";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Las nuevas contraseñas no coinciden";
    } elseif (strlen($newPassword) < 8) {
        $error = "La nueva contraseña debe tener al menos 8 caracteres";
    } else {
        try {
            // Verificar contraseña actual
            if (!password_verify($currentPassword, $user['password'])) {
                $error = "La contraseña actual es incorrecta";
            } else {
                // Actualizar contraseña
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($updateQuery);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    $success = "Contraseña actualizada exitosamente";
                } else {
                    $error = "Error al actualizar la contraseña";
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
    <div class="settings-container">
        <h1>Configuración</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2>Cambiar contraseña</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <small class="form-text">La contraseña debe tener al menos 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="change_password" class="btn btn-primary">Cambiar contraseña</button>
                </div>
            </form>
        </div>
        
        <?php if ($isAdmin): ?>
        <div class="settings-card mt-4">
            <h2>Configuración de administrador</h2>
            <p>Como administrador, puedes gestionar usuarios y configuraciones del sistema.</p>
            
            <div class="admin-links">
                <a href="../admin/users.php" class="admin-link">
                    <span class="icon">👥</span>
                    <div>
                        <h3>Gestión de usuarios</h3>
                        <p>Administrar usuarios, roles y permisos</p>
                    </div>
                </a>
                
                <a href="../admin/system.php" class="admin-link">
                    <span class="icon">⚙️</span>
                    <div>
                        <h3>Configuración del sistema</h3>
                        <p>Parámetros generales y preferencias</p>
                    </div>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="settings-card mt-4">
            <h2>Preferencias de notificación</h2>
            
            <form method="POST" action="">
                <div class="form-check">
                    <input type="checkbox" id="email_notifications" name="email_notifications" class="form-check-input" checked>
                    <label for="email_notifications" class="form-check-label">Recibir notificaciones por correo electrónico</label>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="reservation_notifications" name="reservation_notifications" class="form-check-input" checked>
                    <label for="reservation_notifications" class="form-check-label">Notificaciones de reservas</label>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="maintenance_notifications" name="maintenance_notifications" class="form-check-input" checked>
                    <label for="maintenance_notifications" class="form-check-label">Notificaciones de mantenimiento</label>
                </div>
                
                <div class="form-actions mt-3">
                    <button type="submit" name="save_preferences" class="btn btn-primary">Guardar preferencias</button>
                </div>
            </form>
            <small class="form-text text-muted mt-2">Nota: La funcionalidad de notificaciones está en desarrollo y estará disponible próximamente.</small>
        </div>
        
        <div class="nav-buttons mt-4">
            <a href="../profile.php" class="btn btn-secondary">Mi perfil</a>
            <a href="../dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.settings-container h1 {
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

.settings-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 20px;
}

.settings-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
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

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 14px;
    color: #6c757d;
}

.form-check {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.form-check-input {
    margin-right: 10px;
    width: 18px;
    height: 18px;
}

.form-check-label {
    font-size: 16px;
    color: #333;
}

.form-actions {
    margin-top: 20px;
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

.mt-3 {
    margin-top: 15px;
}

.mt-4 {
    margin-top: 20px;
}

.text-muted {
    color: #6c757d;
}

.admin-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.admin-link {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 5px;
    background-color: #f8f9fa;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.admin-link:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.admin-link .icon {
    font-size: 24px;
    margin-right: 15px;
}

.admin-link h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.admin-link p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #6c757d;
}

.nav-buttons {
    display: flex;
    gap: 15px;
}

@media (max-width: 768px) {
    .admin-links {
        grid-template-columns: 1fr;
    }
}
</style>
</body>
</html>