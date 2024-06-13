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

// Define SQL query for fetching monthly data
$sql = "
    SELECT 
        MONTH(FechaAlbaran) AS Mes, 
        SUM(ImporteFactura) AS Facturado 
    FROM AlbaranVentaCabecera
    WHERE 
        CodigoEmpresa = 1 
        AND EjercicioAlbaran = ? 
        AND CodigoRuta = ?
    GROUP BY MONTH(FechaAlbaran)
    ORDER BY MONTH(FechaAlbaran)
";

$params = array($ANNEE, $userCodigoRuta);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$monthlyData = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $monthlyData[] = $row;
}

sqlsrv_free_stmt($stmt);

// Convert PHP array to JSON
$monthlyDataJson = json_encode($monthlyData);

?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ventas Mensuales</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="style.css">
        <style>
            .logout-btn {
                position: absolute;
                top: 20px;
                right: 20px;
                background-color: #f44336;
                color: white;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
            }

            .logout-btn:hover {
                background-color: #d32f2f;
            }
        </style>
    </head>

    <body>
        <div id="mySidenav" class="sidenav">
            <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
            <!-- Your sidebar links -->
        </div>

        <div id="main">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776; Ventas Mensuales</span>
            <div class="container mt-5">
                <!-- Your form and table -->
            </div>
        </div>

        <form method="POST" action="logout.php" style="display: inline;">
            <button type="submit" class="logout-btn">Déconnexion</button>
        </form>

        <div class="container mt-5">
            <canvas id="myChart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Get the data from PHP
            const monthlyData = <?php echo $monthlyDataJson; ?>;

            // Prepare data for Chart.js
            const labels = monthlyData.map(data => {
                const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                return monthNames[data.Mes - 1];
            });

            const data = monthlyData.map(data => data.Facturado);

            const ctx = document.getElementById('myChart').getContext('2d');
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Facturado',
                        data: data,
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
        </script>

        <script>
            function openNav() {
                document.getElementById("mySidenav").style.width = "250px";
            }

            function closeNav() {
                document.getElementById("mySidenav").style.width = "0";
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>

</html>