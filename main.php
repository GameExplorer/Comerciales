<?php
include 'conexion_exit_pr.php';
if ($conn === false) {
    echo "Error de conexión a la base de datos.";
    exit;
}

// Initialisation des variables de filtre
$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

// Requête SQL
$sql = "
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
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Récupération des résultats
$results = array();
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $results[] = $row;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Ventes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
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
        <table class="table">
            <thead>
                <tr>
                    <th>RUTA</th>
                    <th>COMERCIAL</th>
                    <th>FACTURADO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['RUTA']); ?></td>
                        <td><?php echo htmlspecialchars($row['COMERCIAL']); ?></td>
                        <td><?php echo htmlspecialchars($row['FACTURADO']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>    
</body>
</html>
