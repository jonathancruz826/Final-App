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

// Agregar médico
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $dni = $_POST['dni'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $especialidad_nombre = $_POST['especialidad']; // Tomar el nombre de la especialidad ingresada
    $nombre_usuario = $_POST['nombre_usuario'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);

    // Verificar si el DNI ya existe en la base de datos
    $sql_verificar_dni = "SELECT * FROM medico WHERE dni = '$dni'";
    $resultado_dni = $conn->query($sql_verificar_dni);

    if ($resultado_dni->num_rows > 0) {
        echo "El DNI $dni ya está registrado. Por favor, ingrese un DNI diferente.";
        exit();
    }

    // Verificar si la especialidad ya existe
    $sql_verificar_especialidad = "SELECT especialidad_id FROM especialidad WHERE nombre = '$especialidad_nombre'";
    $result_especialidad = $conn->query($sql_verificar_especialidad);

    if ($result_especialidad->num_rows > 0) {
        // Si la especialidad ya existe, obtener su id
        $row = $result_especialidad->fetch_assoc();
        $especialidad_id = $row['especialidad_id'];
    } else {
        // Si no existe, insertar una nueva especialidad
        $sql_insert_especialidad = "INSERT INTO especialidad (nombre) VALUES ('$especialidad_nombre')";
        if ($conn->query($sql_insert_especialidad) === TRUE) {
            $especialidad_id = $conn->insert_id;
        } else {
            echo "Error al agregar especialidad: " . $conn->error;
            exit();
        }
    }

    // Insertar el médico
    $sql_insert_medico = "INSERT INTO medico (nombre, apellido, dni, telefono, correo_electronico, especialidad_id) 
                          VALUES ('$nombre', '$apellido', '$dni', '$telefono', '$correo', '$especialidad_id')";

    if ($conn->query($sql_insert_medico) === TRUE) {
        $medico_id = $conn->insert_id;

        // Crear usuario para el médico
        $sql_insert_usuario = "INSERT INTO usuario (nombre_usuario, contraseña, rol_id) 
                               VALUES ('$nombre_usuario', '$contraseña', 2)"; // rol_id 2 es para médicos

        if ($conn->query($sql_insert_usuario) === TRUE) {
            $usuario_id = $conn->insert_id;

            // Relacionar el usuario con el médico
            $sql_update_medico = "UPDATE medico SET usuario_id = $usuario_id WHERE medico_id = $medico_id";
            if ($conn->query($sql_update_medico) === TRUE) {

                // Insertar horarios del médico
                if (isset($_POST['dia']) && isset($_POST['hora_inicio']) && isset($_POST['hora_fin'])) {
                    $dias = $_POST['dia'];
                    $horas_inicio = $_POST['hora_inicio'];
                    $horas_fin = $_POST['hora_fin'];

                    // Insertar cada horario para cada día seleccionado
                    for ($i = 0; $i < count($dias); $i++) {
                        $dia = $dias[$i];
                        $hora_inicio = $horas_inicio[$i];
                        $hora_fin = $horas_fin[$i];

                        // Insertar horario en la base de datos
                        $sql_insert_horario = "INSERT INTO horario_medico (medico_id, dia, hora_inicio, hora_fin) 
                                               VALUES ('$medico_id', '$dia', '$hora_inicio', '$hora_fin')";
                        if (!$conn->query($sql_insert_horario)) {
                            echo "Error al agregar horario: " . $conn->error;
                        }
                    }
                }

                echo "Médico creado exitosamente";
            } else {
                echo "Error al asociar el usuario al médico: " . $conn->error;
            }
        } else {
            echo "Error al crear el usuario: " . $conn->error;
        }
    } else {
        echo "Error al agregar médico: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Médico</title>
    <link rel="stylesheet" href="medico.css">
    <script>
        function agregarHorarios() {
            // Obtener el contenedor de los horarios
            const contenedorHorarios = document.getElementById('contenedor_horarios');
            contenedorHorarios.innerHTML = '';  // Limpiar contenido previo

            // Obtener los días seleccionados
            const diasSeleccionados = document.querySelectorAll('select[name="dia[]"] option:checked');

            // Crear un grupo de campos de horario para cada día seleccionado
            diasSeleccionados.forEach(dia => {
                // Crear un contenedor para los horarios del día
                const divDia = document.createElement('div');
                divDia.classList.add('dia');

                // Crear los campos de hora de inicio y hora de fin para cada día
                const labelInicio = document.createElement('label');
                labelInicio.innerText = `Hora de inicio (${dia.value}):`;
                const inputInicio = document.createElement('input');
                inputInicio.type = 'time';
                inputInicio.name = 'hora_inicio[]';
                inputInicio.required = true;

                const labelFin = document.createElement('label');
                labelFin.innerText = `Hora de fin (${dia.value}):`;
                const inputFin = document.createElement('input');
                inputFin.type = 'time';
                inputFin.name = 'hora_fin[]';
                inputFin.required = true;

                // Agregar los elementos al contenedor del día
                divDia.appendChild(labelInicio);
                divDia.appendChild(inputInicio);
                divDia.appendChild(labelFin);
                divDia.appendChild(inputFin);

                // Agregar el contenedor del día al contenedor principal
                contenedorHorarios.appendChild(divDia);
            });
        }
    </script>
</head>
<body>

<h2>Crear Nuevo Médico</h2>

<!-- Navbar -->
<nav>
    <ul>
        <li><a href="admin_dashboard.php">Inicio</a></li>
        <li><a href="ver_turnos.php">Ver Turnos</a></li>
        <li><a href="crear_turno.php">Crear Turno</a></li>
        <li><a href="crear_medico.php">Crear Médico</a></li>
        <li><a href="ver_medicos.php">Ver Médicos</a></li>
        <li><a href="crear_admin.php">Crear Administrador</a></li>
        <li><a href="index.html">Cerrar Sesión</a></li>
    </ul>
</nav>

<form method="POST" action="crear_medico.php">
    <h3>Datos del Médico</h3>
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" required><br>
    
    <label for="apellido">Apellido:</label>
    <input type="text" name="apellido" required><br>

    <label for="dni">DNI:</label>
    <input type="text" name="dni" required><br>

    <label for="telefono">Teléfono:</label>
    <input type="text" name="telefono"><br>

    <label for="correo">Correo Electrónico:</label>
    <input type="email" name="correo"><br>

    <label for="especialidad">Especialidad:</label>
    <input type="text" name="especialidad" required><br>

    <h3>Datos de Usuario</h3>
    <label for="nombre_usuario">Nombre de Usuario:</label>
    <input type="text" name="nombre_usuario" required><br>

    <label for="contraseña">Contraseña:</label>
    <input type="password" name="contraseña" required><br>

    <h3>Horarios de Atención</h3>
    <label for="dias">Días disponibles:</label>
    <select name="dia[]" multiple required onchange="agregarHorarios()">
        <option value="Lunes">Lunes</option>
        <option value="Martes">Martes</option>
        <option value="Miércoles">Miércoles</option>
        <option value="Jueves">Jueves</option>
        <option value="Viernes">Viernes</option>
    </select><br>

    <!-- Contenedor para los horarios -->
    <div id="contenedor_horarios"></div>

    <button type="submit">Crear Médico</button>
</form>

</body>
</html>

<?php $conn->close(); ?>
