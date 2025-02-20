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

$paciente = null;
$historial = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = $_POST['dni'];

    // Buscar el paciente por DNI
    $sql = "SELECT * FROM paciente WHERE dni = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dni);  // Usamos 's' porque el DNI es una cadena
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $paciente = $result->fetch_assoc();

        // Buscar todos los historiales clínicos del paciente
        $sql_historial = "SELECT * FROM historia_clinica WHERE paciente_id = ?";
        $stmt_historial = $conn->prepare($sql_historial);
        $stmt_historial->bind_param("i", $paciente['paciente_id']);
        $stmt_historial->execute();
        $result_historial = $stmt_historial->get_result();

        // Almacenar todos los diagnósticos en un array
        while ($row = $result_historial->fetch_assoc()) {
            $historial[] = $row;
        }
    } else {
        $paciente = null;
        $historial = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Clínico</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="medico_dashboard.php">Inicio</a></li>
            <li><a href="ver_historial.php">Historial Clínico</a></li>
        </ul>
    </nav>

    <h2>Buscar Historial Clínico por DNI</h2>

    <form method="POST" action="ver_historial.php">
        <label for="dni">DNI del paciente:</label>
        <input type="text" name="dni" required>
        <button type="submit">Buscar</button>
    </form>

    <?php if ($paciente): ?>
        <h3>Datos del Paciente</h3>
        <p><strong>Nombre:</strong> <?php echo $paciente['nombre']; ?></p>
        <p><strong>DNI:</strong> <?php echo $paciente['dni']; ?></p>

        <?php if (count($historial) > 0): ?>
            <h3>Historial Clínico</h3>
            <?php foreach ($historial as $registro): ?>
                <p><strong>Diagnóstico:</strong> <?php echo $registro['diagnostico']; ?></p>
                <p><strong>Tratamiento:</strong> <?php echo $registro['tratamiento']; ?></p>
                <p><strong>Fecha de Registro:</strong> <?php echo $registro['fecha']; ?></p>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se ha registrado ningún diagnóstico para este paciente.</p>
        <?php endif; ?>

    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p>No se encontró un paciente con ese DNI.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
