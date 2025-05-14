<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener datos del usuario
$userName = $_SESSION['name'] ?? 'Usuario';
$userEmail = $_SESSION['email'] ?? '';

// Usar is_admin desde la sesión para determinar si es administrador
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Obtener rol del usuario desde la base de datos
require_once dirname(__FILE__) . '/db/db.php';
try {
    $db = Database::connect();
    $query = "SELECT r.role_name 
              FROM user_roles ur 
              JOIN roles r ON ur.role_id = r.id 
              WHERE ur.user_id = :user_id
              ORDER BY CASE WHEN r.role_name = 'admin' THEN 0 ELSE 1 END";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si no tiene roles asignados, establecer como usuario por defecto
    if (empty($roles)) {
        $userRole = 'Usuario';
    } else {
        // Usar el primer rol (el más importante según el ORDER BY)
        $userRole = ucfirst($roles[0]);
    }
} catch (PDOException $e) {
    // Si hay un error en la consulta, usar un valor predeterminado
    $userRole = $isAdmin ? 'Administrador' : 'Usuario';
}

// Determinar la ruta base para los enlaces
$basePath = '';
if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $basePath = '.';
} else {
    $basePath = './pages';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/pages.css">
    <style>
        /* Estilos para la barra de navegación */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #003366;
            color: white;
            padding: 0px 20px;
            box-shadow: 0 2px 4px rgba(12, 58, 241, 0.76);
            min-height: 70px;
        }

        .nav-links {
            display: flex;
            flex: 1; /* Esto hará que ocupe todo el espacio disponible */
            gap: 20px;
            
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8 15px;
            height: 70px;        /* Misma altura que navbar min-height */
            display: flex;
            align-items: center;  /* Centra el texto verticalmente */
            transition: background-color 0.3s;
            font-weight: bold;
        }

        .nav-links a:hover {
            background-color: #555;
        }

        /* Perfil de usuario en la navbar */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color:rgb(232, 72, 9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            margin-right: 10px;
        }

        .user-info span {
            font-size: 14px;
        }

        .user-info a {
            color:rgb(11, 239, 22);
            text-decoration: none;
            font-size: 15px;
            margin-top: 3px;
        }

        .user-info a:hover {
            text-decoration: underline;
        }

        .logo-image {
            width: 100px;  /* o el tamaño que desee */
            height: auto;  /* mantiene la proporción */
        }
        
        .logo {
            margin-right: 20px; /* Espacio entre logo y enlaces */
        }
        
        /* Estilos para el dropdown del usuario */
        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 60%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            font-size: 22px;
        }

        .dropdown-menu {
            position: absolute;
            top: 45px;
            right: 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-width: 200px;
            z-index: 1000;
            display: none; /* Inicialmente oculto */
        }

        .dropdown-menu.show {
            display: block; /* Se muestra cuando tiene la clase 'show' */
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background-color: #3498db;
        }

        .dropdown-header strong {
            display: block;
            font-size: 16px;
            color: #ffe;
            font-weight: bold;
        }

        .dropdown-header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #fff;
            font-weight: bold;
        }

        .user-role {
            color: #e8f4fc;
            font-weight: 500;
            margin-top: 5px;
            background-color: #2580b3;
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
            font-size: 12px;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #ffe;
            margin: 0;
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .user-welcome {
            font-size: 22px;
            color: white;
            font-weight: bold;
            margin-right: 5px;
        }

        /* Media Query para la barra de navegación en pantallas pequeñas */
        @media (max-width: 600px) {
            .navbar {
                flex-direction: column; /* Poner los elementos en columna */
                align-items: center;
            }

            .navbar .user-info {
                margin-bottom: 10px; /* Separar la información del usuario */
            }

            .user-avatar {
                font-size: 18px; /* Aumentar el tamaño de la letra en el avatar */
            }
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarToggle = document.getElementById('avatar-dropdown-toggle');
        const dropdownMenu = document.getElementById('user-dropdown-menu');

        // Mostrar/ocultar el menú al hacer clic en el avatar
        avatarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });
        
        // Cerrar el menú si se hace clic fuera de él
        document.addEventListener('click', function(e) {
            if (!avatarToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    });
    </script>
</head>
<body>
    <div class="navbar">
        <div class="nav-links">
            <div class="logo">
                <img src="../ass/logoUNT.png" class="logo-image">
            </div>
            <a href="<?= $basePath ?>/dashboard.php">Dashboard</a>
            <?php if ($isAdmin): ?>
            <a href="<?= $basePath ?>/recursos.php">Recursos</a>
            <?php endif; ?>
            <a href="<?= $basePath ?>/reservas.php">Reservas</a>
            <a href="<?= $basePath ?>/mantenimiento.php">Mantenimiento</a>
            <?php if ($isAdmin): ?>
            <a href="/project-UNT-reservas/admin/users.php">Usuarios</a>
            <?php endif; ?>
        </div>
        <br><br>
        <div class="user-info">
            <span class="user-welcome">Bienvenido, <?php echo $userName; ?>!</span>
        </div>
        <br><br>
        <div class="user-profile">
            <div class="user-avatar" id="avatar-dropdown-toggle">
                <span><?= substr($userName, 0, 1) ?></span>
            </div>
            <div class="dropdown-menu" id="user-dropdown-menu">
                <div class="dropdown-header">
                    <strong><?= htmlspecialchars($userName) ?></strong>
                    <p><?= htmlspecialchars($userEmail) ?></p>
                    <p class="user-role"><?= htmlspecialchars($userRole) ?></p>
                </div>
                <div class="dropdown-divider"></div>
                <a href="<?= $basePath ?>/profile.php">Mi Perfil</a>
                <a href="<?= $basePath ?>/settings.php">Configuración</a>
                <a href="<?= $basePath ?>/logout.php">Cerrar sesión</a>
            </div>
        </div>
    </div>
</body>
</html>