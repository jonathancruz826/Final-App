<?php
// Conexión a la base de datos
$host = 'localhost';
$db = 'Consultorio';
$user = 'root';
$pass = '1234';
$conn = new mysqli($host, $user, $pass, $db);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $dni = $_POST['dni'];
    $medico_id = $_POST['medico_id'];
    $fecha = $_POST['fecha'];
    $hora = isset($_POST['hora']) ? $_POST['hora'] : ''; // Verificar si 'hora' está definida

    // Validar si fecha y hora no están vacíos
    if (empty($fecha) || empty($hora)) {
    } else {
        // Combinar fecha y hora para formar la fecha completa
        $fecha_hora = $fecha . " " . $hora . ":00";

        // Verificar si el paciente ya existe por su DNI
        $sql_check_paciente = "SELECT paciente_id FROM paciente WHERE dni = ?";
        $stmt_check_paciente = $conn->prepare($sql_check_paciente);
        $stmt_check_paciente->bind_param("s", $dni);
        $stmt_check_paciente->execute();
        $result_check_paciente = $stmt_check_paciente->get_result();

        if ($result_check_paciente->num_rows > 0) {
            // El paciente ya existe, obtener su ID
            $paciente_data = $result_check_paciente->fetch_assoc();
            $paciente_id = $paciente_data['paciente_id'];
        } else {
            // El paciente no existe, insertarlo
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $telefono = $_POST['telefono'];
            $correo_electronico = $_POST['correo_electronico'];
            $direccion = $_POST['direccion'];

            // Insertar nuevo paciente
            $sql_insert_paciente = "INSERT INTO paciente (nombre, apellido, dni, telefono, correo_electronico, direccion) 
                                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert_paciente = $conn->prepare($sql_insert_paciente);
            $stmt_insert_paciente->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $correo_electronico, $direccion);
            $stmt_insert_paciente->execute();

            // Obtener el ID del nuevo paciente
            $paciente_id = $stmt_insert_paciente->insert_id;
        }

        // Comprobar si el paciente ya tiene un turno con el mismo médico el mismo día
        $sql_check = "SELECT * FROM turno WHERE paciente_id = ? AND medico_id = ? AND DATE(fecha_hora) = DATE(?)";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iis", $paciente_id, $medico_id, $fecha_hora);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Ya existe un turno para este paciente con el mismo médico en el mismo día
            echo "<p style='color:red;'>Ya tienes un turno programado con este médico para este día.</p>";
        } else {
            // Si no existe turno, proceder con la inserción
            $sql = "INSERT INTO turno (fecha_hora, paciente_id, medico_id) 
                    VALUES ('$fecha_hora', '$paciente_id', '$medico_id')";
            
            if ($conn->query($sql) === TRUE) {
                echo "<p style='color:green;'>Turno solicitado exitosamente.</p>";
            } else {
                echo "<p style='color:red;'>Error: " . $sql . "<br>" . $conn->error . "</p>";
            }
        }
    }
}

// Obtener la lista de médicos
$sql_medicos = "SELECT medico_id, nombre, apellido FROM medico";
$medicos_result = $conn->query($sql_medicos);

