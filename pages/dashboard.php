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
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>

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