<?php
session_start();
// Usar una ruta fija desde la raíz del proyecto
require_once $_SERVER['DOCUMENT_ROOT'] . '/project-UNT-reservas/db/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Verificar si se recibió un ID de mantenimiento
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../../pages/mantenimientos.php');
    exit();
}

// Obtener información del usuario y del mantenimiento
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Usuario';
$is_admin = $_SESSION['is_admin'] ?? false;
$maintenance_id = $_GET['id'];

// Conexión a la base de datos
try {
    $db = Database::connect();
    
    // Obtener detalles del mantenimiento
    // Cambio: eliminamos la unión con usrs ya que no existe user_id en maintenance_requests
    $query = "SELECT m.*, r.name as resource_name, r.type as resource_type
              FROM maintenance_requests m 
              JOIN resources r ON m.resource_id = r.id 
              WHERE m.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $maintenance_id]);
    $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$maintenance) {
        // Si no existe el mantenimiento, redirigir
        $_SESSION['error_message'] = "No se encontró el mantenimiento solicitado.";
        header('Location: ../../pages/mantenimiento.php');
        exit();
    }
    
    // Verificar si existe la tabla de comentarios
    $table_check = $db->query("SELECT to_regclass('public.maintenance_comments') IS NOT NULL AS exists");
    $table_exists = $table_check && $table_check->fetchColumn();
    
    $comments = []; // Inicializar como array vacío por defecto
    
    if ($table_exists) {
        // Verificar si hay actualizaciones/comentarios del mantenimiento
        $query = "SELECT mc.*, u.name as author_name
                FROM maintenance_comments mc
                LEFT JOIN usrs u ON mc.user_id = u.id
                WHERE mc.maintenance_id = :maintenance_id
                ORDER BY mc.created_at ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([':maintenance_id' => $maintenance_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Procesar formulario de actualización/comentario si se envió
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_status']) && $is_admin) {
            // Actualizar estado del mantenimiento (solo admin)
            $new_status = $_POST['status'];
            $query = "UPDATE maintenance_requests 
                      SET status = :status, updated_at = NOW() 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $maintenance_id
            ]);
            
            // Si se actualizó el estado, comprobar si existe la tabla de comentarios
            // y crear un comentario del sistema
            if ($table_exists) {
                // Registrar el cambio de estado como un comentario
                $status_labels = [
                    'reportado' => 'Reportado',
                    'en_progreso' => 'En Progreso',
                    'completado' => 'Completado'
                ];
                
                $comment_text = "Estado actualizado a: " . ($status_labels[$new_status] ?? $new_status);
                
                $query = "INSERT INTO maintenance_comments (maintenance_id, user_id, comment, created_at) 
                          VALUES (:maintenance_id, :user_id, :comment, NOW())";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':maintenance_id' => $maintenance_id,
                    ':user_id' => $user_id,
                    ':comment' => $comment_text
                ]);
            }
            
            // Actualizar la página para reflejar los cambios
            header("Location: mantenimiento_detalle.php?id=$maintenance_id&updated=1");
            exit();
        } 
        elseif (isset($_POST['add_comment']) && !empty($_POST['comment_text'])) {
            // Añadir comentario al mantenimiento
            $comment_text = $_POST['comment_text'];
            
            // Verificar si existe la tabla de comentarios
            if (!$table_exists) {
                // Si no existe la tabla, crearla
                $db->exec("CREATE TABLE maintenance_comments (
                    id SERIAL PRIMARY KEY,
                    maintenance_id INT NOT NULL,
                    user_id INT NOT NULL,
                    comment TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (maintenance_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES usrs(id) ON DELETE CASCADE
                )");
            }
            
            // Insertar el comentario
            $query = "INSERT INTO maintenance_comments (maintenance_id, user_id, comment, created_at) 
                      VALUES (:maintenance_id, :user_id, :comment, NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':maintenance_id' => $maintenance_id,
                ':user_id' => $user_id,
                ':comment' => $comment_text
            ]);
            
            // Actualizar la página para reflejar los cambios
            header("Location: mantenimiento_detalle.php?id=$maintenance_id&commented=1");
            exit();
        }
    }
    
} catch (PDOException $e) {
    $error = "Error de conexión: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Mantenimiento - Sistema de Reservas</title>
    <link rel="stylesheet" href="../../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos se mantienen igual que en la versión anterior */
        :root {
            --primary-color: #003366;
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
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Header con botón de retorno */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 1rem;
        }
        
        .page-title {
            font-size: 1.8rem;
            margin: 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .back-button i {
            margin-right: 8px;
        }
        
        .back-button:hover {
            background-color: var(--secondary-color);
        }
        
        /* Contenedor de detalles */
        .maintenance-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        /* Sección principal */
        .main-details {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .maintenance-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .maintenance-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        
        .maintenance-header-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .maintenance-header-meta div {
            display: flex;
            align-items: center;
        }
        
        .maintenance-header-meta i {
            margin-right: 5px;
        }
        
        .status-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-reported {
            background-color: #f39c12;
        }
        
        .badge-inprogress {
            background-color: #3498db;
        }
        
        .badge-completed {
            background-color: #2ecc71;
        }
        
        .maintenance-body {
            padding: 1.5rem;
        }
        
        .description-section {
            margin-bottom: 2rem;
        }
        
        .description-section h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            font-size: 1.2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .description-content {
            line-height: 1.6;
            font-size: 1rem;
            color: #444;
        }
        
        .comments-section {
            margin-top: 2rem;
        }
        
        .comment-list {
            margin-bottom: 1.5rem;
        }
        
        .comment-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 3px solid #ddd;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .comment-author {
            font-weight: 600;
        }
        
        .comment-content {
            line-height: 1.5;
        }
        
        .system-comment {
            border-left-color: var(--info-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        .comment-form button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .comment-form button:hover {
            background-color: var(--secondary-color);
        }
        
        /* Sidebar con detalles adicionales */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .sidebar-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .sidebar-card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .sidebar-card-body {
            padding: 1rem;
        }
        
        .detail-item {
            display: flex;
            margin-bottom: 0.8rem;
            align-items: flex-start;
        }
        
        .detail-item i {
            width: 20px;
            margin-right: 10px;
            color: var(--primary-color);
            margin-top: 3px;
        }
        
        .detail-item-label {
            font-weight: 600;
            margin-right: 0.5rem;
            color: #555;
            width: 80px;
        }
        
        .detail-item-value {
            color: #333;
            flex: 1;
        }
        
        .status-form {
            margin-top: 1rem;
        }
        
        .status-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            font-family: inherit;
        }
        
        .update-btn {
            width: 100%;
            padding: 0.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .update-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-high {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .priority-medium {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .priority-low {
            background-color: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        /* Notificaciones de estado */
        .notification {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        .notification i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .notification-success {
            background-color: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }
        
        .notification-info {
            background-color: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
        }
        
        .notification-error {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #e74c3c;
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .maintenance-details {
                grid-template-columns: 1fr;
            }
            
            .status-badge {
                position: static;
                display: inline-block;
                margin-top: 1rem;
            }
            
            .maintenance-header-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Main container -->
    <main class="main-container">
        <!-- Page header -->
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-tools"></i> Detalles de Mantenimiento</h1>
            <a href="../../pages/mantenimiento.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Volver a Mantenimientos
            </a>
        </div>
        
        <?php if (isset($_GET['updated'])): ?>
        <div class="notification notification-success">
            <i class="fas fa-check-circle"></i>
            <span>El estado del mantenimiento ha sido actualizado exitosamente.</span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['commented'])): ?>
        <div class="notification notification-info">
            <i class="fas fa-comment"></i>
            <span>Se ha agregado un nuevo comentario al mantenimiento.</span>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="notification notification-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Maintenance details -->
        <div class="maintenance-details">
            <!-- Main details section -->
            <div class="main-details">
                <div class="maintenance-header">
                    <h2><?php echo htmlspecialchars($maintenance['resource_name']); ?></h2>
                    <div class="maintenance-header-meta">
                        <div>
                            <i class="fas fa-calendar-alt"></i>
                            <span>Reportado el: <?php echo date('d/m/Y', strtotime($maintenance['created_at'])); ?></span>
                        </div>
                        <div>
                            <i class="fas fa-user"></i>
                            <!-- No tenemos información del usuario que reportó, así que mostramos un genérico -->
                            <span>Por: Usuario del Sistema</span>
                        </div>
                    </div>
                    
                    <?php 
                        $statusClass = '';
                        $statusLabel = '';
                        
                        switch ($maintenance['status']) {
                            case 'reportado':
                                $statusClass = 'badge-reported';
                                $statusLabel = 'Reportado';
                                break;
                            case 'en_progreso':
                                $statusClass = 'badge-inprogress';
                                $statusLabel = 'En Progreso';
                                break;
                            case 'completado':
                                $statusClass = 'badge-completed';
                                $statusLabel = 'Completado';
                                break;
                            default:
                                $statusClass = 'badge-reported';
                                $statusLabel = 'Reportado';
                        }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo $statusLabel; ?>
                    </span>
                </div>
                
                <div class="maintenance-body">
                    <div class="description-section">
                        <h3>Descripción</h3>
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($maintenance['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="comments-section">
                        <h3>Comentarios y Actualizaciones</h3>
                        
                        <div class="comment-list">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <?php 
                                        $isSystemComment = strpos($comment['comment'], 'Estado actualizado a:') !== false;
                                        $commentClass = $isSystemComment ? 'system-comment' : '';
                                    ?>
                                    <div class="comment-item <?php echo $commentClass; ?>">
                                        <div class="comment-header">
                                            <span class="comment-author">
                                                <?php echo htmlspecialchars($comment['author_name'] ?? 'Usuario del Sistema'); ?>
                                            </span>
                                            <span class="comment-date">
                                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No hay comentarios o actualizaciones para este mantenimiento.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="comment-form">
                            <h4>Añadir Comentario</h4>
                            <form method="post" action="mantenimiento_detalle.php?id=<?php echo $maintenance_id; ?>">
                                <textarea name="comment_text" placeholder="Escribe tu comentario aquí..." required></textarea>
                                <button type="submit" name="add_comment">Enviar Comentario</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar with additional details -->
            <div class="sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        Detalles del Recurso
                    </div>
                    <div class="sidebar-card-body">
                        <div class="detail-item">
                            <i class="fas fa-box"></i>
                            <span class="detail-item-label">Recurso:</span>
                            <span class="detail-item-value"><?php echo htmlspecialchars($maintenance['resource_name']); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-tag"></i>
                            <span class="detail-item-label">Tipo:</span>
                            <span class="detail-item-value"><?php echo htmlspecialchars($maintenance['resource_type'] ?? 'No especificado'); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-exclamation-circle"></i>
                            <span class="detail-item-label">Prioridad:</span>
                            <span class="detail-item-value">
                                <?php 
                                    $priorityClass = '';
                                    
                                    switch ($maintenance['priority']) {
                                        case 'alta':
                                            $priorityClass = 'priority-high';
                                            $priorityLabel = 'Alta';
                                            break;
                                        case 'media':
                                            $priorityClass = 'priority-medium';
                                            $priorityLabel = 'Media';
                                            break;
                                        case 'baja':
                                            $priorityClass = 'priority-low';
                                            $priorityLabel = 'Baja';
                                            break;
                                        default:
                                            $priorityClass = 'priority-medium';
                                            $priorityLabel = 'Media';
                                    }
                                ?>
                                <span class="priority-badge <?php echo $priorityClass; ?>">
                                    <?php echo $priorityLabel; ?>
                                </span>
                            </span>
                        </div>
                        
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span class="detail-item-label">Creado:</span>
                            <span class="detail-item-value"><?php echo date('d/m/Y H:i', strtotime($maintenance['created_at'])); ?></span>
                        </div>
                        
                        <?php if (isset($maintenance['updated_at']) && $maintenance['updated_at'] && $maintenance['updated_at'] != $maintenance['created_at']): ?>
                        <div class="detail-item">
                            <i class="fas fa-sync-alt"></i>
                            <span class="detail-item-label">Actualizado:</span>
                            <span class="detail-item-value"><?php echo date('d/m/Y H:i', strtotime($maintenance['updated_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($is_admin): ?>
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        Actualizar Estado
                    </div>
                    <div class="sidebar-card-body">
                        <form method="post" action="mantenimiento_detalle.php?id=<?php echo $maintenance_id; ?>" class="status-form">
                            <select name="status" class="status-select">
                                <option value="reportado" <?php echo $maintenance['status'] === 'reportado' ? 'selected' : ''; ?>>Reportado</option>
                                <option value="en_progreso" <?php echo $maintenance['status'] === 'en_progreso' ? 'selected' : ''; ?>>En Progreso</option>
                                <option value="completado" <?php echo $maintenance['status'] === 'completado' ? 'selected' : ''; ?>>Completado</option>
                            </select>
                            <button type="submit" name="update_status" class="update-btn">Actualizar Estado</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        Acciones
                    </div>
                    <div class="sidebar-card-body">
                        <a href="../../pages/mantenimiento.php" class="back-button" style="display:block; text-align:center; margin-bottom:10px;">
                            <i class="fas fa-list"></i> Ver Todos los Mantenimientos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer">
        <p>&copy; 2025 MaiProjects. Todos los derechos reservados.</p>
    </footer>
</body>
</html>