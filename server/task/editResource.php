<?php

require_once '../../db/db.php';

$error_message = "";
$success_message = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener la información del recurso
    try {
        $db = Database::connect();
        $query = "SELECT * FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resource) {
            $error_message = "El recurso no existe o fue eliminado";
        }
    } catch (PDOException $e) {
        $error_message = "Error al obtener el recurso: " . $e->getMessage();
    }

    // Verificar si se enviaron datos para actualizar el recurso
    if (isset($_POST['name'], $_POST['type'], $_POST['status'])) {
        // Obtener los datos del formulario
        $name = $_POST['name'];
        $type = $_POST['type'];
        $status = $_POST['status'];

        // Actualizar el recurso en la base de datos
        try {
            $query = "UPDATE resources SET name = :name, type = :type, status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':status' => $status,
                ':id' => $id
            ]);

            if ($result) {
                $success_message = "Recurso actualizado con éxito";
                // Actualizar los datos del recurso después de guardar
                $query = "SELECT * FROM resources WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $id]);
                $resource = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Error al actualizar el recurso";
            }
        } catch (PDOException $e) {
            $error_message = "Error al actualizar el recurso: " . $e->getMessage();
        }
    }
} else {
    // Si no se pasa un id válido
    $error_message = "ID de recurso no válido";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Recurso</title>
    <link rel="stylesheet" href="../../css/pages.css">
    <link rel="stylesheet" href="../../css/styles.css">
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background-color: #ffcccc;
            border: 1px solid #ff0000;
        }
        .success {
            background-color: #ccffcc;
            border: 1px solid #00cc00;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-cancel {
            background-color: #f44336;
        }
        .button-cancel:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <h2>Editar Recurso</h2>
    
    <?php if ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($resource) && $resource): ?>
    <form action="editResource.php?id=<?= $id ?>" method="POST">
        <div class="form-group">
            <label for="name">Nombre:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($resource['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="type">Tipo:</label>
            <input type="text" name="type" value="<?= htmlspecialchars($resource['type']) ?>" required>
        </div>

        <div class="form-group">
            <label for="status">Estado:</label>
            <input type="text" name="status" value="<?= htmlspecialchars($resource['status']) ?>" required>
        </div>

        <div class="button-container">
            <button type="submit">Actualizar Recurso</button>
            <button type="button" class="button-cancel"onclick="href='../../pages/recursos.php">Cancelar</button>
        </div>
    </form>
    <?php endif; ?>

    <script>
        // Si hay un mensaje de éxito, redirigir a la página de recursos después de 2 segundos
        <?php if ($success_message): ?>
        setTimeout(function() {
            window.location.href = '../../pages/recursos.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
