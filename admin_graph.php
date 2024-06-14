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

$pageTitles = [
    'ventas_por_cliente' => 'Ventas por Cliente',
    'ventas_por_ruta' => 'Ventas por Comerciales',
    'detalle_por_ruta' => 'Detalle por Ruta'
];
$pageTitle = $_SESSION['username'];

$sql_ventes_annee = "
    SELECT 
        EjercicioAlbaran AS ANNEE,
        SUM(CASE WHEN CodigoRuta = 91 THEN ImporteFactura ELSE 0 END) AS ROSA,
        SUM(CASE WHEN CodigoRuta = 92 THEN ImporteFactura ELSE 0 END) AS RUBEN,
        SUM(CASE WHEN CodigoRuta = 93 THEN ImporteFactura ELSE 0 END) AS SUSI
    FROM AlbaranVentaCabecera
    WHERE
        CodigoEmpresa = 1
        AND EjercicioAlbaran >= YEAR(GETDATE()) - 4
        AND ((1 = ?) OR CodigoRuta = ?)
        AND CodigoRuta IN (91, 92, 93)
    GROUP BY EjercicioAlbaran
    ORDER BY EjercicioAlbaran
";

$stmt_ventes_annee = sqlsrv_query($conn, $sql_ventes_annee, array($userRole, $userCodigoRuta));
if ($stmt_ventes_annee === false) {
    echo "Error en la consulta SQL pour les ventes par année:";
    die(print_r(sqlsrv_errors(), true));
}

$results_annee = [];
while ($row = sqlsrv_fetch_array($stmt_ventes_annee, SQLSRV_FETCH_ASSOC)) {
    $results_annee[] = $row;
}

sqlsrv_free_stmt($stmt_ventes_annee);

$jsonDataAnnee = json_encode($results_annee);

$sql_ventes_mois = "
    SELECT 
        MONTH(FechaAlbaran) AS MES,
        SUM(CASE WHEN CodigoRuta = 91 THEN ImporteFactura ELSE 0 END) AS ROSA,
        SUM(CASE WHEN CodigoRuta = 92 THEN ImporteFactura ELSE 0 END) AS RUBEN,
        SUM(CASE WHEN CodigoRuta = 93 THEN ImporteFactura ELSE 0 END) AS SUSI
    FROM AlbaranVentaCabecera
    WHERE
        CodigoEmpresa = 1
        AND EjercicioAlbaran = ?
        AND ((1 = ?) OR CodigoRuta = ?)
        AND CodigoRuta IN (91, 92, 93)
    GROUP BY MONTH(FechaAlbaran)
    ORDER BY MONTH(FechaAlbaran)
";

$stmt_ventes_mois = sqlsrv_query($conn, $sql_ventes_mois, array($ANNEE, $userRole, $userCodigoRuta));
if ($stmt_ventes_mois === false) {
    echo "Error en la consulta SQL pour les ventes par mois:";
    die(print_r(sqlsrv_errors(), true));
}

$results_mois = [];
while ($row = sqlsrv_fetch_array($stmt_ventes_mois, SQLSRV_FETCH_ASSOC)) {
    $results_mois[] = $row;
}

sqlsrv_free_stmt($stmt_ventes_mois);

$jsonDataMes = json_encode($results_mois);
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
        <h1 class="mx-4 mb-4">Graphs of all the users></h1>

        <form method="get">
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

        <!-- Diagramme par Année -->
        <canvas id="facturadoChartAnnee"></canvas>

        <!-- Diagramme par Mois -->
        <canvas id="facturadoChartMes"></canvas>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Données pour le diagramme par année
                const dataAnnee = <?php echo $jsonDataAnnee; ?>;
                const years = dataAnnee.map(item => item.ANNEE);
                const rosaDataAnnee = dataAnnee.map(item => item.ROSA);
                const rubenDataAnnee = dataAnnee.map(item => item.RUBEN);
                const susiDataAnnee = dataAnnee.map(item => item.SUSI);

                const ctxAnnee = document.getElementById('facturadoChartAnnee').getContext('2d');
                new Chart(ctxAnnee, {
                    type: 'bar',
                    data: {
                        labels: years,
                        datasets: [
                            {
                                label: 'ROSA',
                                data: rosaDataAnnee,
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'RUBEN',
                                data: rubenDataAnnee,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'SUSI',
                                data: susiDataAnnee,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Données pour le diagramme par mois
                const dataMes = <?php echo $jsonDataMes; ?>;
                const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const labelsMes = dataMes.map(item => months[item.MES - 1]);
                const rosaDataMes = dataMes.map(item => item.ROSA);
                const rubenDataMes = dataMes.map(item => item.RUBEN);
                const susiDataMes = dataMes.map(item => item.SUSI);

                const ctxMes = document.getElementById('facturadoChartMes').getContext('2d');
                new Chart(ctxMes, {
                    type: 'bar',
                    data: {
                        labels: labelsMes,
                        datasets: [
                            {
                                label: 'ROSA',
                                data: rosaDataMes,
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'RUBEN',
                                data: rubenDataMes,
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'SUSI',
                                data: susiDataMes,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }
                        ]
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