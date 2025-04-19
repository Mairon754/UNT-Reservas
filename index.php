<?php
// index.php: Dashboard principal de UNTGestión

// Incluir la conexión a la base de datos
require_once 'db/db.php';

// Función para obtener el número total de recursos
function getTotalResources() {
    $db = Database::connect();
    $query = "SELECT COUNT(*) AS total FROM resources";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'];
}

// Función para obtener el número de recursos disponibles
function getAvailableResources() {
    $db = Database::connect();
    $query = "SELECT COUNT(*) AS available FROM resources WHERE status = 'disponible'";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['available'];
}

// Función para obtener el número de reservas activas
function getActiveReservations() {
    $db = Database::connect();
    $query = "SELECT COUNT(*) AS active FROM reservations WHERE reservation_date >= CURRENT_DATE";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['active'];
}

// Función para obtener el número de mantenimientos pendientes
function getPendingMaintenance() {
    $db = Database::connect();
    $query = "SELECT COUNT(*) AS pending FROM maintenance_requests WHERE status IN ('reportado', 'en proceso')";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['pending'];
}

// Obtener las estadísticas de la base de datos
$totalResources = getTotalResources();
$availableResources = getAvailableResources();
$activeReservations = getActiveReservations();
$pendingMaintenance = getPendingMaintenance();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNTGestión - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/app.js" defer></script>
</head>

<body>
    <header>
        <div class="logo">
            <img src="ass/logo.png" alt="UNTGestión Logo">
        </div>
        <nav>
            <ul>
                <li><a href="pages/dashboard.php">Dashboard</a></li>
                <li><a href="pages/recursos.php">Recursos</a></li>
                <li><a href="pages/reservas.php">Reservas</a></li>
                <li><a href="pages/mantenimiento.php">Mantenimiento</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Dashboard</h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total de Recursos</h3>
                <p><?php echo $totalResources; ?></p>
            </div>
            <div class="stat-card">
                <h3>Recursos Disponibles</h3>
                <p><?php echo $availableResources; ?></p>
            </div>
            <div class="stat-card">
                <h3>Reservas Activas</h3>
                <p><?php echo $activeReservations; ?></p>
            </div>
            <div class="stat-card">
                <h3>Mantenimientos Pendientes</h3>
                <p><?php echo $pendingMaintenance; ?></p>
            </div>
        </div>

        <div class="resources">
            <h2>Recursos por Tipo</h2>
            <div class="resource-cards">
                <div class="resource-card">
                    <h3>Aulas</h3>
                    <p>3</p>
                </div>
                <div class="resource-card">
                    <h3>Proyectores</h3>
                    <p>1</p>
                </div>
                <div class="resource-card">
                    <h3>Portátiles</h3>
                    <p>1</p>
                </div>
                <div class="resource-card">
                    <h3>Otros</h3>
                    <p>0</p>
                </div>
            </div>
        </div>

        <div class="upcoming-reservations">
            <h2>Próximas Reservas</h2>
            <p>No hay reservas próximas.</p>
        </div>

        <div class="maintenance">
            <h2>Mantenimientos Recientes</h2>
            <div class="maintenance-card">
                <h3>Laptop HP ProBook</h3>
                <p>La batería no retiene carga</p>
                <p><strong>Estado:</strong> En proceso</p>
                <p><strong>Prioridad:</strong> Alta</p>
            </div>
            <div class="maintenance-card">
                <h3>Proyector EPSON X41</h3>
                <p>La bombilla del proyector parpadea</p>
                <p><strong>Estado:</strong> Reportado</p>
                <p><strong>Prioridad:</strong> Media</p>
            </div>
            <div class="maintenance-card">
                <h3>Aula 102</h3>
                <p>El aire acondicionado no enfría correctamente</p>
                <p><strong>Estado:</strong> Resuelto</p>
                <p><strong>Prioridad:</strong> Baja</p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 UNTGestión. Todos los derechos reservados.</p>
    </footer>
</body>

</html>
