<?php
include '../../navbar.php';
require_once '../../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM resources WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener recurso: " . $e->getMessage());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $status = $_POST['status'];

        try {
            $stmt = $db->prepare("UPDATE resources SET name = :name, type = :type, status = :status WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':status' => $status,
                ':id' => $id
            ]);
            header("Location: ../../pages/recursos.php");
        } catch (PDOException $e) {
            die("Error al actualizar: " . $e->getMessage());
        }
    }
} else {
    die("ID inválido");
}
?>

<link rel="stylesheet" href="../../css/pages.css">

<h2>Editar Recurso</h2>
<form method="POST">
    <label>Nombre:</label>
    <input type="text" name="name" value="<?= $resource['name'] ?>" required><br>
    <label>Tipo:</label>
    <input type="text" name="type" value="<?= $resource['type'] ?>" required><br>
    <label>Estado:</label>
    <input type="text" name="status" value="<?= $resource['status'] ?>" required><br>
    <button type="submit">Actualizar</button>
</form>