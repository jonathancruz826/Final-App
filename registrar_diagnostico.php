<?php
session_start();

// Verificar que el usuario está autenticado como médico
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'medico') {
    header("Location: login.php");  // Si no está autenticado, redirige al login
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";  
$password = "1234";  
$dbname = "Consultorio";  

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del médico basado en el usuario_id de la sesión
$usuario_id = $_SESSION['usuario_id'];

// Obtener el medico_id basado en el usuario_id
$sql_medico = "SELECT medico_id FROM medico WHERE usuario_id = ?";
$stmt_medico = $conn->prepare($sql_medico);
$stmt_medico->bind_param("i", $usuario_id);
$stmt_medico->execute();
$stmt_medico->bind_result($medico_id);
$stmt_medico->fetch();
$stmt_medico->close();

// Verificar si se encontró el medico_id
if (!$medico_id) {
    die("Error: El médico no está registrado en la base de datos.");
}

// Inicializar variables
$paciente_id = null;
$paciente_nombre = "";
$paciente_dni = "";
$diagnostico = "";
$tratamiento = "";

// Verificar si se ha enviado el formulario para buscar al paciente por DNI
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar_paciente'])) {
    $dni = $_POST['dni'];

    // Buscar al paciente por DNI
    $sql_paciente = "SELECT paciente_id, nombre, dni FROM paciente WHERE dni = ?";
    $stmt_paciente = $conn->prepare($sql_paciente);
    $stmt_paciente->bind_param("s", $dni);
    $stmt_paciente->execute();
    $stmt_paciente->bind_result($paciente_id, $paciente_nombre, $paciente_dni);
    $stmt_paciente->fetch();
    $stmt_paciente->close();
}

// Verificar si se ha enviado el formulario para registrar el diagnóstico
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_diagnostico'])) {
    // Recuperar los datos del formulario
    $paciente_id = $_POST['paciente_id'];
    $diagnostico = $_POST['diagnostico'];
    $tratamiento = $_POST['tratamiento'];

    // Insertar el diagnóstico en la tabla historia_clinica
    $sql = "INSERT INTO historia_clinica (paciente_id, medico_id, diagnostico, tratamiento, fecha) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $paciente_id, $medico_id, $diagnostico, $tratamiento);

    if ($stmt->execute()) {
        echo "Diagnóstico registrado con éxito.";
    } else {
        echo "Error al registrar el diagnóstico: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Diagnóstico</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="medico_dashboard.php">Inicio</a></li>
            <li><a href="ver_historial.php">Historial Clínico</a></li>
        </ul>
    </nav>
    
    <?php if ($paciente_id): ?>
        <!-- Mostrar los datos del paciente si se encontró -->
        <h3>Datos del Paciente</h3>
        <p><strong>Nombre:</strong> <?php echo $paciente_nombre; ?></p>
        <p><strong>DNI:</strong> <?php echo $paciente_dni; ?></p>

        <!-- Formulario para registrar el diagnóstico -->
        <form method="POST" action="registrar_diagnostico.php">
            <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">

            <label for="diagnostico">Diagnóstico:</label>
            <textarea name="diagnostico" required><?php echo $diagnostico; ?></textarea>

            <label for="tratamiento">Tratamiento:</label>
            <textarea name="tratamiento"><?php echo $tratamiento; ?></textarea>

            <button type="submit" name="registrar_diagnostico">Registrar Diagnóstico</button>
        </form>
    <?php endif; ?>

</body>
</html>
