<?php
include '../../navbar.php';
require_once '../../db/db.php';

$error_message = "";
$success_message = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener la información de la solicitud de mantenimiento
    try {
        $db = Database::connect();
        $query = "SELECT * FROM maintenance_requests WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $error_message = "La solicitud de mantenimiento no existe o fue eliminada";
        }
    } catch (PDOException $e) {
        $error_message = "Error al obtener la solicitud: " . $e->getMessage();
    }

    // Verificar si se enviaron datos para actualizar la solicitud
    if (isset($_POST['description'], $_POST['status'], $_POST['priority'])) {
        // Obtener los datos del formulario
        $description = $_POST['description'];
        $status = $_POST['status'];
        $priority = $_POST['priority'];

        // Actualizar la solicitud de mantenimiento en la base de datos
        try {
            $query = "UPDATE maintenance_requests SET description = :description, status = :status, priority = :priority WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':id' => $id
            ]);

            if ($result) {
                $success_message = "Solicitud de mantenimiento actualizada con éxito";
                // Actualizar los datos de la solicitud después de guardar
                $query = "SELECT * FROM maintenance_requests WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $id]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Error al actualizar la solicitud";
            }
        } catch (PDOException $e) {
            $error_message = "Error al actualizar la solicitud: " . $e->getMessage();
        }
    }
} else {
    // Si no se pasa un id válido
    $error_message = "ID de solicitud no válido";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Solicitud de Mantenimiento</title>
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
    <h2>Editar Solicitud de Mantenimiento</h2>
    
    <?php if ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($request) && $request): ?>
    <form action="editMaintenance.php?id=<?= $id ?>" method="POST">
        <div class="form-group">
            <label for="description">Descripción:</label>
            <input type="text" name="description" value="<?= htmlspecialchars($request['description']) ?>" required>
        </div>

        <div class="form-group">
            <label for="status">Estado:</label>
            <input type="text" name="status" value="<?= htmlspecialchars($request['status']) ?>" required>
        </div>

        <div class="form-group">
            <label for="priority">Prioridad:</label>
            <select name="priority" required>
                <option value="baja" <?= $request['priority'] == 'baja' ? 'selected' : '' ?>>Baja</option>
                <option value="media" <?= $request['priority'] == 'media' ? 'selected' : '' ?>>Media</option>
                <option value="alta" <?= $request['priority'] == 'alta' ? 'selected' : '' ?>>Alta</option>
            </select>
        </div>

        <div class="button-container">
            <button type="submit">Actualizar Solicitud</button>
            <button type="button" class="button-cancel" onclick="window.location.href='mantenimiento.php'">Cancelar</button>
        </div>
    </form>
    <?php endif; ?>

    <script>
        // Si hay un mensaje de éxito, redirigir a la página de mantenimiento después de 2 segundos
        <?php if ($success_message): ?>
        setTimeout(function() {
            window.location.href = '../../pages/mantenimiento.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
