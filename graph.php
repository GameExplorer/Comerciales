<?php
session_start();
include 'conexion_exit_pr.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}

if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit();
}

// Initialize filter variables
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$userCodigoRuta = $_SESSION['codigo_ruta'];
$userRole = $_SESSION['role'];

// Vérification des paramètres de session
if (!isset($userCodigoRuta) || !isset($userRole)) {
    echo "Error: Parámetros de sesión no definidos.";
    exit();
}

// Define page titles
$pageTitles = [
    'ventas_por_cliente' => 'Ventas por Cliente',
    'ventas_por_ruta' => 'Ventas por Comerciales',
    'detalle_por_ruta' => 'Detalle por Ruta'
];
$queryType = 'ventas_por_ruta'; // Assuming we want 'ventas_por_ruta' for this example
$pageTitle = $_SESSION['username'];

// Define SQL queries
$sql_queries = [
    'ventas_por_ruta' => "
        SELECT 
            MONTH(FechaAlbaran) AS MES,
            CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM AlbaranVentaCabecera
        WHERE
            CodigoEmpresa = 1
            AND EjercicioAlbaran = ?
            AND ((1 = ?) OR CodigoRuta = ?)
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY MONTH(FechaAlbaran)
        ORDER BY MONTH(FechaAlbaran)
    ",
];

// Define SQL queries based on user role
if ($userRole == 1) { // If the user is a boss
    $sql_ruta = $sql_queries[$queryType];
    $params_ruta = array($ANNEE, 1, $userCodigoRuta); // Set the role to 1
} else { // If the user is not a boss
    $sql_ruta = $sql_queries[$queryType];
    $params_ruta = array($ANNEE, 0, $userCodigoRuta); // Set the role to 0
}

// Prepare and execute the query
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);
if ($stmt_ruta === false) {
    echo "Error en la consulta SQL:";
    die(print_r(sqlsrv_errors(), true));
}

// Fetch results
$results_ruta = [];
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);

// Convert results to JSON for JavaScript
$jsonData = json_encode($results_ruta);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1><?php echo $pageTitle; ?></h1>
    <form method="get">
        <label for="annee">Seleccione el Año:</label>
        <select id="annee" name="annee" onchange="this.form.submit()">
            <?php
            for ($year = date('Y'); $year >= date('Y') - 4; $year--) {
                $selected = ($year == $ANNEE) ? 'selected' : '';
                echo "<option value=\"$year\" $selected>$year</option>";
            }
            ?>
        </select>
        <noscript><input type="submit" value="Submit"></noscript>
    </form>
    <canvas id="facturadoChart"></canvas>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = <?php echo $jsonData; ?>;
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const labels = data.map(item => months[item.MES - 1]);
            const facturadoData = data.map(item => item.FACTURADO);

            const ctx = document.getElementById('facturadoChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Facturado',
                        data: facturadoData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
