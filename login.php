<?php
session_start();
require_once 'db/db.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit();
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir los datos del formulario
    $email = $_POST['email'] ?? ''; // Usamos el operador de fusión de null para evitar un error si no se ha enviado
    $password = $_POST['password'] ?? ''; // Lo mismo para la contraseña

    // Validar si el correo y la contraseña están definidos
    if (empty($email) || empty($password)) {
        $error_message = "Por favor, ingrese correo y contraseña.";
    } else {
        // Validar si el usuario existe
        try {
            $db = Database::connect();

            // Consulta para obtener el usuario y su rol
            $query = "SELECT u.id, u.email, u.name, u.password, r.role_name
                      FROM usrs u
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id
                      WHERE u.email = :email";

            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el correo y la contraseña son correctos
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user['name'];

                // Verificar si el rol es de administrador buscando en las tres tablas
                $isAdmin = false;

                // Verificar si el usuario tiene el rol 'admin' en user_roles
                $query = "SELECT 1 
                          FROM user_roles ur
                          JOIN roles r ON ur.role_id = r.id
                          WHERE ur.user_id = :user_id AND r.role_name = 'admin'";

                $stmt = $db->prepare($query);
                $stmt->execute([':user_id' => $user['id']]);
                
                if ($stmt->rowCount() > 0) {
                    $isAdmin = true; // El usuario es administrador
                }

                // Asignar el permiso de administrador a la sesión
                $_SESSION['is_admin'] = $isAdmin;

                // Redirigir al dashboard si es administrador o al área correspondiente
                header('Location: pages/dashboard.php');
                exit();
            } else {
                $error_message = "Correo o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error_message = "Error al conectar con la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/login.css">
<style>
    

    </style>
</head>
<body>
<div class="image-carousel">
    <div class="carousel-images">
        <img src="ass/1.png" alt="Imagen 1" class="carousel-item">
        <img src="ass/2.png" alt="Imagen 2" class="carousel-item">
        <img src="ass/3.png" alt="Imagen 3" class="carousel-item">
        <img src="ass/4.png" alt="Imagen 4" class="carousel-item">
        <img src="ass/5.png" alt="Imagen 5" class="carousel-item">
        <img src="ass/6.png" alt="Imagen 6" class="carousel-item">
    </div>
</div>
    
    <!-- Overlay para mejorar legibilidad -->
    <div class="overlay"></div>
    
    <div class="login-container">
        <div class="logo">
            <!-- Añadido el mismo logo que está en register.php -->
            <img src="ass/logo-login.png" alt="Logo">
        </div>
        <h2>Iniciar Sesión</h2>
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="email">Nombre de Usuario:</label>
            <input type="text" id="email" name="email" required placeholder="Ingresa tu nombre de usuario">
           
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required placeholder="Ingresa tu contraseña">
           
            <button type="submit">Iniciar Sesión</button>
        </form>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
    
</body>
</html>