<?php
// pages/settings.php - Versión completa con todas las secciones funcionales
require_once '../db/db.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener datos del usuario para la navbar
$userName = $_SESSION['name'] ?? 'Usuario';
$userEmail = $_SESSION['email'] ?? '';
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;
$userId = $_SESSION['user_id'];

// Mensajes de estado y error
$statusMessage = '';
$errorMessage = '';
$activeTab = 'notifications'; // Pestaña activa por defecto

// Conectar a la base de datos
try {
    $db = Database::connect();
    
    // Obtener datos completos del usuario
    $query = "SELECT * FROM usrs WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener configuración actual de notificaciones
    $query = "SELECT * FROM user_notification_settings WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $notificationSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no hay configuración, crear con valores predeterminados
    if (!$notificationSettings) {
        $query = "INSERT INTO user_notification_settings 
                  (user_id, email_notifications, reservation_notifications, maintenance_notifications) 
                  VALUES (:user_id, TRUE, TRUE, TRUE)";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        
        // Obtener los valores recién insertados
        $query = "SELECT * FROM user_notification_settings WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $notificationSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorMessage = "Error al obtener datos: " . $e->getMessage();
    // Valores predeterminados por si falla la consulta
    $notificationSettings = [
        'email_notifications' => true,
        'reservation_notifications' => true,
        'maintenance_notifications' => true
    ];
}

// Procesar formulario de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notifications'])) {
    $activeTab = 'notifications';
    
    // Obtener valores del formulario
    $emailNotifications = isset($_POST['email_notifications']) ? true : false;
    $reservationNotifications = isset($_POST['reservation_notifications']) ? true : false;
    $maintenanceNotifications = isset($_POST['maintenance_notifications']) ? true : false;
    
    try {
        // Actualizar la configuración en la base de datos
        $query = "UPDATE user_notification_settings 
                  SET email_notifications = :email_notifications,
                      reservation_notifications = :reservation_notifications,
                      maintenance_notifications = :maintenance_notifications,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':email_notifications' => $emailNotifications,
            ':reservation_notifications' => $reservationNotifications,
            ':maintenance_notifications' => $maintenanceNotifications,
            ':user_id' => $userId
        ]);
        
        // Actualizar los valores locales después de guardar
        $notificationSettings['email_notifications'] = $emailNotifications;
        $notificationSettings['reservation_notifications'] = $reservationNotifications;
        $notificationSettings['maintenance_notifications'] = $maintenanceNotifications;
        
        $statusMessage = "Preferencias de notificaciones guardadas correctamente.";
    } catch (PDOException $e) {
        $errorMessage = "Error al guardar las preferencias: " . $e->getMessage();
    }
}

// Procesar formulario de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $activeTab = 'profile';
    
    // Obtener valores del formulario
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    // Validar datos
    if (empty($name) || empty($email)) {
        $errorMessage = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "El formato del correo electrónico no es válido.";
    } else {
        try {
            // Verificar si el correo ya existe para otro usuario
            $query = "SELECT COUNT(*) FROM usrs WHERE email = :email AND id != :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $email, ':id' => $userId]);
            $emailExists = $stmt->fetchColumn();
            
            if ($emailExists) {
                $errorMessage = "El correo electrónico ya está en uso por otro usuario.";
            } else {
                // Actualizar datos del usuario
                $query = "UPDATE usrs SET name = :name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':id' => $userId
                ]);
                
                if ($result) {
                    // Actualizar datos en sesión
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $userName = $name;
                    $userEmail = $email;
                    
                    // Actualizar datos locales
                    $userData['name'] = $name;
                    $userData['email'] = $email;
                    
                    $statusMessage = "Perfil actualizado correctamente.";
                } else {
                    $errorMessage = "Error al actualizar el perfil.";
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// Procesar formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    $activeTab = 'password';
    
    // Obtener valores del formulario
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validar datos
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "Todos los campos son obligatorios.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            // Verificar la contraseña actual
            $query = "SELECT password FROM usrs WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $userId]);
            $storedHashedPassword = $stmt->fetchColumn();
            
            if (!password_verify($currentPassword, $storedHashedPassword)) {
                $errorMessage = "La contraseña actual es incorrecta.";
            } else {
                // Actualizar la contraseña
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $query = "UPDATE usrs SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([
                    ':password' => $hashedNewPassword,
                    ':id' => $userId
                ]);
                
                if ($result) {
                    $statusMessage = "Contraseña actualizada correctamente.";
                } else {
                    $errorMessage = "Error al actualizar la contraseña.";
                }
            }
        } catch (PDOException $e) {
            $errorMessage = "Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}

// Variables para navbar.php
$userName = $userData['name'];
$userEmail = $userData['email'];
$userRole = ucfirst($userData['role']);
$isAdmin = ($userData['role'] === 'admin');
$basePath = '..';

// Incluir el header y la navbar
require_once '../navbar.php';
?>
<!-- Aquí el resto del código de la página de configuración -->
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
            <a href="../pages/profile.php" class="btn btn-secondary">Mi perfil</a>
            <a href="../pages/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
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
<footer class="footer">
    <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
</footer>
</html>