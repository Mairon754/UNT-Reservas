<?php
require_once '../navbar.php';
require_once '../db/db.php';

try {
    // Conectar a la base de datos
    $db = Database::connect();

    // Consultar el total de recursos
    $queryTotalResources = "SELECT COUNT(*) AS total FROM resources";
    $stmt = $db->query($queryTotalResources);
    $totalResources = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consultar el total de recursos disponibles
    $queryAvailableResources = "SELECT COUNT(*) AS total FROM resources WHERE status = 'disponible'";
    $stmt = $db->query($queryAvailableResources);
    $availableResources = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consultar el total de reservas activas
    $queryActiveReservations = "SELECT COUNT(*) AS total FROM reservations WHERE reservation_date >= CURRENT_DATE";
    $stmt = $db->query($queryActiveReservations);
    $activeReservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consultar el total de mantenimientos pendientes
    $queryPendingMaintenance = "SELECT COUNT(*) AS total FROM maintenance_requests WHERE status != 'resuelto'";
    $stmt = $db->query($queryPendingMaintenance);
    $pendingMaintenance = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consultar las próximas reservas (que son mayores o iguales a la fecha actual)
    $queryUpcomingReservations = "SELECT r.id, res.name AS resource_name, r.responsible_person, r.reservation_date, r.reservation_time
                                  FROM reservations r
                                  JOIN resources res ON r.resource_id = res.id
                                  WHERE r.reservation_date >= CURRENT_DATE
                                  ORDER BY r.reservation_date ASC
                                  LIMIT 5";  // Limitar las próximas 5 reservas
    $stmt = $db->query($queryUpcomingReservations);
    $upcomingReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error al conectar o ejecutar la consulta: " . $e->getMessage();
    die();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/pages.css">
    

</head>
<body>
    <h2>Vista General del Sistema de Gestión de Recursos</h2>

    <div class="dashboard">
        <!-- Total de Recursos -->
        <div class="dashboard-item">
            <h3>Total de Recursos</h3>
            <p><?= $totalResources ?></p>
        </div>

        <!-- Recursos Disponibles -->
        <div class="dashboard-item">
            <h3>Recursos Disponibles</h3>
            <p><?= $availableResources ?></p>
        </div>

        <!-- Reservas Activas -->
        <div class="dashboard-item">
            <h3>Reservas Activas</h3>
            <p><?= $activeReservations ?></p>
        </div>

        <!-- Mantenimientos Pendientes -->
        <div class="dashboard-item">
            <h3>Mantenimientos Pendientes</h3>
            <p><?= $pendingMaintenance ?></p>
        </div>
    </div>

    <!-- Sección de Próximas Reservas -->
    <div class="upcoming-reservations">
        <h3>Próximas Reservas</h3>
        <?php if (!empty($upcomingReservations)): ?>
            <ul>
                <?php foreach ($upcomingReservations as $reservation): ?>
                    <li>
                        <strong><?= htmlspecialchars($reservation['resource_name']) ?></strong> - 
                        <?= htmlspecialchars($reservation['responsible_person']) ?> - 
                        <?= htmlspecialchars($reservation['reservation_date']) ?>, 
                        <?= htmlspecialchars($reservation['reservation_time']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay reservas próximas.</p>
        <?php endif; ?>
    </div>
</body>
</html>