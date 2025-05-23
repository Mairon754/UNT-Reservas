<?php
require_once '../../db/db.php';
require_once '../../models/reserva.php';

$error_message = "";
$success_message = "";
$delete_success = "";

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Instanciar el modelo de Reserva
    $reservaModel = new Reserva();

    // Procesar eliminación de reserva si se envió el formulario de eliminación
    if (isset($_POST['delete_reservation'])) {
        try {
            // Verificar si el usuario es el creador de la reserva
            if (!$reservaModel->isUserOwner($id, $user_id)) {
                $error_message = "No tienes permiso para eliminar esta reserva";
            } else {
                $result = $reservaModel->deleteReserva($id, $user_id);
                
                if ($result) {
                    $delete_success = "La reserva ha sido eliminada correctamente";
                    // Redireccionar después de 2 segundos
                    header("refresh:2;url=../../pages/reservas.php");
                    exit();
                } else {
                    $error_message = "Error al eliminar la reserva";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error al eliminar la reserva: " . $e->getMessage();
        }
    }

    // Obtener la información de la reserva
    try {
        $reservation = $reservaModel->getReservaById($id);
        
        if (!$reservation) {
            // La reserva no existe
            $error_message = "La reserva no existe o fue eliminada";
        }
        
        // Verificar si el usuario actual es el creador de la reserva
        $canEdit = false;
        
        if ($reservation) {
            if (!isset($reservation['user_id']) || $reservation['user_id'] === null) {
                // Para reservas antiguas sin user_id, permitimos editar
                $canEdit = true;
            } else if ($reservation['user_id'] == $user_id) {
                $canEdit = true;
            } else if (isset($reservation['created_by']) && $reservation['created_by'] == $user_id) {
                $canEdit = true;
            }
            
            if (!$canEdit) {
                $error_message = "No tienes permiso para editar esta reserva";
            }
        }
        
        // Verificamos si existen las columnas end_date y end_time en la tabla
        $db = Database::connect();
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
        // Verificar si el usuario tiene permiso para editar
        if (!$canEdit) {
            $error_message = "No tienes permiso para editar esta reserva";
        } else {
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
                // Usar el modelo para actualizar la reserva
                $result = $reservaModel->updateReserva($id, $resource_id, $responsible_person, $reservation_date, $reservation_time, $user_id, $observations);

                if ($result) {
                    $success_message = "Reserva actualizada con éxito";
                    // Actualizar los datos de la reserva después de guardar
                    $reservation = $reservaModel->getReservaById($id);
                } else {
                    $error_message = "Error al actualizar la reserva o no tienes permiso para editarla";
                }
            } catch (PDOException $e) {
                $error_message = "Error al actualizar la reserva: " . $e->getMessage();
            }
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
<link rel="stylesheet" href="../../css/reservas.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="../../css/styles.css">
<script src="../../js/navbar-mobile.js"></script>


    <style>

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

        <?php if ($canEdit): ?>
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
                    <button type="button" class="button-delete" onclick="confirmDelete()">Eliminar Reserva</button>
                </div>
            </form>
        <?php else: ?>
            <div class="message error">No tienes permiso para editar esta reserva. Solo el creador puede editarla.</div>
            <button class="back-button" onclick="window.location.href='../../pages/reservas.php'">
                <i class="fas fa-arrow-left"></i> Volver al Calendario
            </button>
        <?php endif; ?>
        <?php else: ?>
            <div class="message error">No se encontró la reserva o no tiene permisos para verla.</div>
            <button class="back-button" onclick="window.location.href='../../pages/reservas.php'">
                <i class="fas fa-arrow-left"></i> Volver al Calendario
            </button>
        <?php endif; ?>
    </div>

    <script>
        // Función para confirmar y eliminar la reserva
        function confirmDelete() {
            if (confirm('¿Está seguro que desea eliminar esta reserva? Esta acción no se puede deshacer.')) {
                // Crear un formulario dinámicamente para enviar la solicitud de eliminación
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'editReservation.php?id=<?= $id ?>';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_reservation';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
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