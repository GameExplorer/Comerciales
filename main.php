<?php
session_start();
include 'conexion_exit_pr.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit();
}

// Initialize filter variables
$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$queryType = isset($_GET['query']) ? $_GET['query'] : 'ventas_por_cliente';
$userCodigoRuta = $_SESSION['codigo_ruta'];
$userRole = $_SESSION['role'];

// Define page titles
$pageTitles = [
    'ventas_por_cliente' => 'Ventas por Cliente',
    'ventas_por_ruta' => 'Ventas por Comerciales',
    'detalle_por_ruta' => 'Detalle por Ruta'
];
$pageTitle = $pageTitles[$queryType] ?? 'Ventas';

// Define SQL queries
$sql_queries = [
    'ventas_por_cliente' => "
        SELECT
            CodigoRuta AS RUTA,
            CASE
                WHEN CodigoRuta = 91 THEN 'ROSA'
                WHEN CodigoRuta = 92 THEN 'RUBEN'
                WHEN CodigoRuta = 93 THEN 'SUSI'
            END AS COMERCIAL,
            CodigoCliente,
            RazonSocial,
            CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM AlbaranVentaCabecera
        WHERE
            CodigoEmpresa = 1
            AND EjercicioAlbaran = ?
            AND MONTH(FechaAlbaran) = ?
            AND (('boss' = ? AND ? = 0) OR CodigoRuta = ?)
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY CodigoRuta, CodigoCliente, RazonSocial
        ORDER BY RUTA, CodigoCliente
    ",
    'ventas_por_ruta' => "
        SELECT 
            CodigoRuta AS RUTA,
            CASE
                WHEN CodigoRuta = 91 THEN 'ROSA'
                WHEN CodigoRuta = 92 THEN 'RUBEN'
                WHEN CodigoRuta = 93 THEN 'SUSI'
            END AS COMERCIAL,
            CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM AlbaranVentaCabecera
        WHERE
            CodigoEmpresa = 1
            AND EjercicioAlbaran = ?
            AND MONTH(FechaAlbaran) = ?
            AND (('boss' = ? AND ? = 0) OR CodigoRuta = ?)
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY CodigoRuta
    ",
    'detalle_por_ruta' => "
        SELECT 
            'Albaran' AS TIPO,
            AVC.CodigoRuta AS RUTA,
            AVC.CodigoComisionista AS COMISIONISTA,
            IIF(AVC.CodigoComisionista IN (51,3,25), COMI.Comisionista, '') AS NOMBRE,
            CONVERT(VARCHAR, AVC.FechaAlbaran, 101) AS FECHA,
            AVC.CodigoCliente,
            AVC.RazonSocial,
            AVC.NumeroFactura,
            CAST(SUM(AVC.ImporteBruto) AS numeric(10,2)) AS BRUTO,
            CAST(SUM(AVC.ImporteDescuento) AS numeric(10,2)) AS DTO,
            CAST(SUM(AVC.ImporteFactura) AS numeric(10,2)) AS FACTURADO
        FROM AlbaranVentaCabecera AS AVC
        LEFT JOIN Comisionistas AS COMI
            ON COMI.CodigoComisionista = AVC.CodigoComisionista
        WHERE
            AVC.CodigoEmpresa = 1
            AND AVC.EjercicioAlbaran = ?
            AND MONTH(AVC.FechaAlbaran) = ?
            AND (('boss' = ? AND ? = 0) OR AVC.CodigoRuta = ?)
            AND AVC.CodigoRuta IN (91, 92, 93)
        GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
        ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente
    ",
];

// Prepare and execute the query
$sql_ruta = $sql_queries[$queryType];
$params_ruta = array($ANNEE, $MES, $userRole, $userCodigoRuta, $userCodigoRuta);
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);
if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch results
$results_ruta = [];
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);
?>
<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="style.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=ventas_por_cliente">Ventas por
                Cliente</a>
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=ventas_por_ruta">Ventas por
                Comerciales</a>
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=detalle_por_ruta">Detalle por Ruta</a>
            <a href="?tab=show_yearly_graph">Show Yearly Graph</a> <!-- New tab -->
        </div>

        <div id="main">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;
                <?php echo htmlspecialchars($pageTitle); ?></span>
            <div class="container mt-5">
                <!-- Existing Content -->
                <?php if (isset($_GET['tab']) && $_GET['tab'] == 'show_yearly_graph'): ?>
                    <h2>Yearly Graph</h2>
                    <?php if ($userRole == 'boss'): ?>
                        <form id="user-selection-form" method="POST">
                            <label for="selected_user">Select User:</label>
                            <select id="selected_user" name="selected_user" class="form-select">
                                <?php
                                $user_sql = "SELECT id, username FROM users WHERE disabled = 0";
                                $user_stmt = sqlsrv_query($conn, $user_sql);
                                while ($user_row = sqlsrv_fetch_array($user_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                    <option value="<?php echo $user_row['id']; ?>">
                                        <?php echo htmlspecialchars($user_row['username']); ?>
                                    </option>
                                <?php endwhile;
                                sqlsrv_free_stmt($user_stmt);
                                ?>
                            </select>
                            <button type="button" class="btn btn-primary mt-2" onclick="loadGraphData()">Load Graph</button>
                        </form>
                    <?php endif; ?>
                    <canvas id="yearlyGraph" width="400" height="200"></canvas>
                    <script>
                        function loadGraphData() {
                            const selectedUser = document.getElementById('selected_user').value;
                            fetch('fetch_graph_data.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ selected_user: selectedUser })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    const labels = data.map(item => `Month ${item.mes}`);
                                    const values = data.map(item => item.total_dias);

                                    const ctx = document.getElementById('yearlyGraph').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: labels,
                                            datasets: [{
                                                label: 'Total Days in Month',
                                                data: values,
                                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
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
                        }

                        // Load the graph data on page load if the user is not a boss
                        <?php if ($userRole != 'boss'): ?>
                            loadGraphData();
                        <?php endif; ?>
                    </script>
                <?php else: ?>
                    <!-- Existing Content Here -->
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="logout.php" style="display: inline;">
            <button type="submit" class="logout-btn">Déconnexion</button>
        </form>

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