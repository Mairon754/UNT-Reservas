<?php
require_once __DIR__ . '/../db/db.php';   // Incluye la conexión a la base de datos

class Reserva {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    /**
     * Obtiene todas las reservas con información del recurso
     * @return array Lista de todas las reservas
     */
    public function getAllReservas() {
        try {
            // Modificar la consulta para incluir el nombre del recurso
            $query = "SELECT r.*, res.name as resource_name 
                     FROM reservations r 
                     JOIN resources res ON r.resource_id = res.id 
                     ORDER BY r.reservation_date, r.reservation_time";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAllReservas: " . $e->getMessage());
            throw $e;
        }
    }

    // Crear una nueva reserva
    // Crear una nueva reserva
public function createReserva($resource_id, $responsible_person, $reservation_date, $reservation_time, $user_id, $observations = '') {
    try {
        // Verificar primero si ya existe una reserva para este recurso en esta fecha y hora
        if ($this->isResourceReserved($resource_id, $reservation_date, $reservation_time)) {
            return false; // Recurso ya reservado
        }
        
        // Verificar si existen las columnas necesarias
        $columnsQuery = "SELECT column_name FROM information_schema.columns 
                        WHERE table_name = 'reservations' AND column_name IN ('user_id', 'created_by')";
        $columnsStmt = $this->db->query($columnsQuery);
        $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $hasUserId = in_array('user_id', $existingColumns);
        $hasCreatedBy = in_array('created_by', $existingColumns);
        
        // Construir la consulta según las columnas disponibles
        $fields = "resource_id, responsible_person, reservation_date, reservation_time, observations";
        $placeholders = ":resource_id, :responsible_person, :reservation_date, :reservation_time, :observations";
        $params = [
            ':resource_id' => $resource_id,
            ':responsible_person' => $responsible_person,
            ':reservation_date' => $reservation_date,
            ':reservation_time' => $reservation_time,
            ':observations' => $observations
        ];
        
        if ($hasUserId) {
            $fields .= ", user_id";
            $placeholders .= ", :user_id";
            $params[':user_id'] = $user_id;
        }
        
        if ($hasCreatedBy) {
            $fields .= ", created_by";
            $placeholders .= ", :created_by";
            $params[':created_by'] = $user_id; // Usar el mismo user_id para created_by
        }
        
        $query = "INSERT INTO reservations ($fields) VALUES ($placeholders)";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return true; // Reserva creada con éxito
    } catch (PDOException $e) {
        throw new PDOException("Error al crear la reserva: " . $e->getMessage());
    }
}

    // Verificar si un recurso ya está reservado en una fecha y hora específica
    public function isResourceReserved($resource_id, $date, $time) {
        try {
            $query = "SELECT COUNT(*) FROM reservations 
                     WHERE resource_id = :resource_id 
                     AND reservation_date = :date 
                     AND reservation_time = :time";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':resource_id' => $resource_id,
                ':date' => $date,
                ':time' => $time
            ]);
            return $stmt->fetchColumn() > 0;  // Devuelve true si está reservado, false si no
        } catch (PDOException $e) {
            die("Error al verificar la reserva: " . $e->getMessage());
        }
    }
    
    // Obtener reservas por fecha
    public function getReservasByDate($date) {
        try {
            $query = "SELECT r.id, r.resource_id, r.responsible_person, r.reservation_date, r.reservation_time, 
                      r.observations, res.name AS resource_name 
                      FROM reservations r
                      JOIN resources res ON r.resource_id = res.id
                      WHERE r.reservation_date = :date
                      ORDER BY r.reservation_time";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':date' => $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al obtener las reservas por fecha: " . $e->getMessage());
        }
    }
    
    // Eliminar una reserva
    public function deleteReserva($id, $user_id = null) {
        try {
            // Verificar si el campo user_id existe en la tabla
            $query = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'reservations' AND column_name = 'user_id'";
            $stmt = $this->db->query($query);
            $userIdExists = $stmt->rowCount() > 0;
            
            if ($userIdExists && $user_id !== null) {
                // Verificar si el usuario es el creador de la reserva
                if (!$this->isUserOwner($id, $user_id)) {
                    return false; // El usuario no es el creador
                }
                
                $query = "DELETE FROM reservations WHERE id = :id AND user_id = :user_id";
                $params = [':id' => $id, ':user_id' => $user_id];
            } else {
                $query = "DELETE FROM reservations WHERE id = :id";
                $params = [':id' => $id];
            }
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            die("Error al eliminar la reserva: " . $e->getMessage());
        }
    }
    
    // Actualizar una reserva existente
    public function updateReserva($id, $resource_id, $responsible_person, $reservation_date, $reservation_time, $user_id, $observations = '') {
        try {
            // Verificar si el campo user_id existe en la tabla
            $query = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'reservations' AND column_name = 'user_id'";
            $stmt = $this->db->query($query);
            $userIdExists = $stmt->rowCount() > 0;
            
            if ($userIdExists && $user_id !== null) {
                // Verificar si el usuario es el creador de la reserva
                if (!$this->isUserOwner($id, $user_id)) {
                    return false; // El usuario no es el creador
                }
                
                $query = "UPDATE reservations 
                          SET resource_id = :resource_id, 
                              responsible_person = :responsible_person, 
                              reservation_date = :reservation_date, 
                              reservation_time = :reservation_time, 
                              observations = :observations
                          WHERE id = :id AND user_id = :user_id";
                $params = [
                    ':id' => $id,
                    ':resource_id' => $resource_id,
                    ':responsible_person' => $responsible_person,
                    ':reservation_date' => $reservation_date,
                    ':reservation_time' => $reservation_time,
                    ':observations' => $observations,
                    ':user_id' => $user_id
                ];
            } else {
                $query = "UPDATE reservations 
                          SET resource_id = :resource_id, 
                              responsible_person = :responsible_person, 
                              reservation_date = :reservation_date, 
                              reservation_time = :reservation_time, 
                              observations = :observations
                          WHERE id = :id";
                $params = [
                    ':id' => $id,
                    ':resource_id' => $resource_id,
                    ':responsible_person' => $responsible_person,
                    ':reservation_date' => $reservation_date,
                    ':reservation_time' => $reservation_time,
                    ':observations' => $observations
                ];
            }
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            die("Error al actualizar la reserva: " . $e->getMessage());
        }
    }
    
    // Obtener una reserva específica por ID
    public function getReservaById($id) {
        try {
            $query = "SELECT r.*, res.name AS resource_name 
                      FROM reservations r
                      JOIN resources res ON r.resource_id = res.id
                      WHERE r.id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al obtener la reserva: " . $e->getMessage());
        }
    }
    
    // Verificar si un usuario es el creador de una reserva
    public function isUserOwner($reservation_id, $user_id) {
        try {
            // Verificar si el campo user_id existe en la tabla
            $query = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'reservations' AND column_name = 'user_id'";
            $stmt = $this->db->query($query);
            $userIdExists = $stmt->rowCount() > 0;
            
            if (!$userIdExists) {
                return false; // Si no existe la columna, no podemos verificar
            }
            
            $query = "SELECT COUNT(*) FROM reservations 
                     WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $reservation_id,
                ':user_id' => $user_id
            ]);
            return $stmt->fetchColumn() > 0;  // Devuelve true si el usuario es el creador, false si no
        } catch (PDOException $e) {
            die("Error al verificar el creador de la reserva: " . $e->getMessage());
        }
    }
}
?>