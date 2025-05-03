<?php
require_once '../db/db.php';  // Incluye la conexión a la base de datos

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
    public function createReserva($resource_id, $responsible_person, $reservation_date, $reservation_time, $observations = '') {
        try {
            // Verificar primero si ya existe una reserva para este recurso en esta fecha y hora
            if ($this->isResourceReserved($resource_id, $reservation_date, $reservation_time)) {
                return false; // Recurso ya reservado
            }
            
            $query = "INSERT INTO reservations (resource_id, responsible_person, reservation_date, reservation_time, observations) 
                      VALUES (:resource_id, :responsible_person, :reservation_date, :reservation_time, :observations)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':resource_id' => $resource_id,
                ':responsible_person' => $responsible_person,
                ':reservation_date' => $reservation_date,
                ':reservation_time' => $reservation_time,
                ':observations' => $observations
            ]);
            return true; // Reserva creada con éxito
        } catch (PDOException $e) {
            die("Error al crear la reserva: " . $e->getMessage());
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
    public function deleteReserva($id) {
        try {
            $query = "DELETE FROM reservations WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            die("Error al eliminar la reserva: " . $e->getMessage());
        }
    }
    
    // Actualizar una reserva existente
    public function updateReserva($id, $resource_id, $responsible_person, $reservation_date, $reservation_time, $observations = '') {
        try {
            $query = "UPDATE reservations 
                      SET resource_id = :resource_id, 
                          responsible_person = :responsible_person, 
                          reservation_date = :reservation_date, 
                          reservation_time = :reservation_time, 
                          observations = :observations
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id' => $id,
                ':resource_id' => $resource_id,
                ':responsible_person' => $responsible_person,
                ':reservation_date' => $reservation_date,
                ':reservation_time' => $reservation_time,
                ':observations' => $observations
            ]);
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
}
?>