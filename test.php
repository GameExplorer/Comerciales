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
";

// Préparation et exécution de la requête
$params_ruta = array($ANNEE, $MES);
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);
if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Récupération des résultats
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
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <form method="GET" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="mes" class="form-label">Mes</label>
                    <select class="form-select" id="mes" name="mes">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if ($m == $MES) echo 'selected'; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="annee" class="form-label">Année</label>
                    <select class="form-select" id="annee" name="annee">
                        <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php if ($y == $ANNEE) echo 'selected'; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
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
                        <th>CodigoCliente</th>
                        <th>RazonSocial</th>
                        <th>FACTURADO</th>
                    </tr>
                </thead>
                <tbody>
                    <div class="row">
                        <div class="col-md-9">‎ ‎‎‎ </div>
                        <div class="col-md-4 pt-2">
                            <a href="?mes=<?php echo $MES; ?>&annee=<?php echo $ANNEE; ?>&download=ruta" class="btn btn-success">Descargar como CSV</a>
                        </div>
                    </div>
                    <?php foreach ($results_ruta as $row): ?>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
