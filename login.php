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
        .login-container {
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
        
        .login-container:hover {
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
            box-shadow: 0 0 0 2px #3498db;
            transform: translateY(-2px); /* Efecto sutil al enfocar */
        }
        
        button {
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #2980b9;
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            max-width: 200px; /* Ajustado para que coincida con el tamaño en register.php */
            height: auto;
            transition: transform 0.3s;
        }
        
        .logo img:hover {
            transform: scale(1.05);
        }

        /* Optimización para dispositivos móviles */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                width: 85%;
            }
            
            /* Ajustar posición del video en móviles */
            .video-background video {
                position: fixed;
                left: 50%;
                transform: translateX(-50%);
                width: auto;
                height: 100%;
            }
        }
        
        /* Optimización para tablets */
        @media (min-width: 481px) and (max-width: 1024px) {
            .video-background video {
                position: fixed;
                width: 100%;
                height: auto;
            }
        }
        
        /* Soporte para navegadores más antiguos o dispositivos que no soportan video */
        @media (prefers-reduced-motion: reduce), (max-width: 360px) {
            .video-background {
                background-image: url('ass/fondo-login.png');
                background-size: cover;
                background-position: center;
            }
            
            .video-background video {
                display: none;
            }
        }
        
        /* Estilos generales del carrusel */
        .image-carousel {
            position: absolute;
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