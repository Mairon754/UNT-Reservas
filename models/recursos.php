<?php
require_once '../db/db.php';  // Incluye la conexión a la base de datos


class Recurso { 
    private $db;

    public function __construct() {
        $this->db = Database::connect();  // Conexión a la base de datos
    }

    // Obtener todos los recursos disponibles
    public function getAllRecursos() {
        try {
            // Consulta para obtener todos los recursos disponibles (estado 'disponible')
            $query = "SELECT id, name FROM resources WHERE status = 'Disponible'";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);  // Devuelve todos los recursos disponibles
        } catch (PDOException $e) {
            die("Error al obtener los recursos: " . $e->getMessage());
        }
    }
}


?>
