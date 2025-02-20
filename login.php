<?php
session_start();

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

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['nombre_usuario'];
    $contraseña = $_POST['contraseña'];

    // Buscar el usuario en la base de datos
    $sql = "SELECT * FROM usuario WHERE nombre_usuario = '$usuario'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Si el usuario existe, verificar la contraseña
        $row = $result->fetch_assoc();
        if (password_verify($contraseña, $row['contraseña'])) {
            // La contraseña es correcta, establecer sesión
            $_SESSION['usuario_id'] = $row['usuario_id'];
            $_SESSION['nombre_usuario'] = $row['nombre_usuario'];
            $_SESSION['rol'] = ($row['rol_id'] == 1) ? 'admin' : 'medico';

            // Redirigir según el rol
            if ($_SESSION['rol'] == 'admin') {
                header("Location: admin_dashboard.php"); // Cambia a la página que desees para admins
            } else {
                header("Location: medico_dashboard.php"); // Cambia a la página que desees para médicos
            }
            exit();
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Iniciar Sesión</h2>

<form method="POST" action="login.php">
    <label for="nombre_usuario">Usuario:</label>
    <input type="text" name="nombre_usuario" required><br>

    <label for="contraseña">Contraseña:</label>
    <input type="password" name="contraseña" required><br>

    <button type="submit">Iniciar sesión</button>

    <!-- Botón para volver al index -->
<a href="index.html">
    <button type="button">Volver al inicio</button>
</a>
</form>

</body>
</html>