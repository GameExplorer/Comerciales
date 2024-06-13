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
$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$queryType = isset($_GET['query']) ? $_GET['query'] : 'ventas_por_cliente';
$userCodigoRuta = $_SESSION['codigo_ruta'];
$userRole = $_SESSION['role'];

// Vérification des paramètres de session
if (!isset($userCodigoRuta) || !isset($userRole)) {
    echo "Error: Parámetros de sesión no definidos.";
    exit();
}

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
            AND ((1 = ?) OR CodigoRuta = ?)
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
            AND ((1 = ?) OR CodigoRuta = ?)
            AND CodigoRuta IN (91, 92, 93)
        GROUP BY CodigoRuta
    ",
    'detalle_por_ruta' => "
        SELECT 
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
            ON COMI.CodigoComisionista = AVC.CodigoComisionista
        WHERE 
            AVC.CodigoEmpresa = 1
            AND AVC.EjercicioAlbaran = ?
            AND MONTH(AVC.FechaAlbaran) = ?
            AND ((1 = ?) OR CodigoRuta = ?)
            AND AVC.CodigoRuta IN (91,92,93)
        GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
        ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente
    ",
];

// Define SQL queries based on user role
if ($userRole == 1) { // If the user is a boss
    $sql_ruta = $sql_queries[$queryType];
    $params_ruta = array($ANNEE, $MES, 1, $userCodigoRuta); // Set the role to 1
} else { // If the user is not a boss
    $sql_ruta = $sql_queries[$queryType];
    $params_ruta = array($ANNEE, $MES, 0, $userCodigoRuta); // Set the role to 0
}

// Prepare and execute the query
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);
if ($stmt_ruta === false) {
    echo "Error en la consulta SQL:";
    die(print_r(sqlsrv_errors(), true));
}

// Fetch results
$results_ruta = [];
while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    $results_ruta[] = $row;
}

sqlsrv_free_stmt($stmt_ruta);
?>

<!DO    CTYPE html>
    <html>
<hea    d>
    <title><?php echo $pageTitle; ?></title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</he    ad>
    <body>
    <h1>    <?php echo $pageTitle; ?></h1>
        <canvas id="ventasChart" width="400" height="200"></canvas>
    <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                var ctx = document.getElementById('ventasChart').getContext('2d');
                var chartData = {
                    labels: [<?php
                    // Get unique route names for labels
                    $routes = array_unique(array_column($results_ruta, 'COMERCIAL'));
                    echo '"' . implode('","', $routes) . '"';
                    ?>],
                    datasets: [{
                    label: 'Ventas Facturado',
                    data: [<?php
                    // Aggregate data by route
                    $facturadoPorRuta = array_reduce($results_ruta, function ($carry, $item) {
                        if (!isset($carry[$item['COMERCIAL']])) {
                            $carry[$item['COMERCIAL']] = 0;
                        }
                        $carry[$item['COMERCIAL']] += $item['FACTURADO'];
                        return $carry;
                    }, []);
                    echo implode(',', $facturadoPorRuta);
                    ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            };
            var ventasChart = new Chart(ctx, {
                type: 'bar',
                    data: chartData,
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
