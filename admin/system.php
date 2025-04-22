<?php
// admin/system.php
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

// Manejo del formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = $_POST['system_name'];
    $system_email = $_POST['system_email'];

    try {
        $db = Database::connect();
        
        // Actualizar configuración en la base de datos (ejemplo con datos del sistema)
        $query = "UPDATE system_settings SET name = :name, email = :email WHERE id = 1";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([':name' => $system_name, ':email' => $system_email]);

        if ($result) {
            $success_message = "Configuración actualizada con éxito";
        } else {
            $error_message = "Error al actualizar la configuración";
        }
    } catch (PDOException $e) {
        $error_message = "Error al conectar con la base de datos: " . $e->getMessage();
    }
}

// Obtener configuración actual
try {
    $db = Database::connect();
    $query = "SELECT * FROM system_settings WHERE id = 1";
    $stmt = $db->query($query);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al obtener la configuración: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <h2>Configuración del Sistema</h2>
    </header>
    
    <div class="container">
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>
        
        <form action="system.php" method="POST">
            <div>
                <label for="system_name">Nombre del Sistema:</label>
                <input type="text" name="system_name" id="system_name" value="<?= htmlspecialchars($settings['name']) ?>" required>
            </div>
            <div>
                <label for="system_email">Correo del Sistema:</label>
                <input type="email" name="system_email" id="system_email" value="<?= htmlspecialchars($settings['email']) ?>" required>
            </div>
            <div>
                <button type="submit">Actualizar Configuración</button>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2025 Sistema de Gestión</p>
    </footer>
</body>
</html>
