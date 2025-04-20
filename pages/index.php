<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');  // Redirigir al login si no hay sesión
    exit();
}

$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir los datos del formulario
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validar si el usuario existe
    try {
        $db = Database::connect();
        $query = "SELECT * FROM usrs WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el correo y la contraseña son correctos
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            // Añadir bandera para identificar si es el usuario especial
            $_SESSION['is_admin'] = ($user['email'] === 'mairon@gmail.com') ? true : false;
            header('Location: ../pages/index.php');  // Redirige al dashboard
            exit();
        } else {
            $error_message = "Correo o contraseña incorrectos.";
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
    <title>Login</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>

        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <form action="../login.php" method="POST">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
