<?php
// server/notification/NotificationService.php
// Versión simplificada sin dependencias de PHPMailer

require_once '../../db/db.php';

class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }

    // Función para obtener la configuración de notificaciones de correo
    private function getNotificationSetting($userId, $setting) {
        try {
            // Verificar si existe configuración para este usuario
            $query = "SELECT COUNT(*) FROM user_notification_settings WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            $count = $stmt->fetchColumn();
            
            // Si no existe, crear con valores predeterminados
            if ($count == 0) {
                $this->createDefaultSettings($userId);
                return true; // Por defecto, habilitado
            }
            
            // Obtener configuración existente
            $query = "SELECT $setting FROM user_notification_settings WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result[$setting];
        } catch (PDOException $e) {
            error_log("Error al obtener configuración de notificación: " . $e->getMessage());
            return false;
        }
    }

    // Verificar si el usuario tiene habilitadas las notificaciones por correo
    public function hasEmailNotificationsEnabled($userId) {
        return $this->getNotificationSetting($userId, 'email_notifications');
    }
    
    // Verificar si el usuario tiene habilitadas las notificaciones de reservas
    public function hasReservationNotificationsEnabled($userId) {
        return $this->getNotificationSetting($userId, 'reservation_notifications');
    }
    
    // Verificar si el usuario tiene habilitadas las notificaciones de mantenimiento
    public function hasMaintenanceNotificationsEnabled($userId) {
        return $this->getNotificationSetting($userId, 'maintenance_notifications');
    }
    
    // Crear configuración predeterminada para un usuario
    private function createDefaultSettings($userId) {
        try {
            $query = "INSERT INTO user_notification_settings 
                     (user_id, email_notifications, reservation_notifications, maintenance_notifications, created_at, updated_at) 
                     VALUES (:user_id, TRUE, TRUE, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                     ON CONFLICT (user_id) DO NOTHING";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error al crear configuración predeterminada: " . $e->getMessage());
            return false;
        }
    }

    // Enviar correo electrónico utilizando la función mail() nativa de PHP
    private function sendEmail($to, $subject, $message) {
        // Intentar obtener la configuración del sistema
        try {
            $query = "SELECT name, email FROM system_settings LIMIT 1";
            $stmt = $this->db->query($query);
            $systemSettings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $fromName = $systemSettings ? $systemSettings['name'] : 'Sistema de Gestión';
            $fromEmail = $systemSettings ? $systemSettings['email'] : 'noreply@sistema.com';
        } catch (PDOException $e) {
            error_log("Error al obtener configuración del sistema: " . $e->getMessage());
            $fromName = 'Sistema de Gestión';
            $fromEmail = 'noreply@sistema.com';
        }
        
        // Encabezados del correo
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // En entorno de desarrollo, solo registramos el correo en el log
        error_log("SIMULACIÓN DE CORREO ENVIADO: Destinatario: $to, Asunto: $subject, Mensaje: $message");
        
        // Para habilitar el envío real de correos en producción, descomenta la línea siguiente:
        return mail($to, $subject, $message, $headers);
        
        //return true; // Simulamos éxito en el envío
    }

    // Enviar notificación de nueva reserva
    public function sendReservationNotification($userId, $reservationData) {
        // Verificar si el usuario tiene habilitadas las notificaciones
        if (!$this->hasEmailNotificationsEnabled($userId) || !$this->hasReservationNotificationsEnabled($userId)) {
            return false;
        }

        // Obtener datos del usuario
        $user = $this->getUserData($userId);
        if (!$user) {
            return false;
        }

        // Preparar el mensaje de notificación
        $subject = "Nueva reserva creada";
        $message = "Hola {$user['name']},\n\n";
        $message .= "Tu reserva ha sido creada exitosamente con la siguiente información:\n\n";
        $message .= "Recurso: {$reservationData['resource_name']}\n";
        $message .= "Fecha: {$reservationData['reservation_date']}\n";
        $message .= "Hora: {$reservationData['reservation_time']}\n";

        if (isset($reservationData['end_date']) && isset($reservationData['end_time'])) {
            $message .= "Hasta: {$reservationData['end_date']} {$reservationData['end_time']}\n";
        }

        $message .= "\nGracias por usar nuestro sistema de reservas.\n";
        $message .= "Equipo de MaiProjects";

        return $this->sendEmail($user['email'], $subject, $message);
    }

    // Enviar notificación de mantenimiento
    public function sendMaintenanceNotification($userId, $maintenanceData) {
        // Verificar si el usuario tiene habilitadas las notificaciones
        if (!$this->hasEmailNotificationsEnabled($userId) || !$this->hasMaintenanceNotificationsEnabled($userId)) {
            return false;
        }

        // Obtener datos del usuario
        $user = $this->getUserData($userId);
        if (!$user) {
            return false;
        }

        // Preparar el mensaje de notificación
        $subject = "Nuevo mantenimiento programado";
        $message = "Hola {$user['name']},\n\n";
        $message .= "Se ha programado un mantenimiento que podría afectar tus reservas:\n\n";
        $message .= "Recurso: {$maintenanceData['resource_name']}\n";
        $message .= "Descripción: {$maintenanceData['description']}\n";
        $message .= "Estado: {$maintenanceData['status']}\n";
        $message .= "Prioridad: {$maintenanceData['priority']}\n";

        $message .= "\nGracias por tu comprensión.\n";
        $message .= "Equipo de MaiProjects";

        return $this->sendEmail($user['email'], $subject, $message);
    }

    // Obtener los datos del usuario
    private function getUserData($userId) {
        try {
            $query = "SELECT id, name, email FROM usrs WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario: " . $e->getMessage());
            return false;
        }
    }
}
?>