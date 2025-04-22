<?php

require_once '../../db/db.php';

$error_message = "";
$success_message = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener la información de la reserva
    try {
        $db = Database::connect();
        $query = "SELECT * FROM reservations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            $error_message = "La reserva no existe o fue eliminada";
        }
    } catch (PDOException $e) {
        $error_message = "Error al obtener la reserva: " . $e->getMessage();
    }

    // Verificar si se enviaron datos para actualizar la reserva
    if (isset($_POST['resource_id'], $_POST['responsible_person'], $_POST['reservation_date'], $_POST['reservation_time'])) {
        // Obtener los datos del formulario
        $resource_id = $_POST['resource_id'];
        $responsible_person = $_POST['responsible_person'];
        $reservation_date = $_POST['reservation_date'];
        $reservation_time = $_POST['reservation_time'];

        // Actualizar la reserva en la base de datos
        try {
            $query = "UPDATE reservations SET resource_id = :resource_id, responsible_person = :responsible_person,
                      reservation_date = :reservation_date, reservation_time = :reservation_time WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':resource_id' => $resource_id,
                ':responsible_person' => $responsible_person,
                ':reservation_date' => $reservation_date,
                ':reservation_time' => $reservation_time,
                ':id' => $id
            ]);

            if ($result) {
                $success_message = "Reserva actualizada con éxito";
                // Actualizar los datos de la reserva después de guardar
                $query = "SELECT * FROM reservations WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $id]);
                $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Error al actualizar la reserva";
            }
        } catch (PDOException $e) {
            $error_message = "Error al actualizar la reserva: " . $e->getMessage();
        }
    }
} else {
    // Si no se pasa un id válido
    $error_message = "ID de reserva no válido";
}

// Obtener los recursos disponibles
try {
    $db = Database::connect();
    $query = "SELECT id, name FROM resources";
    $stmt = $db->query($query);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error al obtener los recursos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reserva</title>
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
    <h2>Editar Reserva</h2>
    
    <?php if ($error_message): ?>
        <div class="message error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="message success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($reservation) && $reservation): ?>
    <form action="editReservation.php?id=<?= $id ?>" method="POST">
        <div class="form-group">
            <label for="resource_id">Recurso:</label>
            <select name="resource_id" required>
                <?php foreach ($resources as $resource): ?>
                    <option value="<?= $resource['id'] ?>" <?= $reservation['resource_id'] == $resource['id'] ? 'selected' : '' ?>>
                        <?= $resource['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="responsible_person">Responsable:</label>
            <input type="text" name="responsible_person" value="<?= htmlspecialchars($reservation['responsible_person']) ?>" required>
        </div>

        <div class="form-group">
            <label for="reservation_date">Fecha:</label>
            <input type="date" name="reservation_date" value="<?= $reservation['reservation_date'] ?>" required>
        </div>

        <div class="form-group">
            <label for="reservation_time">Hora:</label>
            <input type="time" name="reservation_time" value="<?= $reservation['reservation_time'] ?>" required>
        </div>

        <div class="button-container">
            <button type="submit">Actualizar Reserva</button>
            <button type="button" class="button-cancel" onclick="window.location.href='../../pages/recursos.php'">Cancelar</button>
        </div>
    </form>
    <?php endif; ?>

    <script>
        // Si hay un mensaje de éxito, redirigir a la página de reservas después de 2 segundos
        <?php if ($success_message): ?>
        setTimeout(function() {
            window.location.href = '../../pages/reservas.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>