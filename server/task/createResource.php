<?php
// createResource.php: Agregar nuevo recurso

require_once '../db/db.php';

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $status = $_POST['status'];

    // Validar los datos (simplemente asegurarnos que no estén vacíos)
    if (!empty($name) && !empty($type) && !empty($status)) {
        // Insertar el recurso en la base de datos
        $db = Database::connect();
        $query = "INSERT INTO resources (name, type, status) VALUES (:name, :type, :status)";
        $stmt = $db->prepare($query);
        $stmt->execute([':name' => $name, ':type' => $type, ':status' => $status]);

        echo "Recurso creado con éxito!";
    } else {
        echo "Por favor, completa todos los campos.";
    }
}
?>

<!-- Formulario para crear un nuevo recurso -->
<form action="../server/task/createResource.php" method="POST">
    <label for="name">Nombre del Recurso:</label>
    <input type="text" id="name" name="name" required>
    <br>

    <label for="type">Tipo de Recurso:</label>
    <input type="text" id="type" name="type" required>
    <br>

    <label for="status">Estado:</label>
    <input type="text" id="status" name="status" required>
    <br>

    <button type="submit">Crear Recurso</button>
</form>
