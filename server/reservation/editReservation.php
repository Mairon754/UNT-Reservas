<?php
require_once '../../db/db.php';

$error_message = "";
$success_message = "";
$delete_success = "";

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Procesar eliminación de reserva si se envió el formulario de eliminación
    if (isset($_POST['delete_reservation'])) {
        try {
            $db = Database::connect();
            $query = "DELETE FROM reservations WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([':id' => $id]);
            
            if ($result) {
                $delete_success = "La reserva ha sido eliminada correctamente";
                // Redireccionar después de 2 segundos
                header("refresh:2;url=../../pages/reservas.php");
                exit();
            } else {
                $error_message = "Error al eliminar la reserva";
            }
        } catch (PDOException $e) {
            $error_message = "Error al eliminar la reserva: " . $e->getMessage();
        }
    }

    // Obtener la información de la reserva
    try {
        $db = Database::connect();
        $query = "SELECT r.*, res.name as resource_name 
                 FROM reservations r 
                 LEFT JOIN resources res ON r.resource_id = res.id 
                 WHERE r.id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            $error_message = "La reserva no existe o fue eliminada";
        }
        
        // Verificamos si existen las columnas end_date y end_time en la tabla
        $query = "SELECT column_name FROM information_schema.columns 
                 WHERE table_name = 'reservations' AND column_name = 'end_date'";
        $stmt = $db->query($query);
        $endDateExists = $stmt->rowCount() > 0;
        
        $query = "SELECT column_name FROM information_schema.columns 
                 WHERE table_name = 'reservations' AND column_name = 'end_time'";
        $stmt = $db->query($query);
        $endTimeExists = $stmt->rowCount() > 0;
        
        // Si los campos no existen, alteramos la tabla para crearlos
        if (!$endDateExists) {
            $db->exec("ALTER TABLE reservations ADD COLUMN end_date DATE");
            // Actualizamos la reserva actual con valor predeterminado
            $db->exec("UPDATE reservations SET end_date = reservation_date WHERE id = $id");
        }
        
        if (!$endTimeExists) {
            $db->exec("ALTER TABLE reservations ADD COLUMN end_time TIME");
            // Actualizamos la reserva actual con valor predeterminado
            $db->exec("UPDATE reservations SET end_time = reservation_time WHERE id = $id");
        }
        
        // Volvemos a obtener la reserva con las columnas nuevas
        $query = "SELECT r.*, res.name as resource_name 
                 FROM reservations r 
                 LEFT JOIN resources res ON r.resource_id = res.id 
                 WHERE r.id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurarnos de que todos los campos necesarios existan
        if (!isset($reservation['end_date']) || $reservation['end_date'] == '') {
            $reservation['end_date'] = $reservation['reservation_date'];
        }
        
        if (!isset($reservation['end_time']) || $reservation['end_time'] == '') {
            $reservation['end_time'] = $reservation['reservation_time'];
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
        
        // Nuevos campos de fecha y hora de finalización
        $end_date = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : $reservation_date;
        $end_time = isset($_POST['end_time']) && !empty($_POST['end_time']) ? $_POST['end_time'] : $reservation_time;
        
        // Observaciones (opcional)
        $observations = isset($_POST['observations']) ? $_POST['observations'] : '';

        // Actualizar la reserva en la base de datos
        try {
            // Ahora actualizamos la reserva con todos los campos
            $query = "UPDATE reservations SET 
                      resource_id = :resource_id, 
                      responsible_person = :responsible_person,
                      reservation_date = :reservation_date, 
                      reservation_time = :reservation_time,
                      end_date = :end_date,
                      end_time = :end_time,
                      observations = :observations 
                      WHERE id = :id";
                      
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':resource_id' => $resource_id,
                ':responsible_person' => $responsible_person,
                ':reservation_date' => $reservation_date,
                ':reservation_time' => $reservation_time,
                ':end_date' => $end_date,
                ':end_time' => $end_time,
                ':observations' => $observations,
                ':id' => $id
            ]);

            if ($result) {
                $success_message = "Reserva actualizada con éxito";
                // Actualizar los datos de la reserva después de guardar
                $query = "SELECT r.*, res.name as resource_name 
                         FROM reservations r 
                         LEFT JOIN resources res ON r.resource_id = res.id 
                         WHERE r.id = :id";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .delete-success {
            background-color: #ffecb3;
            border: 1px solid #ff9800;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
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
            border-radius: 5px;
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
        .button-delete {
            background-color: #ff9800;
        }
        .button-delete:hover {
            background-color: #f57c00;
        }
        .delete-confirmation {
            display: none;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .delete-confirmation p {
            margin-bottom: 15px;
            color: #856404;
        }
        .delete-confirmation .button-container {
            justify-content: center;
            gap: 10px;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-button {
            background-color: #607d8b;
            padding: 8px 12px;
            font-size: 14px;
        }
        .back-button:hover {
            background-color: #455a64;
        }
        .reservation-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .reservation-details h3 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h2>Editar Reserva</h2>
            <button class="back-button" onclick="window.location.href='../../pages/reservas.php'">
                <i class="fas fa-arrow-left"></i> Volver al Calendario
            </button>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($delete_success): ?>
            <div class="message delete-success"><?php echo $delete_success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($reservation) && $reservation): ?>
        <!-- Mostrar detalles de la reserva -->
        <div class="reservation-details">
            <h3>Detalles de la Reserva #<?= $id ?></h3>
            <p><strong>Recurso:</strong> <?= $reservation['resource_name'] ?></p>
            <p><strong>Responsable:</strong> <?= htmlspecialchars($reservation['responsible_person']) ?></p>
            <p><strong>Fecha y hora de inicio:</strong> <?= $reservation['reservation_date'] ?> a las <?= $reservation['reservation_time'] ?></p>
            <p><strong>Fecha y hora de fin:</strong> 
                <?= isset($reservation['end_date']) ? $reservation['end_date'] : $reservation['reservation_date'] ?> 
                a las 
                <?= isset($reservation['end_time']) ? $reservation['end_time'] : $reservation['reservation_time'] ?>
            </p>
            <?php if (!empty($reservation['observations'])): ?>
                <p><strong>Observaciones:</strong> <?= htmlspecialchars($reservation['observations']) ?></p>
            <?php endif; ?>
        </div>

        <form action="editReservation.php?id=<?= $id ?>" method="POST">
            <div class="form-group">
                <label for="resource_id">Recurso:</label>
                <select name="resource_id" id="resource_id" required>
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?= $resource['id'] ?>" <?= $reservation['resource_id'] == $resource['id'] ? 'selected' : '' ?>>
                            <?= $resource['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="responsible_person">Responsable:</label>
                <input type="text" name="responsible_person" id="responsible_person" value="<?= htmlspecialchars($reservation['responsible_person']) ?>" required>
            </div>

            <div class="form-group">
                <label for="reservation_date">Fecha de inicio:</label>
                <input type="date" name="reservation_date" id="reservation_date" value="<?= $reservation['reservation_date'] ?>" required>
            </div>

            <div class="form-group">
                <label for="reservation_time">Hora de inicio:</label>
                <input type="time" name="reservation_time" id="reservation_time" value="<?= $reservation['reservation_time'] ?>" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">Fecha de fin:</label>
                <input type="date" name="end_date" id="end_date" value="<?= isset($reservation['end_date']) && !empty($reservation['end_date']) ? $reservation['end_date'] : $reservation['reservation_date'] ?>">
            </div>
            
            <div class="form-group">
                <label for="end_time">Hora de fin:</label>
                <input type="time" name="end_time" id="end_time" value="<?= isset($reservation['end_time']) && !empty($reservation['end_time']) ? $reservation['end_time'] : $reservation['reservation_time'] ?>">
            </div>
            
            <div class="form-group">
                <label for="observations">Observaciones:</label>
                <textarea name="observations" id="observations" rows="3"><?= isset($reservation['observations']) ? htmlspecialchars($reservation['observations']) : '' ?></textarea>
            </div>

            <div class="button-container">
                <button type="submit" class="btn-primary">Guardar Cambios</button>
                <button type="button" class="button-cancel" onclick="window.location.href='../../pages/reservas.php'">Cancelar</button>
                <button type="button" class="button-delete" onclick="toggleDeleteConfirmation()">Eliminar Reserva</button>
            </div>
        </form>
        
        <!-- Confirmación de eliminación -->
        <div id="delete-confirmation" class="delete-confirmation">
            <p><i class="fas fa-exclamation-triangle"></i> ¿Está seguro que desea eliminar esta reserva? Esta acción no se puede deshacer.</p>
            <div class="button-container">
                <form action="editReservation.php?id=<?= $id ?>" method="POST">
                    <input type="hidden" name="delete_reservation" value="1">
                    <button type="submit" class="button-delete">Sí, Eliminar</button>
                </form>
                <button type="button" class="btn-primary" onclick="toggleDeleteConfirmation()">Cancelar</button>
            </div>
        </div>
        <?php else: ?>
            <div class="message error">No se encontró la reserva o no tiene permisos para verla.</div>
            <button class="back-button" onclick="window.location.href='../../pages/reservas.php'">
                <i class="fas fa-arrow-left"></i> Volver al Calendario
            </button>
        <?php endif; ?>
    </div>

    <script>
        // Mostrar/ocultar confirmación de eliminación
        function toggleDeleteConfirmation() {
            const confirmationDiv = document.getElementById('delete-confirmation');
            if (confirmationDiv.style.display === 'block') {
                confirmationDiv.style.display = 'none';
            } else {
                confirmationDiv.style.display = 'block';
            }
        }
        
        // Validar fechas al enviar el formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    const startDate = new Date(document.getElementById('reservation_date').value + 'T' + document.getElementById('reservation_time').value);
                    const endDate = new Date(document.getElementById('end_date').value + 'T' + document.getElementById('end_time').value);
                    
                    if (endDate < startDate) {
                        e.preventDefault();
                        alert('La fecha y hora de fin debe ser posterior a la fecha y hora de inicio');
                    }
                });
            }
            
            // Vincular fecha inicio con fecha fin
            const fechaInicio = document.getElementById('reservation_date');
            const fechaFin = document.getElementById('end_date');
            
            if (fechaInicio && fechaFin) {
                fechaInicio.addEventListener('change', function() {
                    // Si la fecha fin está vacía o es anterior a la fecha inicio, actualizar
                    if (!fechaFin.value || new Date(fechaFin.value) < new Date(this.value)) {
                        fechaFin.value = this.value;
                    }
                });
            }
        });
        
        // Si hay un mensaje de éxito, redirigir a la página de reservas después de 2 segundos
        <?php if ($success_message): ?>
        setTimeout(function() {
            window.location.href = '../../pages/reservas.php';
        }, 2000);
        <?php endif; ?>
    </script>
    
    <footer class="footer">
        <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>
</body>
</html>