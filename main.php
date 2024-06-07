<?php
include 'conexion_exit_pr.php';
if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit;
}

// Initialisation des variables de filtre
$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

// Requête SQL pour les ventes par ruta
$sql_ruta = "
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
    AND EjercicioAlbaran = ?
    AND MONTH(FechaAlbaran) = ?
    AND YEAR(FechaAlbaran) = ?
    AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta
";

// Préparation et exécution de la requête
$params = array($ANNEE, $MES, $ANNEE);
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params);
if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Récupération des résultats
$results_ruta = array();
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);

// Requête SQL pour les ventes par client
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
    AND EjercicioAlbaran = ?
    AND MONTH(FechaAlbaran) = ?
    AND YEAR(FechaAlbaran) = ?
    AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta, CodigoCliente, RazonSocial
ORDER BY RUTA, CodigoCliente
";


$sql_ruta = " SELECT 
		'Albaran' AS TIPO,
		AVC.CodigoRuta AS RUTA,
		AVC.CodigoComisionista AS COMISIONISTA,
		IIF(AVC.CodigoComisionista IN (51,3,25),COMI.Comisionista,'') AS NOMBRE,
		CONVERT(VARCHAR,AVC.FechaAlbaran,101) AS FECHA,
		AVC.CodigoCliente,
		AVC.RazonSocial,
		AVC.NumeroFactura,
		CAST(SUM(AVC.ImporteBruto) AS numeric(10,2)) AS BRUTO,
		CAST(SUM(AVC.ImporteDescuento) AS numeric(10,2)) AS DTO,
		CAST(SUM(AVC.ImporteFactura) AS numeric(10,2)) AS FACTURADO

FROM AlbaranVentaCabecera AS AVC
LEFT JOIN Comisionistas AS COMI
	ON	COMI.CodigoComisionista = AVC.CodigoComisionista
WHERE		AVC.CodigoEmpresa = 1
		AND AVC.EjercicioAlbaran = @EJERCICIO
		AND MONTH(AVC.FechaAlbaran) = @MES
		AND AVC.CodigoRuta IN (91,92,93)
GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente
";

// Préparation et exécution de la requête
$stmt_cliente = sqlsrv_query($conn, $sql_cliente, $params);
if ($stmt_cliente === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Récupération des résultats
$results_cliente = array();
while ($row = sqlsrv_fetch_array($stmt_cliente, SQLSRV_FETCH_ASSOC)) {
    $results_cliente[] = $row;
}

sqlsrv_free_stmt($stmt_cliente);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Ventes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        .tabcontent {
            display: none;
        }

        .tablinks.active {
            background-color: #ccc;
        }

        #menuToggle {
            display: block;
            position: relative;
            top: 50px;
            left: 50px;
            z-index: 1;
            -webkit-user-select: none;
            user-select: none;
        }

        #menuToggle a {
            text-decoration: none;
            color: #232323;
            transition: color 0.3s ease;
        }

        #menuToggle a:hover {
            color: tomato;
        }

        #menuToggle input {
            display: block;
            width: 40px;
            height: 32px;
            position: absolute;
            top: -7px;
            left: -5px;
            cursor: pointer;
            opacity: 0; 
            z-index: 2; 
            -webkit-touch-callout: none;
        }

        #menuToggle span {
            display: block;
            width: 33px;
            height: 4px;
            margin-bottom: 5px;
            position: relative;
            background: #cdcdcd;
            border-radius: 3px;
            z-index: 1;
            transform-origin: 4px 0px;
            transition: transform 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
                        background 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
                        opacity 0.55s ease;
        }

        #menuToggle span:first-child {
            transform-origin: 0% 0%;
        }

        #menuToggle span:nth-last-child(2) {
            transform-origin: 0% 100%;
        }

        #menuToggle input:checked ~ span {
            opacity: 1;
            transform: rotate(45deg) translate(-2px, -1px);
            background: #232323;
        }

        #menuToggle input:checked ~ span:nth-last-child(3) {
            opacity: 0;
            transform: rotate(0deg) scale(0.2, 0.2);
        }

        #menuToggle input:checked ~ span:nth-last-child(2) {
            transform: rotate(-45deg) translate(0, -1px);
        }

        #menu {
            position: absolute;
            width: 300px;
            margin: -100px 0 0 -50px;
            padding: 50px;
            padding-top: 125px;
            background: #ededed;
            list-style-type: none;
            -webkit-font-smoothing: antialiased;
            transform-origin: 0% 0%;
            transform: translate(-100%, 0);
            transition: transform 0.5s cubic-bezier(0.77,0.2,0.05,1.0);
        }

        #menu li {
            padding: 10px 0;
            font-size: 22px;
        }

        #menuToggle input:checked ~ ul {
            transform: none;
        }
    </style>
    <script>
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
                <a href="#" onclick="openTab(event, 'Ruta')"><li>Seller</li></a>
                <a href="#" onclick="openTab(event, 'Cliente')"><li>Cliente</li></a>
                <a href="#" onclick="openTab(event, 'Ruta')"><li>Ruta</li></a>
            </ul>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2>Rapport des Ventes</h2>
                <form method="get" action="" class="row g-3">
                    <div class="col-auto">
                        <label for="mes" class="form-label">MES :</label>
                        <input type="number" class="form-control" id="mes" name="mes" min="1" max="12" value="<?php echo $MES; ?>" size="2">
                    </div>
                    <div class="col-auto">
                        <label for="annee" class="form-label">AÑO :</label>
                        <input type="number" class="form-control" id="annee" name="annee" min="2000" max="<?php echo date('Y'); ?>" value="<?php echo $ANNEE; ?>" size="4">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                    </div>
                </form>

                <!-- Tab content for Ruta -->
                <div id="Ruta" class="tabcontent">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>RUTA</th>
                                <th>COMERCIAL</th>
                                <th>FACTURADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results_ruta as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                                    <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>    
</body>
</html>
