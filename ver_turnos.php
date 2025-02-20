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
$search_dni = isset($_POST['dni']) ? $_POST['dni'] : '';

// Construir la consulta SQL para obtener los turnos
$sql = "SELECT t.turno_id, t.fecha_hora, p.nombre AS paciente_nombre, p.dni AS paciente_dni, 
               m.nombre AS medico_nombre, e.nombre AS especialidad, p.paciente_id
        FROM turno t 
        JOIN paciente p ON t.paciente_id = p.paciente_id 
        JOIN medico m ON t.medico_id = m.medico_id
        JOIN especialidad e ON m.especialidad_id = e.especialidad_id";

// Si se proporciona un DNI, filtrar por el DNI del paciente
if ($search_dni) {
    $sql .= " WHERE p.dni LIKE '%$search_dni%'";
}

$result = $conn->query($sql);

// Eliminar un turno y el paciente si se ha recibido el ID de un turno
if (isset($_GET['eliminar_id'])) {
    $turno_id = $_GET['eliminar_id'];

    // Obtener el paciente_id relacionado con el turno
    $turno_sql = "SELECT paciente_id FROM turno WHERE turno_id = $turno_id";
    $turno_result = $conn->query($turno_sql);
    $turno_row = $turno_result->fetch_assoc();
    $paciente_id = $turno_row['paciente_id'];

    // Eliminar el turno
    $delete_sql = "DELETE FROM turno WHERE turno_id = $turno_id";
    if ($conn->query($delete_sql) === TRUE) {
        // Verificar si el paciente tiene otros turnos programados
        $check_turnos_sql = "SELECT COUNT(*) AS total_turnos FROM turno WHERE paciente_id = $paciente_id";
        $turnos_check = $conn->query($check_turnos_sql);
        $turnos_row = $turnos_check->fetch_assoc();

        // Si no tiene otros turnos, eliminar al paciente
        if ($turnos_row['total_turnos'] == 0) {
            $delete_paciente_sql = "DELETE FROM paciente WHERE paciente_id = $paciente_id";
            $conn->query($delete_paciente_sql);
        }
        
        echo "<script>alert('Turno eliminado exitosamente');</script>";
    } else {
        echo "<script>alert('Error al eliminar el turno');</script>";
    }
    header("Location: ver_turnos.php"); // Redirigir después de eliminar
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="medico.css">
    <title>Ver Turnos - Admin</title>
</head>
<body>

<h2>Turnos Programados</h2>

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

<!-- Formulario de búsqueda por DNI -->
<form method="POST" action="ver_turnos.php">
    <label for="dni">Buscar por DNI del paciente:</label>
    <input type="text" id="dni" name="dni" value="<?php echo htmlspecialchars($search_dni); ?>">
    <button type="submit">Buscar</button>
</form>

<!-- Mostrar los turnos -->
<table>
    <tr>
        <th>Paciente</th>
        <th>DNI</th>
        <th>Fecha y Hora</th>
        <th>Médico</th>
        <th>Especialidad</th>
        <th>Acciones</th>
    </tr>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['paciente_nombre']}</td>
                    <td>{$row['paciente_dni']}</td>
                    <td>{$row['fecha_hora']}</td>
                    <td>{$row['medico_nombre']}</td>
                    <td>{$row['especialidad']}</td>
                    <td>
                        <a href='ver_turnos.php?eliminar_id={$row['turno_id']}' onclick='return confirm(\"¿Estás seguro de eliminar este turno?\")'>Eliminar</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No se encontraron turnos para ese DNI.</td></tr>";
    }
    ?>
</table>

</body>
</html>

<?php $conn->close(); ?>
