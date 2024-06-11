<?php
include 'conexion_exit_pr.php';
if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit;
}

// Initialize filter variables
$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$queryType = isset($_GET['query']) ? $_GET['query'] : 'ventas_por_cliente';

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
            AND CodigoRuta IN (91,92,93)
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
            AND CodigoRuta IN (91,92,93)
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
            AND AVC.CodigoRuta IN (91,92,93)
        GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
        ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente
    ",
];

// Prepare and execute the query
$sql_ruta = $sql_queries[$queryType];
$params_ruta = array($ANNEE, $MES);
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);
if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch results
$results_ruta = array();
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Display</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        /* The side navigation menu */
        .sidenav {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #111;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }

        /* The navigation menu links */
        .sidenav a {
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 25px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        /* When you mouse over the navigation links, change their color */
        .sidenav a:hover {
            color: #f1f1f1;
        }

        /* Position and style the close button (top right corner) */
        .sidenav .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
            margin-left: 50px;
        }

        /* Style page content */
        .main {
            transition: margin-left .5s;
            padding: 16px;
        }
    </style>
</head>
<body>

<div id="mySidenav" class="sidenav">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="?query=ventas_por_cliente&mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>">Ventas por Cliente</a>
    <a href="?query=ventas_por_ruta&mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>">Ventas por Ruta</a>
    <a href="?query=detalle_por_ruta&mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>">Detalle por Ruta</a>
</div>

<div class="main">
    <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776; Open Menu</span>
    <div class="container mt-5">
        <form method="GET" action="">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="mes" class="form-label">Mes</label>
                    <select class="form-select" id="mes" name="mes">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if ($m == $MES) echo 'selected'; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="annee" class="form-label">Année</label>
                    <select class="form-select" id="annee" name="annee">
                        <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php if ($y == $ANNEE) echo 'selected'; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="query" class="form-label">Tipo de Consulta</label>
                    <select class="form-select" id="query" name="query">
                        <option value="ventas_por_cliente" <?php if ($queryType == 'ventas_por_cliente') echo 'selected'; ?>>Ventas por Cliente</option>
                        <option value="ventas_por_ruta" <?php if ($queryType == 'ventas_por_ruta') echo 'selected'; ?>>Ventas por Ruta</option>
                        <option value="detalle_por_ruta" <?php if ($queryType == 'detalle_por_ruta') echo 'selected'; ?>>Detalle por Ruta</option>
                    </select>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </div>
        </form>

        <!-- Tab content for Ruta -->
        <div id="Ruta" class="tabcontent">
            <table class="table">
                <thead>
                    <tr>
                        <?php if ($queryType == 'detalle_por_ruta'): ?>
                            <th>TIPO</th>
                            <th>RUTA</th>
                            <th>COMISIONISTA</th>
                            <th>NOMBRE</th>
                            <th>FECHA</th>
                            <th>CodigoCliente</th>
                            <th>RazonSocial</th>
                            <th>NumeroFactura</th>
                            <th>BRUTO</th>
                            <th>DTO</th>
                            <th>FACTURADO</th>
                        <?php elseif ($queryType == 'ventas_por_ruta'): ?>
                            <th>RUTA</th>
                            <th>COMERCIAL</th>
                            <th>FACTURADO</th>
                        <?php else: ?>
                            <th>RUTA</th>
                            <th>COMERCIAL</th>
                            <th>CodigoCliente</th>
                            <th>RazonSocial</th>
                            <th>FACTURADO</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <div class="row">
                        <div class="col-md-9">‎ ‎‎‎ </div>
                        <div class="col-md-4 pt-2">
                            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&query=<?php echo $queryType; ?>&download=ruta"
                                class="btn btn-success">Descargar como CSV</a>
                        </div>
                    </div>
                    <?php foreach ($results_ruta as $row): ?>
                        <tr>
                            <?php if ($queryType == 'detalle_por_ruta'): ?>
                                <td><?php echo htmlspecialchars($row['TIPO']); ?></td>
                                <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                <td><?php echo htmlspecialchars($row['COMISIONISTA']); ?></td>
                                <td><?php echo htmlspecialchars($row['NOMBRE']); ?></td>
                                <td><?php echo htmlspecialchars($row['FECHA']); ?></td>
                                <td><?php echo htmlspecialchars($row['CodigoCliente']); ?></td>
                                <td><?php echo htmlspecialchars($row['RazonSocial']); ?></td>
                                <td><?php echo htmlspecialchars($row['NumeroFactura']); ?></td>
                                <td><?php echo htmlspecialchars($row['BRUTO']); ?></td>
                                <td><?php echo htmlspecialchars($row['DTO']); ?></td>
                                <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                            <?php elseif ($queryType == 'ventas_por_ruta'): ?>
                                <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
                                <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                            <?php else: ?>
                                <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
                                <td><?php echo htmlspecialchars($row['CodigoCliente']); ?></td>
                                <td><?php echo htmlspecialchars($row['RazonSocial']); ?></td>
                                <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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