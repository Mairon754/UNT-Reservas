<?php
// reservas.php

require_once '../db/db.php';
require_once '../models/recursos.php';
require_once '../models/reserva.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Instanciar las clases necesarias
$recursoModel = new Recurso();
$reservaModel = new Reserva();

// Obtener todos los recursos disponibles para el dropdown
$resources = $recursoModel->getAllRecursos();

// Obtener todas las reservas para mostrar en el calendario
try {
    // Usar el modelo de reserva para obtener las reservas
    $reservations = $reservaModel->getAllReservas();
    
    // Convertir las reservas a formato JSON para usarlas en JS
    $reservationsJson = json_encode($reservations);
} catch (PDOException $e) {
    die("Error al obtener las reservas: " . $e->getMessage());
}

// Obtener el mes y año actual para el calendario
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Nombre del mes en español
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombreMes = $meses[$month];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reservas</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/reservas.css">  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include '../navbar.php'; ?>
    
    <div class="container">
        <h1>Sistema de Reservas</h1>
        
        <div class="calendar-container">
            <div class="calendar">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <button id="prev-month"><i class="fas fa-chevron-left"></i></button>
                        <h2 class="calendar-title"><?php echo $nombreMes . ' ' . $year; ?></h2>
                        <button id="next-month"><i class="fas fa-chevron-right"></i></button>
                        <button id="today">Hoy</button>
                    </div>
                    <div class="calendar-view-options">
                        <button id="month-view" class="active">Mes</button>
                        <button id="week-view">Semana</button>
                        <button id="day-view">Día</button>
                    </div>
                </div>
                
                <div id="calendar-grid" class="calendar-grid">
                    <div class="calendar-day-header">Dom</div>
                    <div class="calendar-day-header">Lun</div>
                    <div class="calendar-day-header">Mar</div>
                    <div class="calendar-day-header">Mié</div>
                    <div class="calendar-day-header">Jue</div>
                    <div class="calendar-day-header">Vie</div>
                    <div class="calendar-day-header">Sáb</div>
                    
                    <!-- Los días del calendario se generarán con JavaScript -->
                </div>
            </div>
            
            <div class="reservation-form">
                <h2>Nueva Reserva</h2>
                <!-- Formulario -->
                <form id="reserva-form" method="post" action="../server/reservation/createReservation.php">
                    <!-- Selección de recurso -->
                    <div class="form-group">
                        <label for="recurso_id">Seleccionar Recurso</label>
                        <select class="form-control" id="recurso_id" name="resource_id" required>
                            <option value="">Por favor selecciona un recurso</option>
                            <?php foreach ($resources as $resource): ?>
                                <option value="<?php echo $resource['id']; ?>"><?php echo $resource['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Nombre de la persona responsable -->
                    <div class="form-group">
                        <label for="nombre_reserva">Reserva a Nombre de:</label>
                        <input type="text" class="form-control" id="nombre_reserva" name="responsible_person" required>
                    </div>

                    <!-- Fecha y hora de inicio de la reserva -->
                    <div class="form-group">
                        <label for="fecha_inicio">Desde:</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="reservation_date" required>
                        <input type="time" class="form-control" id="hora_inicio" name="reservation_time" required>
                    </div>

                    <!-- Fecha y hora de fin de la reserva (nuevo campo) -->
                    <div class="form-group">
                        <label for="fecha_fin">Hasta:</label>
                        <input type="date" class="form-control" id="fecha_fin" name="end_date" required>
                        <input type="time" class="form-control" id="hora_fin" name="end_time" required>
                    </div>

                    <!-- Observaciones -->
                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea class="form-control" id="observaciones" name="observations" rows="3"></textarea>
                    </div>

                    <!-- Botones -->
                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">Guardar</button>
                        <button type="reset" class="btn-secondary">Resetear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>

    <!-- Modal para mostrar errores de validación -->
    <div id="error-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Error</h3>
            <p id="error-message"></p>
        </div>
    </div>
    
    <script>
        // Pasar datos de PHP a JavaScript
        const currentMonth = <?php echo $month; ?>;
        const currentYear = <?php echo $year; ?>;
        const reservations = <?php echo json_encode($reservations ?: []); ?>;

        
        // Debugging: Verificar si los datos se están cargando correctamente
        console.log('Datos cargados en reservas.php:');
        console.log('Month:', currentMonth);
        console.log('Year:', currentYear);
        console.log('Reservations:', reservations);
    </script>
    <script src="../js/calendar.js"></script>
</body>
</html>