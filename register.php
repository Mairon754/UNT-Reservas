<?php
require_once 'db/db.php';
$error_message = "";
$success_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir los datos del formulario
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    // Validar si el correo ya está registrado
    try {
        $db = Database::connect();
        $query = "SELECT * FROM usrs WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $error_message = "Este correo ya está registrado.";
        } else {
            // Cifrar la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Insertar el nuevo usuario en la base de datos
            $query = "INSERT INTO usrs (email, password, name) VALUES (:email, :password, :name)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':email' => $email,
                ':password' => $hashedPassword,
                ':name' => $name
            ]);
            $success_message = "Usuario registrado con éxito. Ahora puedes iniciar sesión.";
        }
    } catch (PDOException $e) {
        $error_message = "Error al conectar con la base de datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro de Usuario</title>
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/register.css">
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
    
    <div class="register-container">
        <div class="logo">
            <!-- Logo existente -->
            <img src="ass/logo-login.png" alt="Logo">
        </div>
        
        <h2>Registro de Nuevo Usuario</h2>
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="name">Nombre:</label>
            <input type="text" id="name" name="name" required placeholder="Tu nombre completo">
            
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required placeholder="nombre@ejemplo.com">
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required placeholder="Crea una contraseña segura">
            
            <button type="submit">Registrarse</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
    

</body>
</html>