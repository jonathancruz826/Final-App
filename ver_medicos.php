<?php
session_start();

// Verificar si el usuario ha iniciado sesión y si es un admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "Consultorio";

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se ha enviado el formulario de búsqueda
$search_nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';

// Construir la consulta SQL para obtener los médicos
$sql = "SELECT m.medico_id, m.nombre, m.apellido, e.nombre AS especialidad, m.especialidad_id
        FROM medico m 
        JOIN especialidad e ON m.especialidad_id = e.especialidad_id";

// Si se proporciona un nombre, filtrar por el nombre del médico
if ($search_nombre) {
    $sql .= " WHERE m.nombre LIKE '%$search_nombre%'";
}

$result = $conn->query($sql);

// Eliminar un médico si se ha recibido el ID del médico
if (isset($_GET['eliminar_id'])) {
    $medico_id = $_GET['eliminar_id'];

    // Obtener el usuario_id asociado al médico
    $get_usuario_sql = "SELECT usuario_id, especialidad_id FROM medico WHERE medico_id = $medico_id";
    $usuario_result = $conn->query($get_usuario_sql);
    $usuario_id = null;
    $especialidad_id = null;
    if ($usuario_result->num_rows > 0) {
        $row = $usuario_result->fetch_assoc();
        $usuario_id = $row['usuario_id'];
        $especialidad_id = $row['especialidad_id'];

        // Eliminar la relación de usuario del médico (vaciar el campo usuario_id)
        $update_medico_sql = "UPDATE medico SET usuario_id = NULL WHERE medico_id = $medico_id";
        $conn->query($update_medico_sql);

        // Ahora eliminar el usuario relacionado con el médico
        $delete_usuario_sql = "DELETE FROM usuario WHERE usuario_id = $usuario_id";
        if ($conn->query($delete_usuario_sql) === TRUE) {
            echo "<script>alert('Usuario asociado al médico eliminado exitosamente');</script>";
        } else {
            echo "<script>alert('Error al eliminar el usuario asociado al médico');</script>";
        }
    }

    // Eliminar primero los horarios del médico si existen
    $delete_horarios_sql = "DELETE FROM horario_medico WHERE medico_id = $medico_id";
    $conn->query($delete_horarios_sql);

    // Ahora eliminar el médico
    $delete_medico_sql = "DELETE FROM medico WHERE medico_id = $medico_id";
    if ($conn->query($delete_medico_sql) === TRUE) {
        // Verificar si la especialidad está asociada a otros médicos
        $check_especialidad_sql = "SELECT COUNT(*) AS count FROM medico WHERE especialidad_id = $especialidad_id";
        $count_result = $conn->query($check_especialidad_sql);
        $count_row = $count_result->fetch_assoc();
        if ($count_row['count'] == 0) {
            // Si no hay más médicos con esa especialidad, eliminarla
            $delete_especialidad_sql = "DELETE FROM especialidad WHERE especialidad_id = $especialidad_id";
            $conn->query($delete_especialidad_sql);
        }

        echo "<script>alert('Médico eliminado exitosamente');</script>";
    } else {
        echo "<script>alert('Error al eliminar el médico');</script>";
    }

    header("Location: ver_medicos.php"); // Redirigir después de eliminar
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Médicos - Admin</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body>

<h2>Médicos Registrados</h2>

<!-- Navbar -->
<nav>
    <ul>
        <li><a href="admin_dashboard.php">Inicio</a></li>
        <li><a href="ver_turnos.php">Ver Turnos</a></li>
        <li><a href="crear_turno.php">Crear Turno</a></li>
        <li><a href="crear_medico.php">Crear Médico</a></li>
        <li><a href="ver_medicos.php">Ver Médicos</a></li>
        <li><a href="crear_admin.php">Crear Administrador</a></li>
        <li><a href="index.html">Cerrar Sesión</a></li> <!-- Opción para cerrar sesión -->
    </ul>
</nav>

<!-- Formulario de búsqueda por nombre de médico -->
<form method="POST" action="ver_medicos.php">
    <label for="nombre">Buscar por Nombre del Médico:</label>
    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($search_nombre); ?>">
    <button type="submit">Buscar</button>
</form>

<!-- Mostrar los médicos -->
<table>
    <tr>
        <th>Nombre del Médico</th>
        <th>Especialidad</th>
        <th>Acciones</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['nombre']} {$row['apellido']}</td>
                    <td>{$row['especialidad']}</td>
                    <td>
                    <a href='ver_medicos.php?eliminar_id={$row['medico_id']}' onclick='return confirm(\"¿Estás seguro de eliminar este médico?\")'>Eliminar</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='3'>No se encontraron médicos.</td></tr>";
    }
    ?>
</table>

</body>
</html>

<?php $conn->close(); ?>
