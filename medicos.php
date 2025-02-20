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

// Verificar si se ha ingresado una especialidad en la búsqueda
$especialidad_filtro = isset($_GET['especialidad']) ? $_GET['especialidad'] : '';

// Consulta para obtener la información del médico y sus horarios
$sql = "SELECT m.medico_id, m.nombre AS medico_nombre, m.apellido AS medico_apellido, e.nombre AS especialidad,
               h.dia, h.hora_inicio, h.hora_fin
        FROM medico m
        JOIN especialidad e ON m.especialidad_id = e.especialidad_id
        LEFT JOIN horario_medico h ON m.medico_id = h.medico_id";

// Si se ha ingresado una especialidad, agregar un filtro en la consulta
if ($especialidad_filtro) {
    $sql .= " WHERE e.nombre LIKE '%" . $conn->real_escape_string($especialidad_filtro) . "%'";
}

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos y Especialidades</title>
    <link rel="stylesheet" href="medico.css">
</head>
<body>
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
        <h1>Médicos y Especialidades</h1>

        <!-- Formulario de búsqueda por nombre de especialidad -->
        <form method="GET" action="">
            <label for="especialidad">Buscar por Especialidad:</label>
            <input type="text" name="especialidad" id="especialidad" placeholder="Ingresa el nombre de la especialidad" value="<?php echo htmlspecialchars($especialidad_filtro); ?>">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <tr>
                <th>Nombre del Médico</th>
                <th>Especialidad</th>
                <th>Días y Horarios</th>
            </tr>
            <?php 
            $medicos = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $medico_id = $row["medico_id"];
                    $medico_nombre = $row["medico_nombre"];
                    $medico_apellido = $row["medico_apellido"];
                    $especialidad = $row["especialidad"];
                    $dia = $row["dia"];
                    $hora_inicio = $row["hora_inicio"];
                    $hora_fin = $row["hora_fin"];

                    // Organizar horarios por médico
                    if (!isset($medicos[$medico_id])) {
                        $medicos[$medico_id] = [
                            'nombre' => $medico_nombre . " " . $medico_apellido,
                            'especialidad' => $especialidad,
                            'horarios' => []
                        ];
                    }
                    if ($dia) {
                        $medicos[$medico_id]['horarios'][] = "$dia: $hora_inicio - $hora_fin";
                    }
                }

                // Mostrar médicos y sus horarios
                foreach ($medicos as $medico) {
                    echo "<tr><td>" . $medico['nombre'] . "</td><td>" . $medico['especialidad'] . "</td><td>";
                    echo implode("<br>", $medico['horarios']); // Mostrar horarios en una línea por cada uno
                    echo "</td></tr>";
                }

            } else {
                echo "<tr><td colspan='3'>No hay médicos registrados o no se encontraron resultados.</td></tr>";
            } 
            ?>
        </table>
    </section>
</body>
</html>
