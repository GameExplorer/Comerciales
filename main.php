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
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=ventas_por_cliente">Ventas por
                Cliente</a>
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=ventas_por_ruta">Ventas por
                Comerciales</a>
            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=detalle_por_ruta">Detalle por Ruta</a>
            <?php
            if ($userRole == 1) {
                echo "<a href='controlPanel.php'>Control Panel</a>";
            }
            ?>
        </div>


        <div id="main">
            <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;
                <?php echo htmlspecialchars($pageTitle); ?></span>
            <div class="container mt-5">
                <form method="GET" action="">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="mes" class="form-label">Mes</label>
                            <select class="form-select" id="mes" name="mes">
                                <?php
                                $meses = [
                                    1 => 'Enero',
                                    2 => 'Febrero',
                                    3 => 'Marzo',
                                    4 => 'Abril',
                                    5 => 'Mayo',
                                    6 => 'Junio',
                                    7 => 'Julio',
                                    8 => 'Agosto',
                                    9 => 'Septiembre',
                                    10 => 'Octubre',
                                    11 => 'Noviembre',
                                    12 => 'Diciembre'
                                ];
                                foreach ($meses as $num => $nombre): ?>
                                    <option value="<?php echo $num; ?>" <?php if ($num == $MES)
                                           echo 'selected'; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="annee" class="form-label">Año</label>
                            <select class="form-select" id="annee" name="annee">
                                <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php if ($y == $ANNEE)
                                           echo 'selected'; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-none">
                            <label for="query" class="form-label">Tipo de Consulta</label>
                            <select class="form-select" id="query" name="query">
                                <option value="ventas_por_cliente" <?php if ($queryType == 'ventas_por_cliente')
                                    echo 'selected'; ?>>Ventas por Cliente</option>
                                <option value="ventas_por_ruta" <?php if ($queryType == 'ventas_por_ruta')
                                    echo 'selected'; ?>>Ventas por Comerciales</option>
                                <option value="detalle_por_ruta" <?php if ($queryType == 'detalle_por_ruta')
                                    echo 'selected'; ?>>Detalle por Ruta</option>
                            </select>
                        </div>
                        <div class="col-md-3 align-self-end">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </div>
                </form>

                <!-- Tab content for Ruta -->
                <div id="Ruta" class="tabcontent">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>RUTA</th>
                                <th>COMERCIAL</th>
                                <?php if ($queryType == 'detalle_por_ruta'): ?>
                                    <th>TIPO</th>
                                    <th>COMISIONISTA</th>
                                    <th>NOMBRE</th>
                                    <th>FECHA</th>
                                    <th>Código Cliente</th>
                                    <th>Razón Social</th>
                                    <th>Numero Factura</th>
                                    <th>BRUTO</th>
                                    <th>DTO</th>
                                    <th>FACTURADO</th>
                                <?php elseif ($queryType == 'ventas_por_ruta'): ?>
                                    <th>FACTURADO</th>
                                <?php else: ?>
                                    <th>Código Cliente</th>
                                    <th>Razón Social</th>
                                    <th>FACTURADO</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <div class="row">
                                <div class="col-md-9">‎ ‎‎‎ </div>
                                <div class="col-md-4 pt-2">
                                    <a href="export_csv.php?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=<?php echo $queryType; ?>"
                                        class="btn btn-success">Descargar como CSV</a>
                                </div>
                            </div>
                            <?php foreach ($results_ruta as $row): ?>
                                <?php if (isset($row['COMERCIAL']) && !is_null($row['COMERCIAL'])): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['RUTA'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($row['COMERCIAL'] ?? ''); ?></td>
                                        <?php if ($queryType == 'detalle_por_ruta'): ?>
                                            <td><?php echo htmlspecialchars($row['TIPO'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['COMISIONISTA'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['NOMBRE'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['FECHA'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['CodigoCliente'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['RazonSocial'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['NumeroFactura'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['BRUTO'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['DTO'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['FACTURADO'] ?? ''); ?></td>
                                        <?php elseif ($queryType == 'ventas_por_ruta'): ?>
                                            <td><?php echo htmlspecialchars($row['FACTURADO'] ?? ''); ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($row['CodigoCliente'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['RazonSocial'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['FACTURADO'] ?? ''); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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