// Obtener horarios disponibles del médico seleccionado
$horarios = [];
$turnos_existentes = [];
if (isset($_POST['medico_id']) && $_POST['medico_id'] != "") {
    $medico_id = $_POST['medico_id'];
    $sql_horarios = "SELECT dia, hora_inicio, hora_fin FROM horario_medico WHERE medico_id = ? ORDER BY FIELD(dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes')";
    $stmt_horarios = $conn->prepare($sql_horarios);
    $stmt_horarios->bind_param("i", $medico_id);
    $stmt_horarios->execute();
    $result_horarios = $stmt_horarios->get_result();
    
    while ($row = $result_horarios->fetch_assoc()) {
        // Generar los rangos de 30 minutos entre la hora de inicio y la hora de fin
        $start_time = strtotime($row['hora_inicio']);
        $end_time = strtotime($row['hora_fin']);
        $horarios[$row['dia']] = [];

        // Crear los rangos de 30 minutos
        while ($start_time < $end_time) {
            $hora_inicio = date("H:i", $start_time);
            $start_time += 30 * 60; // Incrementar 30 minutos
            $hora_fin = date("H:i", $start_time);
            $horarios[$row['dia']][] = ['hora_inicio' => $hora_inicio, 'hora_fin' => $hora_fin];
        }
    }

    // Obtener los turnos ya existentes para el día seleccionado y médico
    if (isset($_POST['dia']) && $_POST['dia'] != "") {
        $dia = $_POST['dia'];
        $sql_turnos_existentes = "SELECT fecha_hora FROM turno WHERE medico_id = ? AND DATE(fecha_hora) = ?";
        $stmt_turnos_existentes = $conn->prepare($sql_turnos_existentes);
        $stmt_turnos_existentes->bind_param("is", $medico_id, $dia);
        $stmt_turnos_existentes->execute();
        $result_turnos_existentes = $stmt_turnos_existentes->get_result();

        while ($row = $result_turnos_existentes->fetch_assoc()) {
            $turnos_existentes[] = date("H:i", strtotime($row['fecha_hora']));
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sacar Turno</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body >
    <header>
        <nav>
            <ul>
                <li><a href="index.html">Inicio</a></li>
                <li><a href="sacar_turno.php">Sacar Turno</a></li>
                <li><a href="medicos.php">Médicos y Especialidades</a></li>
                <li><a href="login.php">Ingresar</a></li>
            </ul>
        </nav>
    </header>

    <section>
        <h1>Formulario para sacar turno</h1>
        <form method="POST" action="sacar_turno.php">

        <label for="medico_id">Selecciona al médico:</label>
        <select id="medico_id" name="medico_id" required onchange="this.form.submit();">
                <option value="">Seleccionar médico</option>
                <?php while ($medico = $medicos_result->fetch_assoc()) { ?>
                    <option value="<?php echo $medico['medico_id']; ?>" <?php echo (isset($_POST['medico_id']) && $_POST['medico_id'] == $medico['medico_id']) ? 'selected' : ''; ?>>
                        <?php echo $medico['nombre'] . " " . $medico['apellido']; ?>
                    </option>
                <?php } ?>
            </select>

            <?php if (isset($_POST['medico_id']) && $_POST['medico_id'] != "" && !empty($horarios)) { ?>
                <label for="dia">Selecciona el día del turno:</label>
                <select id="dia" name="dia" required onchange="this.form.submit();">
                    <option value="">Seleccionar día</option>
                    <?php foreach ($horarios as $dia => $horario) { ?>
                        <option value="<?php echo $dia; ?>" <?php echo (isset($_POST['dia']) && $_POST['dia'] == $dia) ? 'selected' : ''; ?>>
                            <?php echo $dia; ?>
                        </option>
                    <?php } ?>
                </select>

                <?php if (isset($_POST['dia']) && $_POST['dia'] != "") { ?>
                    <label for="hora">Selecciona el rango horario:</label>
                    <select id="hora" name="hora" required>
                        <option value="">Seleccionar rango horario</option>
                        <?php 
                        foreach ($horarios[$_POST['dia']] as $horario) {
                            // Comprobar si la hora ya está reservada
                            if (!in_array($horario['hora_inicio'], $turnos_existentes)) { ?>
                                <option value="<?php echo $horario['hora_inicio']; ?>">
                                    <?php echo $horario['hora_inicio'] . " - " . $horario['hora_fin']; ?>
                                </option>
                            <?php } 
                        } ?>
                    </select>
                <?php } ?>
            <?php } ?>

            <label for="fecha">Selecciona la fecha del turno:</label>
            <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>">

            <label for="dni">DNI del paciente:</label>
            <input type="text" id="dni" name="dni" required>

            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono">

            <label for="correo_electronico">Correo electrónico:</label>
            <input type="email" id="correo_electronico" name="correo_electronico">

            <label for="direccion">Dirección:</label>
            <input type="text" id="direccion" name="direccion">

            <button type="submit">Solicitar Turno</button>
        </form>
    </section>
</body>
</html>
