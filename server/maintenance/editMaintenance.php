<?php
include '../../navbar.php';
require_once '../../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM maintenance_requests WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener mantenimiento: " . $e->getMessage());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = $_POST['description'];
        $status = $_POST['status'];
        $priority = $_POST['priority'];

        try {
            $stmt = $db->prepare("UPDATE maintenance_requests SET description = :description, status = :status, priority = :priority WHERE id = :id");
            $stmt->execute([
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':id' => $id
            ]);
            header("Location: ../../pages/mantenimiento.php");
        } catch (PDOException $e) {
            die("Error al actualizar: " . $e->getMessage());
        }
    }
} else {
    die("ID inválido");
}
?>
<link rel="stylesheet" href="../../css/pages.css">


<h2>Editar Solicitud de Mantenimiento</h2>
<form method="POST">
    <label>Descripción:</label>
    <input type="text" name="description" value="<?= $request['description'] ?>" required><br>
    <label>Estado:</label>
    <input type="text" name="status" value="<?= $request['status'] ?>" required><br>
    <label>Prioridad:</label>
    <select name="priority">
        <option value="baja" <?= $request['priority'] == 'baja' ? 'selected' : '' ?>>Baja</option>
        <option value="media" <?= $request['priority'] == 'media' ? 'selected' : '' ?>>Media</option>
        <option value="alta" <?= $request['priority'] == 'alta' ? 'selected' : '' ?>>Alta</option>
    </select><br>
    <button type="submit">Actualizar</button>
</form>
