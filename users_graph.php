<?php
session_start();
include 'conexion_exit_pr.php';

if (!isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}

if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit();
}

$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$userCodigoRuta = $_SESSION['codigo_ruta'];
$userRole = $_SESSION['role'];

if (!isset($userCodigoRuta) || !isset($userRole)) {
    echo "Error: Parámetros de sesión no definidos.";
    exit();
}

$pageTitle = $_SESSION['username'];

// sql query
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
        <title>Gráficos</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://getbootstrap.com/docs/5.3/assets/css/docs.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
        <a href="Main.php" class="m-4 btn btn-success">
            <i class="fas fa-arrow-left"></i> Devolver
        </a><br>
        <h1 class="mx-4 mb-4"><?php echo "Usuario: $pageTitle" ?></h1>
        <form method="get">
            <div class="">

                <div class="row">
                    <div class="col-md-4 mx-4 mb-4">
                        <label for="annee">Seleccione el Año:</label>
                        <select id="annee" name="annee" class="form-select" onchange="this.form.submit()">
                            <?php
                            for ($year = date('Y'); $year >= date('Y') - 4; $year--) {
                                $selected = ($year == $ANNEE) ? 'selected' : '';
                                echo "<option value=\"$year\" $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <noscript><input type="submit" value="Submit"></noscript>
        </form>
        <canvas id="yearlyFacturadoChart"></canvas>
        <canvas id="monthlyFacturadoChart" class="mt-5"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const yearlyData = <?php echo $jsonYearlyData; ?>;
                const monthlyData = <?php echo $jsonMonthlyData; ?>;
                const years = yearlyData.map(item => item.ANNEE);
                const yearlyFacturadoData = yearlyData.map(item => item.FACTURADO);
                const months = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
                const monthlyLabels = monthlyData.map(item => months[item.MES - 1]);
                const monthlyFacturadoData = monthlyData.map(item => item.FACTURADO);

                const colors = [
                    'rgba(54, 162, 235, 0.5)',
                ];

                const borderColors = [
                    'rgba(54, 162, 235, 1)',
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
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Facturados per year'
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
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Facturado in ' + <?php echo $ANNEE; ?>
                            }
                        }
                    }
                });
            });
        </script>
    </body>

</html>