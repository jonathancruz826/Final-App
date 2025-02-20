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

// Obtener el nombre del usuario de la sesión
$nombre_usuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : 'Administrador';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Admin</title>
    <link rel="stylesheet" href="medico.css"> <!-- Si tienes un archivo de estilos -->
</head>
<body>

<!-- Menú de Navegación (Nav) -->
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

<h2>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></h2>

<!-- Opciones para gestionar turnos, médicos, etc. -->
<p>Aquí puedes gestionar los turnos, médicos, y administradores del consultorio.</p>

<section id="imagen">
        <img src="admin.webp" alt="Imagen relacionada al consultorio médico" style="max-width: 50%; height: auto;">
</section>

</body>
</html>

<?php $conn->close(); ?>
