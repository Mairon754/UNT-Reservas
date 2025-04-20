<?php
require_once '../../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $db = Database::connect();
        $query = "DELETE FROM resources WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        header("Location: ../../pages/recursos.php");
    } catch (PDOException $e) {
        echo "Error al eliminar: " . $e->getMessage();
    }
}
?>