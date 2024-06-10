<?php
include 'conexion_exit_pr.php';
if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit;
}

// Fetch all data initially
$sql_ruta = "
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
LEFT JOIN Comisionistas AS COMI ON COMI.CodigoComisionista = AVC.CodigoComisionista
WHERE AVC.CodigoEmpresa = 1
    AND AVC.CodigoRuta IN (91,92,93)
GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente
";

$stmt_ruta = sqlsrv_query($conn, $sql_ruta);
if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

$results_ruta = array();
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);

// Fetch data for Cliente tab
$sql_cliente = "
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
WHERE CodigoEmpresa = 1
    AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta, CodigoCliente, RazonSocial
ORDER BY CodigoRuta, CodigoCliente, RazonSocial
";

$stmt_cliente = sqlsrv_query($conn, $sql_cliente);
if ($stmt_cliente === false) {
    die(print_r(sqlsrv_errors(), true));
}

$results_cliente = array();
while ($row = sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC)) {
    $results_cliente[] = $row;
}

sqlsrv_free_stmt($stmt_cliente);

// Fetch data for Seller tab
$sql_all = "
SELECT 
    CodigoRuta AS RUTA,
    CASE
        WHEN CodigoRuta = 91 THEN 'ROSA'
        WHEN CodigoRuta = 92 THEN 'RUBEN'
        WHEN CodigoRuta = 93 THEN 'SUSI'
    END AS COMERCIAL,
    CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO
FROM AlbaranVentaCabecera
WHERE CodigoEmpresa = 1
    AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta
";

$stmt_all = sqlsrv_query($conn, $sql_all);
if ($stmt_all === false) {
    die(print_r(sqlsrv_errors(), true));
}

$results_all = array();
while ($row = sqlsrv_fetch_array($stmt_all, SQLSRV_FETCH_ASSOC)) {
    $results_all[] = $row;
}

sqlsrv_free_stmt($stmt_all);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport des Ventes</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="style.css">
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function filterTable() {
                    var mes = document.getElementById('mes').value;
                    var annee = document.getElementById('annee').value;
                    var tables = document.querySelectorAll('.tabcontent table tbody');

                    tables.forEach(function (tbody) {
                        var rows = tbody.querySelectorAll('tr');
                        rows.forEach(function (row) {
                            var fecha = row.querySelector('td:nth-child(5)').textContent;
                            var dateParts = fecha.split('/');
                            var rowMonth = dateParts[0];
                            var rowYear = dateParts[2];

                            if (rowMonth == mes && rowYear == annee) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                }

                document.getElementById('mes').addEventListener('change', filterTable);
                document.getElementById('annee').addEventListener('change', filterTable);

                // Initial table filter
                filterTable();
            });

            function openTab(evt, tabName) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tabcontent");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tablinks");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" active", "");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.className += " active";
            }
        </script>
    </head>

    <body>
        <nav role="navigation">
            <div id="menuToggle">
                <input type="checkbox" />
                <span></span>
                <span></span>
                <span></span>
                <ul id="menu">
                    <a href="#" onclick="openTab(event, 'Seller')">
                        <li>Todo</li>
                    </a>
                    <a href="#" onclick="openTab(event, 'Cliente')">
                        <li>Cliente</li>
                    </a>
                    <a href="#" onclick="openTab(event, 'Ruta')">
                        <li>Ruta</li>
                    </a>
                </ul>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <h2>Rapport des Ventes</h2>
                    <form method="get" action="" class="row g-3">
                        <div class="col-auto">
                            <label for="mes" class="form-label">Mois :</label>
                            <select class="form-select" id="mes" name="mes">
                                <?php
                                $monthNames = [
                                    1 => 'Janvier',
                                    2 => 'Février',
                                    3 => 'Mars',
                                    4 => 'Avril',
                                    5 => 'Mai',
                                    6 => 'Juin',
                                    7 => 'Juillet',
                                    8 => 'Août',
                                    9 => 'Septembre',
                                    10 => 'Octobre',
                                    11 => 'Novembre',
                                    12 => 'Décembre'
                                ];
                                foreach ($monthNames as $key => $value) {
                                    $selected = ($key == $MES) ? 'selected' : '';
                                    echo "<option value=\"$key\" $selected>$value</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="annee" class="form-label">Année :</label>
                            <select class="form-select" id="annee" name="annee">
                                <?php
                                $current_year = date('Y');
                                for ($i = $current_year - 5; $i <= $current_year; $i++) {
                                    $selected = ($i == $ANNEE) ? 'selected' : '';
                                    echo "<option value=\"$i\" $selected>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary" onclick="filterTable()">Filtrer</button>
                        </div>
                    </form>
                    <!-- Tab content for Ruta -->
                    <div id="Ruta" class="tabcontent">
                        <table class="table">
                            <thead>
                                <tr>
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
                                </tr>
                            </thead>
                            <tbody>
                                <div class="row">
                                    <div class="col-md-9">‎ ‎‎‎ </div>
                                    <div class="col-md-4 pt-2">
                                        <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=ruta"
                                            class="btn btn-success">Descargar como CSV</a>
                                    </div>
                                </div>
                                <?php foreach ($results_ruta as $row): ?>
                                    <tr>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab content for Cliente -->
                    <div id="Cliente" class="tabcontent">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>RUTA</th>
                                    <th>COMERCIAL</th>
                                    <th>CLIENTE</th>
                                    <th>RAZON SOCIAL</th>
                                    <th>FACTURADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <div class="row">
                                    <div class="col-md-9">‎ ‎‎‎ </div>
                                    <div class="col-md-4 pt-2">
                                        <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=cliente"
                                            class="btn btn-success">Descargar como CSV</a>
                                    </div>
                                </div>
                                <?php foreach ($results_cliente as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                        <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
                                        <td><?php echo htmlspecialchars($row['CodigoCliente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['RazonSocial']); ?></td>
                                        <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab content for Seller -->
                    <div id="Seller" class="tabcontent">
                        <table class="table">
                            <div class="row">
                                <div class="col-md-9">‎ ‎‎‎ </div>
                                <div class="col-md-4 pt-2">
                                    <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=all"
                                        class="btn btn-success">Descargar como CSV</a>
                                </div>
                            </div>
                            <thead>
                                <tr>
                                    <th>RUTA</th>
                                    <th>COMERCIAL</th>
                                    <th>FACTURADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results_all as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                        <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
                                        <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>

</html>