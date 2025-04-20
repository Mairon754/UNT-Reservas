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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Estilo global del dashboard */
        .dashboard {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
            padding: 0 20px;
        }

        /* Cada cuadro dentro del dashboard */
        .dashboard-item {
            background-color: #f4f4f4;
            padding: 20px;
            margin: 10px;
            border-radius: 8px;
            width: 22%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Títulos dentro de los cuadros */
        .dashboard-item h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }

        /* Números o valores dentro de los cuadros */
        .dashboard-item p {
            font-size: 28px;
            font-weight: 600;
            color: #4CAF50;  /* Un verde similar al que aparece en la imagen */
            margin: 0;
        }

        /* Estilos para los cuadros de "Recursos por Tipo" */
        .dashboard-item.resources {
            background-color: #d1e7dd;  /* Color para los recursos disponibles */
            border-left: 10px solid #28a745; /* Línea de color verde al lado */
        }

        /* Cuadros con alertas de tipo "mantenimiento" o "problema" */
        .dashboard-item.alert {
            background-color: #ffecb3;  /* Amarillo para mostrar alertas */
            border-left: 10px solid #f39c12;  /* Línea de color amarillo fuerte */
        }

        /* Cuadros con alertas de tipo "problema crítico" */
        .dashboard-item.critical {
            background-color: #f8d7da;  /* Fondo rojo pálido */
            border-left: 10px solid #dc3545;  /* Línea roja al lado */
        }

        /* Estilo para la parte de "Recursos por Tipo", más específico */
        .dashboard-item .resource-type {
            font-size: 16px;
            color: #666;
        }

        /* Para los botones dentro del dashboard */
        .dashboard-item button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }

        /* Cambiar color de los botones cuando se pasa el mouse */
        .dashboard-item button:hover {
            background-color: #0056b3;
        }

        /* Estilos específicos para la sección de "Próximas Reservas" */
        .upcoming-reservations {
            width: 100%;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .upcoming-reservations h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .upcoming-reservations ul {
            list-style-type: none;
            padding: 0;
        }

        .upcoming-reservations li {
            background-color: white;
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin: 20px;
            color: #333;
        }
    </style>
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