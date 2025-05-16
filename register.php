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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            background-color: #1a1a1a; /* Color de fondo por si hay espacios sin cubrir */
        }
        
        
        /* Para pantallas muy anchas o muy altas */
        @media (min-aspect-ratio: 16/9) {
            .video-background video {
                height: 300%; /* Exageración deliberada para asegurar cobertura */
                width: 100%;
            }
        }
        
        @media (max-aspect-ratio: 16/9) {
            .video-background video {
                width: 300%; /* Exageración deliberada para asegurar cobertura */
                height: 100%;
            }
        }
        
        /* Overlay mejorado con gradiente para mejor visibilidad */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                rgba(0, 0, 0, 0.5), 
                rgba(0, 0, 0, 0.7)
            );
            z-index: 0;
        }
        
        /* Contenedor más elegante y con mejor contraste */
        .register-container {
            background-color: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px); /* Para Safari */
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            padding: 40px;
            z-index: 2;
            transition: all 0.3s ease;
            position: relative; /* Para posicionamiento correcto en diferentes pantallas */
        }
        
        .register-container:hover {
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            transform: translateY(-5px); /* Efecto sutil de elevación al pasar el cursor */
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 8px;
            color: #444;
            font-weight: 500;
        }
        
        input {
            padding: 12px 15px;
            margin-bottom: 20px;
            border: none;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #27ae60; /* Verde para el formulario de registro */
            transform: translateY(-2px); /* Efecto sutil al enfocar */
        }
        
        button {
            padding: 12px;
            background-color: #27ae60; /* Verde para el botón de registro */
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #219653;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        p {
            text-align: center;
            margin-top: 25px;
            color: #555;
        }
        
        a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.8);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .success-message {
            background-color: rgba(46, 204, 113, 0.8);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            max-width: 200px;
            height: auto;
            transition: transform 0.3s;
        }
        
        .logo img:hover {
            transform: scale(1.05);
        }

        /* Optimización para dispositivos móviles */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
                width: 85%;
            }
        
        /* Optimización para tablets */
        @media (min-width: 481px) and (max-width: 1024px) {
            .video-background video {
                position: fixed;
                width: 100%;
                height: auto;
            }
        }
        

        /* Estilos generales del carrusel */
        .image-carousel {
            position: relative;
            width: 100%;
            height: 100vh; /* Ocupa toda la altura de la pantalla */
            overflow: hidden; /* Esconde las imágenes que salen del área visible */
        }

        .carousel-images {
            display: flex;
            width: 600%; /* El ancho total es 6 veces el tamaño de una imagen */
            animation: slide 30s infinite linear; /* 6 imágenes × 5 segundos = 30s total */
        }

        .carousel-item {
            width: 16.666%; /* Cada imagen ocupa 1/6 del contenedor (100% ÷ 6) */
            height: 100vh; /* Cada imagen ocupa toda la altura de la pantalla */
            object-fit: cover; /* Asegura que la imagen cubra todo el área del contenedor */
        }

        /* Animación del carrusel */
        @keyframes slide {
            0%, 16.66% {
                transform: translateX(0);
            }
            16.67%, 33.33% {
                transform: translateX(-16.666%);
            }
            33.34%, 50% {
                transform: translateX(-33.332%);
            }
            50.01%, 66.66% {
                transform: translateX(-49.998%);
            }
            66.67%, 83.33% {
                transform: translateX(-66.664%);
            }
            83.34%, 99.99% {
                transform: translateX(-83.33%);
            }
            100% {
                transform: translateX(0);
            }
        }
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