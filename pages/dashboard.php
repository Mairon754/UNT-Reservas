<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/project-UNT-reservas/db/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Usuario';
$is_admin = $_SESSION['is_admin'] ?? false;

// Conectar a la base de datos
try {
    $db = Database::connect();
    
    // Obtener estadísticas para el dashboard
    // Número total de recursos
    $stmt = $db->query("SELECT COUNT(*) FROM resources");
    $total_resources = $stmt->fetchColumn();
    
    // Número de reservas activas
    $stmt = $db->query("SELECT COUNT(*) FROM reservations WHERE reservation_date >= CURRENT_DATE");
    $active_reservations = $stmt->fetchColumn();
    
    // Número de mantenimientos pendientes
    $stmt = $db->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'reportado'");
    $pending_maintenance = $stmt->fetchColumn();
    
    // Obtener últimos mantenimientos
    $stmt = $db->query("SELECT m.*, r.name as resource_name 
                       FROM maintenance_requests m 
                       JOIN resources r ON m.resource_id = r.id 
                       ORDER BY m.created_at DESC LIMIT 6");
    $maintenance_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener próximas reservas del usuario
    $stmt = $db->prepare("SELECT r.*, res.name as resource_name 
                        FROM reservations r 
                        JOIN resources res ON r.resource_id = res.id 
                        WHERE r.user_id = :user_id AND r.reservation_date >= CURRENT_DATE
                        ORDER BY r.reservation_date, r.reservation_time 
                        LIMIT 5");
    $stmt->execute([':user_id' => $user_id]);
    $upcoming_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Reservas</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #003366; /* Azul marino oscuro como en tu navbar */
            --secondary-color: #004080;
            --accent-color: #0066cc;
            --dark-color: #002347;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --success-color: #2ecc71;
            --text-light: #ffffff;
            --card-bg: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main container */
        .main-container {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Dashboard overview */
        .dashboard-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .overview-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            align-items: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .overview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: #f9f9f9;
        }
        
        .overview-icon {
            background-color: rgba(0, 51, 102, 0.1); /* Azul marino con transparencia */
            color: var(--primary-color);
            padding: 12px;
            border-radius: 10px;
            margin-right: 1rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
        }
        
        .overview-content h3 {
            font-size: 2rem;
            margin: 0 0 0.3rem 0;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .overview-content p {
            margin: 0;
            color: #555;
            font-size: 0.95rem;
        }
        
        /* Títulos de secciones */
        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            justify-content: space-between;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        
        .section-title h2 {
            font-size: 1.6rem;
            margin: 0;
            position: relative;
            padding-left: 15px;
            color: var(--primary-color);
        }
        
        .section-title h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10%;
            height: 80%;
            width: 5px;
            background-color: var(--primary-color);
            border-radius: 10px;
        }
        
        .view-all-btn {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid var(--primary-color);
            padding: 6px 12px;
            border-radius: 4px;
        }
        
        .view-all-btn:hover {
            color: white;
            background-color: var(--primary-color);
        }
        
        .view-all-btn i {
            margin-left: 5px;
        }
        
        /* Maintenance cards - horizontal layout with more space */
        
        /*.maintenance-cards {
        display: grid;
        grid-template-columns: 1fr 1fr; 
        gap: 1.5rem;
        margin-bottom: 2.5rem;
        }*/
        
        .maintenance-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s;
            display: flex;
            flex-direction: column;
            border-top: 4px solid var(--primary-color);
            min-height: 300px; /* Altura mínima para uniformidad */
        }
        
        .maintenance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: #f9f9f9;
        }
        
        .maintenance-card-header {
            background-color: rgba(0, 51, 102, 0.05);
            padding: 1.2rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .maintenance-card-title {
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--primary-color);
            margin: 0;
            text-align: center;
        }
        
        .resource-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-reported {
            background-color: rgba(243, 156, 18, 0.15);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .status-inprogress {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .status-completed {
            background-color: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .maintenance-card-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .maintenance-card-description {
            color: #444;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.6;
            flex: 1;
        }
        
        .maintenance-card-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: auto;
        }
        
        .maintenance-detail {
            display: flex;
            align-items: center;
            font-size: 1rem;
            padding: 5px 0;
        }
        
        .maintenance-detail i {
            width: 25px;
            margin-right: 10px;
            color: #666;
            font-size: 1.1rem;
        }
        
        .priority-label {
            font-weight: 500;
            margin-right: 5px;
        }
        
        .priority-high {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .priority-medium {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .priority-low {
            color: var(--info-color);
            font-weight: 600;
        }
        
        .maintenance-card-footer {
            padding: 1.2rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: center; /* Centrado */
            background-color: rgba(0, 51, 102, 0.03);
        }
        
        .card-btn {
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            width: 80%; /* Botón más ancho */
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        /* Upcoming reservations */
        .upcoming-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .reservations-table th, 
        .reservations-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .reservations-table th {
            font-weight: 600;
            color: var(--primary-color);
            background-color: rgba(0, 51, 102, 0.05);
        }
        
        .reservations-table tr:last-child td {
            border-bottom: none;
        }
        
        .reservations-table tbody tr {
            transition: background-color 0.3s;
        }
        
        .reservations-table tbody tr:hover {
            background-color: rgba(0, 51, 102, 0.05);
        }
        
        .reservation-date {
            white-space: nowrap;
        }
        
        .reservation-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background-color: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1rem;
            transition: color 0.3s;
            padding: 5px;
        }
        
        .action-btn.edit:hover {
            color: var(--primary-color);
        }
        
        .action-btn.delete:hover {
            color: var(--danger-color);
        }
        
        /* Quick actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .quick-action-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--dark-color);
            text-align: center;
            border-bottom: 4px solid var(--primary-color);
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: #f9f9f9;
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .quick-action-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .quick-action-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .maintenance-cards {
                grid-template-columns: 1fr; 
            }
            
            .maintenance-card {
                min-height: auto;} /* Sin altura mínima en móviles */
            
            .reservations-table {
                font-size: 0.9rem;
            }
            
            .reservations-table th, 
            .reservations-table td {
                padding: 0.7rem;
            }
            
            /* Make table scrollable on small screens */
            .table-container {
                overflow-x: auto;
            }
        }
        </style>
</head>
<body>
    <!-- Incluir la navbar existente -->
    <?php include '../navbar.php'; ?>
    
    <!-- Main container -->
    <main class="main-container">
        <!-- Dashboard overview -->
        <section class="dashboard-overview">
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="overview-content">
                    <h3><?php echo $total_resources; ?></h3>
                    <p>Recursos Totales</p>
                </div>
            </div>
            
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="overview-content">
                    <h3><?php echo $active_reservations; ?></h3>
                    <p>Reservas Activas</p>
                </div>
            </div>
            
            <div class="overview-card">
                <div class="overview-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="overview-content">
                    <h3><?php echo $pending_maintenance; ?></h3>
                    <p>Mantenimientos Pendientes</p>
                </div>
            </div>
        </section>
        
        <!-- Mantenimientos -->
        <section>
            <div class="section-title">
                <h2>Mantenimientos Recientes</h2>
                <a href="mantenimiento.php" class="view-all-btn">Ver todos <i class="fas fa-chevron-right"></i></a>
            </div>
            
            <div class="maintenance-cards">
                <?php if (isset($maintenance_requests) && count($maintenance_requests) > 0): ?>
                    <?php foreach ($maintenance_requests as $request): ?>
                        <div class="maintenance-card">
                            <div class="maintenance-card-header">
                                <h3 class="maintenance-card-title"><?php echo htmlspecialchars($request['resource_name']); ?></h3>
                                <?php 
                                    $statusClass = '';
                                    $statusLabel = '';
                                    switch ($request['status']) {
                                        case 'reportado': 
                                            $statusClass = 'status-reported';
                                            $statusLabel = 'Reportado';
                                            break;
                                        case 'en_progreso': 
                                            $statusClass = 'status-inprogress';
                                            $statusLabel = 'En Progreso';
                                            break;
                                        case 'completado': 
                                            $statusClass = 'status-completed';
                                            $statusLabel = 'Completado';
                                            break;
                                        default: 
                                            $statusClass = 'status-reported';
                                            $statusLabel = 'R';
                                    }
                                ?>
                                <span class="resource-status <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </div>
                            
                            <div class="maintenance-card-body">
                                <p class="maintenance-card-description">
                                    <?php 
                                        // Mostrar más caracteres de la descripción
                                        echo htmlspecialchars(substr($request['description'], 0, 200)) . 
                                            (strlen($request['description']) > 200 ? '...' : ''); 
                                    ?>
                                </p>
                                
                                <div class="maintenance-card-details">
                                    <div class="maintenance-detail">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Fecha: <strong><?php echo date('d/m/Y', strtotime($request['created_at'])); ?></strong></span>
                                    </div>
                                    
                                    <div class="maintenance-detail">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span class="priority-label">Prioridad:</span>
                                        <?php 
                                            $priorityClass = '';
                                            switch ($request['priority']) {
                                                case 'alta': 
                                                    $priorityClass = 'priority-high';
                                                    break;
                                                case 'media': 
                                                    $priorityClass = 'priority-medium';
                                                    break;
                                                case 'baja': 
                                                    $priorityClass = 'priority-low';
                                                    break;
                                            }
                                        ?>
                                        <span class="<?php echo $priorityClass; ?>">
                                            <?php echo ucfirst($request['priority']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="maintenance-card-footer">
                                <a href="/project-UNT-reservas/server/maintenance/mantenimiento_detalle.php?id=<?php echo $request['id']; ?>" class="card-btn btn-primary">Ver Detalles</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay solicitudes de mantenimiento recientes.</p>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Próximas reservas -->
        <section class="upcoming-section">
            <div class="section-title">
                <h2>Mis Próximas Reservas</h2>
                <a href="reservas.php" class="view-all-btn">Ver todas <i class="fas fa-chevron-right"></i></a>
            </div>
            
            <div class="table-container">
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Responsable</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($upcoming_reservations) && count($upcoming_reservations) > 0): ?>
                            <?php foreach ($upcoming_reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reservation['resource_name']); ?></td>
                                    <td class="reservation-date"><?php echo date('d/m/Y', strtotime($reservation['reservation_date'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($reservation['reservation_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['responsible_person']); ?></td>
                                    <td>
                                        <div class="reservation-actions">
                                            <a href="../server/reservation/editReservation.php?id=<?php echo $reservation['id']; ?>" class="action-btn edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $reservation['id']; ?>)" class="action-btn delete" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No tienes reservas próximas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Acciones rápidas -->
        <section>
            <div class="section-title">
                <h2>Acciones Rápidas</h2>
            </div>
            
            <div class="quick-actions">
                <a href="reservas.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h3>Nueva Reserva</h3>
                    <p>Reserva un recurso</p>
                </a>
                
                <a href="mantenimiento.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Reportar Problema</h3>
                    <p>Solicitar mantenimiento</p>
                </a>
                
                <a href="recursos.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Ver Recursos</h3>
                    <p>Gestionar recursos disponibles</p>
                </a>
                
                <?php if ($is_admin): ?>
                <a href="../admin/users.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Gestionar Usuarios</h3>
                    <p>Administrar usuarios del sistema</p>
                </a>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer class="footer">
        <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>
    
    <script>
        // Función para confirmar eliminación de reserva
        function confirmDelete(id) {
            if (confirm('¿Estás seguro que deseas eliminar esta reserva?')) {
                window.location.href = '../server/reservation/deleteReservation.php?id=' + id;
            }
        }
        
        // Script para animar las tarjetas de estadísticas cuando son visibles
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.transform = 'translateY(0)';
                        entry.target.style.opacity = '1';
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.overview-card, .maintenance-card, .quick-action-card').forEach(card => {
                card.style.transform = 'translateY(20px)';
                card.style.opacity = '0';
                card.style.transition = 'transform 0.5s ease, opacity 0.5s ease, box-shadow 0.3s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>