<?php
session_start();

// Verificar que el usuario está autenticado como médico
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'medico') {
    header("Location: login.php");  // Si no está autenticado, redirige al login
    exit();
}

// Obtener el ID del usuario desde la sesión
$usuario_id = $_SESSION['usuario_id'];

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "Consultorio";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el nombre y apellido del médico basándose en el usuario_id
$sql_nombre_medico = "SELECT nombre, apellido FROM medico WHERE usuario_id = ?";
$stmt_nombre = $conn->prepare($sql_nombre_medico);
$stmt_nombre->bind_param("i", $usuario_id);  // Usar el usuario_id
$stmt_nombre->execute();
$stmt_nombre->bind_result($nombre_medico, $apellido_medico);
$stmt_nombre->fetch();
$stmt_nombre->close();

// Comprobar si se obtuvo el nombre del médico
if (!$nombre_medico) {
    $nombre_medico = "Desconocido";  // Si no se encuentra el nombre, mostrar un mensaje por defecto
    $apellido_medico = "";
}

// Consulta para obtener los turnos asignados al médico
$sql = "SELECT t.turno_id, 
               DATE_FORMAT(t.fecha_hora, '%Y-%m-%d') AS fecha, 
               DATE_FORMAT(t.fecha_hora, '%H:%i') AS hora,
               p.nombre AS paciente_nombre
        FROM turno t
        JOIN paciente p ON t.paciente_id = p.paciente_id
        WHERE t.medico_id = (SELECT medico_id FROM medico WHERE usuario_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);  // Buscar turnos basados en usuario_id
$stmt->execute();
$result_turnos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Médico</title>
    <link rel="stylesheet" href="medico.css"> <!-- Enlaza tu archivo CSS -->
</head>
<body>

    <!-- Menú de Navegación (Nav) -->
    <nav>
        <ul>
            <li><a onclick="showSection('inicio')">Inicio</a></li>
            <li><a onclick="showSection('turnos')">Turnos Asignados</a></li>
            <li><a onclick="showSection('historial')">Historial Clínico</a></li>
            <li><a onclick="showSection('diagnostico')">Registrar Diagnóstico</a></li> <!-- Enlace para registrar diagnóstico -->
            <li><a href="index.html">Cerrar sesión</a></li> <!-- Link para cerrar sesión -->
        </ul>
    </nav>

    <!-- Sección de Inicio -->
    <div id="inicio" class="section active">
        <h2>Bienvenido, Dr./Dra. <?php echo htmlspecialchars($nombre_medico . " " . $apellido_medico); ?></h2>
        <p>En esta sección puedes gestionar tus turnos y ver el historial clínico de tus pacientes.</p>

        <section id="imagen">
        <img src="medico.jpg" alt="Imagen relacionada al consultorio médico" style="max-width: 50%; height: auto;">
    </section>
    
    </div>

    <!-- Sección de Turnos Asignados -->
    <div id="turnos" class="section">
        <h2>Turnos Asignados</h2>

        <?php if ($result_turnos->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Paciente</th>
                </tr>
                <?php while ($row = $result_turnos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['fecha']; ?></td>
                        <td><?php echo $row['hora']; ?></td>
                        <td><?php echo $row['paciente_nombre']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No tienes turnos asignados.</p>
        <?php endif; ?>
    </div>

    <!-- Sección de Historial Clínico -->
    <div id="historial" class="section">
        <h2>Buscar Historial Clínico por DNI</h2>

        <form action="ver_historial.php" method="POST">
            <label for="dni">DNI del Paciente:</label>
            <input type="text" name="dni" required>
            <button type="submit">Buscar Historial</button>
        </form>
    </div>

    <!-- Sección de Registrar Diagnóstico -->
    <div id="diagnostico" class="section">
        <h2>Registrar Diagnóstico y Tratamiento</h2>

        <form method="POST" action="registrar_diagnostico.php">
        <label for="dni">DNI del Paciente:</label>
        <input type="text" name="dni" required>
        <button type="submit" name="buscar_paciente">Buscar Paciente</button>
    </form>
    </div>

    <script>
        function showSection(sectionId) {
            // Ocultar todas las secciones
            var sections = document.getElementsByClassName('section');
            for (var i = 0; i < sections.length; i++) {
                sections[i].classList.remove('active');
            }

            // Mostrar la sección seleccionada
            document.getElementById(sectionId).classList.add('active');
        }
    </script>

</body>
</html>

<?php
$conn->close();
?>
