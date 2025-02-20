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

// Eliminar admin
if (isset($_GET['eliminar_admin'])) {
    $admin_id = $_GET['eliminar_admin'];
    // Eliminar al admin de la base de datos
    $sql_delete = "DELETE FROM usuario WHERE usuario_id = $admin_id";
    if ($conn->query($sql_delete) === TRUE) {
        echo "Administrador eliminado.";
    } else {
        echo "Error al eliminar el administrador: " . $conn->error;
    }
}

// Agregar admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);

    // Crear usuario para el administrador
    $sql_insert_usuario = "INSERT INTO usuario (nombre_usuario, contraseña, rol_id) 
                           VALUES ('$nombre_usuario', '$contraseña', 1)"; // rol_id 1 es para admin

    if ($conn->query($sql_insert_usuario) === TRUE) {
        echo "Administrador creado exitosamente";
    } else {
        echo "Error al crear el administrador: " . $conn->error;
    }
}

// Mostrar la lista de administradores
$sql_admins = "SELECT * FROM usuario WHERE rol_id = 1";
$admins = $conn->query($sql_admins);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Admin</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body>

<h2>Crear Nuevo Administrador</h2>

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

<!-- Formulario para crear administrador -->
<form method="POST" action="crear_admin.php">
    <h3>Datos de Usuario</h3>
    <label for="nombre_usuario">Nombre de Usuario:</label>
    <input type="text" name="nombre_usuario" required><br>

    <label for="contraseña">Contraseña:</label>
    <input type="password" name="contraseña" required><br>

    <button type="submit">Crear Administrador</button>
</form>

<h3>Lista de Administradores</h3>
<ul>
    <?php while ($admin = $admins->fetch_assoc()): ?>
        <li>
            <?php echo $admin['nombre_usuario']; ?>
            <a href="crear_admin.php?eliminar_admin=<?php echo $admin['usuario_id']; ?>" onclick="return confirm('¿Estás seguro de eliminar este administrador?')">Eliminar</a>
        </li>
    <?php endwhile; ?>
</ul>

</body>
</html>
