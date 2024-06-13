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
$pageTitle = $_SESSION['username'];

// Define SQL queries
$sql_queries = [
    'ventas_por_ruta_yearly' => "
        SELECT 
            EjercicioAlbaran AS ANNEE,
            CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM AlbaranVentaCabecera
        WHERE
            CodigoEmpresa = 1
            AND EjercicioAlbaran BETWEEN ? AND ?
            AND ((1 = ?) OR CodigoRuta = ?)
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY EjercicioAlbaran
        ORDER BY EjercicioAlbaran
    ",
    'ventas_por_ruta_monthly' => "
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

$startYear = date('Y') - 4;
$currentYear = date('Y');

// Define SQL queries based on user role
if ($userRole == 1) { // If the user is a boss
    $sql_yearly = $sql_queries['ventas_por_ruta_yearly'];
    $params_yearly = array($startYear, $currentYear, 1, $userCodigoRuta); // Set the role to 1
    $sql_monthly = $sql_queries['ventas_por_ruta_monthly'];
    $params_monthly = array($ANNEE, 1, $userCodigoRuta); // Set the role to 1
} else { // If the user is not a boss
    $sql_yearly = $sql_queries['ventas_por_ruta_yearly'];
    $params_yearly = array($startYear, $currentYear, 0, $userCodigoRuta); // Set the role to 0
    $sql_monthly = $sql_queries['ventas_por_ruta_monthly'];
    $params_monthly = array($ANNEE, 0, $userCodigoRuta); // Set the role to 0
}

// Prepare and execute the yearly query
$stmt_yearly = sqlsrv_query($conn, $sql_yearly, $params_yearly);
if ($stmt_yearly === false) {
    echo "Error en la consulta SQL:";
    die(print_r(sqlsrv_errors(), true));
}

// Fetch yearly results
$results_yearly = [];
while ($row = sqlsrv_fetch_array($stmt_yearly, SQLSRV_FETCH_ASSOC)) {
    $results_yearly[] = $row;
}

sqlsrv_free_stmt($stmt_yearly);

// Prepare and execute the monthly query
$stmt_monthly = sqlsrv_query($conn, $sql_monthly, $params_monthly);
if ($stmt_monthly === false) {
    echo "Error en la consulta SQL:";
    die(print_r(sqlsrv_errors(), true));
}

// Fetch monthly results
$results_monthly = [];
while ($row = sqlsrv_fetch_array($stmt_monthly, SQLSRV_FETCH_ASSOC)) {
    $results_monthly[] = $row;
}

sqlsrv_free_stmt($stmt_monthly);

// Convert results to JSON for JavaScript
$jsonYearlyData = json_encode($results_yearly);
$jsonMonthlyData = json_encode($results_monthly);

?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <title><?php echo $pageTitle; ?></title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body>
        <h1><?php echo "Usuario: $pageTitle" ?></h1>
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
        <canvas id="yearlyFacturadoChart"></canvas>
        <canvas id="monthlyFacturadoChart"></canvas>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const yearlyData = <?php echo $jsonYearlyData; ?>;
                const monthlyData = <?php echo $jsonMonthlyData; ?>;
                const years = yearlyData.map(item => item.ANNEE);
                const yearlyFacturadoData = yearlyData.map(item => item.FACTURADO);
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const monthlyLabels = monthlyData.map(item => months[item.MES - 1]);
                const monthlyFacturadoData = monthlyData.map(item => item.FACTURADO);

                // Colors for the yearly chart
                const colors = [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)'
                ];

                const borderColors = [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ];

                // Yearly Facturado Chart
                const ctxYearly = document.getElementById('yearlyFacturadoChart').getContext('2d');
                new Chart(ctxYearly, {
                    type: 'bar',
                    data: {
                        labels: years,
                        datasets: [{
                            label: 'Facturado',
                            data: yearlyFacturadoData,
                            backgroundColor: colors,
                            borderColor: borderColors,
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

                // Monthly Facturado Chart
                const ctxMonthly = document.getElementById('monthlyFacturadoChart').getContext('2d');
                new Chart(ctxMonthly, {
                    type: 'bar',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: 'Facturado in ' + <?php echo $ANNEE; ?>,
                            data: monthlyFacturadoData,
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