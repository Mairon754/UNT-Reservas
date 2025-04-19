<?php
// editMaintenance.php: Editar una solicitud de mantenimiento existente

require_once '../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener la solicitud de mantenimiento actual
    $db = Database::connect();
    $query = "SELECT * FROM maintenance_requests WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_id = $_POST['resource_id'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];

    // Actualizar la solicitud de mantenimiento
    $query = "UPDATE maintenance_requests SET resource_id = :resource_id, description = :description, priority = :priority WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':resource_id' => $resource_id,
        ':description' => $description,
        ':priority' => $priority,
        ':id' => $id
    ]);

    echo "Mantenimiento actualizado con éxito!";
}
?>

<form action="editMaintenance.php?id=<?php echo $maintenance['id']; ?>" method="POST">
    <label for="resource_id">Recurso:</label>
    <select id="resource_id" name="resource_id">
        <option value="1">Aula 101</option>
        <option value="2">Proyector EPSON</option>
        <option value="3">Laptop HP</option>
    </select>
    <br>

    <label for="description">Descripción:</label>
    <textarea id="description" name="description" required><?php echo $maintenance['description']; ?></textarea>
    <br>

    <label for="priority">Prioridad:</label>
    <select id="priority" name="priority" required>
        <option value="alta" <?php echo $maintenance['priority'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
        <option value="media" <?php echo $maintenance['priority'] == 'media' ? 'selected' : ''; ?>>Media</option>
        <option value="baja" <?php echo $maintenance['priority'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
    </select>
    <br>

    <button type="submit">Actualizar Solicitud de Mantenimiento</button>
</form>
