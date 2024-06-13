<?php
session_start();
include 'conexion_exit_pr.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}

if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit();
}

// Initialize filter variables
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$userRole = $_SESSION['role'];
$loggedInUser = $_SESSION['username'];

// Define SQL query
$sql = "
    SELECT 
        MONTH(FechaAlbaran) AS month,
        CodigoRuta AS RUTA,
        CASE
            WHEN CodigoRuta = 91 THEN 'ROSA'
            WHEN CodigoRuta = 92 THEN 'RUBEN'
            WHEN CodigoRuta = 93 THEN 'SUSI'
        END AS COMERCIAL,
        CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
    FROM 
        AlbaranVentaCabecera
    WHERE
        CodigoEmpresa = 1
        AND YEAR(FechaAlbaran) = ?
        AND CodigoRuta IN (91, 92, 93)
    GROUP BY 
        MONTH(FechaAlbaran), CodigoRuta
    ORDER BY 
        MONTH(FechaAlbaran), CodigoRuta
";

// If the user is a boss, select data for all users
if ($userRole == 1) {
    $sql = "
        SELECT 
            MONTH(FechaAlbaran) AS month,
            CodigoRuta AS RUTA,
            CASE
                WHEN CodigoRuta = 91 THEN 'ROSA'
                WHEN CodigoRuta = 92 THEN 'RUBEN'
                WHEN CodigoRuta = 93 THEN 'SUSI'
            END AS COMERCIAL,
            CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM 
            AlbaranVentaCabecera
        WHERE
            CodigoEmpresa = 1
            AND YEAR(FechaAlbaran) = ?
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY 
            MONTH(FechaAlbaran), CodigoRuta
        ORDER BY 
            MONTH(FechaAlbaran), CodigoRuta
    ";
}

$stmt = sqlsrv_prepare($conn, $sql, array($year));
if (!$stmt) {
    echo "Error en la consulta SQL:";
    die(print_r(sqlsrv_errors(), true));
}

// Execute the query
sqlsrv_execute($stmt);

// Fetch results
$chartData = array();
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $month = $row['month'];
    $ruta = $row['RUTA'];
    $user = '';
    switch ($ruta) {
        case 91:
            $user = 'ROSA';
            break;
        case 92:
            $user = 'RUBEN';
            break;
        case 93:
            $user = 'SUSI';
            break;
        default:
            $user = '';
            break;
    }
    if (!isset($chartData[$month])) {
        $chartData[$month] = array();
    }
    $chartData[$month][$user] = $row['FACTURADO'];
}
sqlsrv_free_stmt($stmt);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Facturados per Month</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body>
        <h2>Facturados per Month</h2>
        <div>
            <label for="year">Select Year:</label>
            <select id="year">
                <?php
                // Generate options for years
                $currentYear = date("Y");
                for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                    echo "<option value='$i'>$i</option>";
                }
                ?>
            </select>
            <button onclick="getData()">Get Data</button>
        </div>
        <canvas id="myChart"></canvas>

        <script>
            function getData() {
                var year = document.getElementById('year').value;
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var data = JSON.parse(this.responseText);
                        updateChart(data);
                    }
                };
                xhr.open("GET", "get_data.php?year=" + year, true);
                xhr.send();
            }

            function updateChart(data) {
                var ctx = document.getElementById('myChart').getContext('2d');
                var labels = Object.keys(data);
                var datasets = [];
                var users = ['ROSA', 'RUBEN', 'SUSI'];
                var backgroundColors = ['#ff0000', '#00ff00', '#0000ff'];
                users.forEach(function (user, index) {
                    var facturados = Object.values(data).map(function (monthData) {
                        return monthData[user] || 0;
                    });
                    var dataset = {
                        label: user,
                        data: facturados,
                        backgroundColor: backgroundColors[index],
                        borderWidth: 1
                    };
                    datasets.push(dataset);
                });
                var chartData = {
                    labels: labels,
                    datasets: datasets
                };
                if (window.myChart instanceof Chart) {
                    window.myChart.destroy();
                }
                window.myChart = new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }

            getData(); // Load data initially
        </script>
    </body>

</html>