<?php
include 'conexion_exit_pr.php';

if ($conn === false) {
    die("Error de conexión a la base de datos.");
}

$MES = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ANNEE = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');
$queryType = isset($_GET['query']) ? $_GET['query'] : 'ventas_por_cliente';

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

$sql_ruta = $sql_queries[$queryType];
$params_ruta = array($ANNEE, $MES);
$stmt_ruta = sqlsrv_query($conn, $sql_ruta, $params_ruta);

if ($stmt_ruta === false) {
    die(print_r(sqlsrv_errors(), true));
}

$filename = "export_" . $queryType . "_" . date('Ymd') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);
$output = fopen('php://output', 'w');

if ($queryType == 'detalle_por_ruta') {
    fputcsv($output, array('TIPO', 'RUTA', 'COMISIONISTA', 'NOMBRE', 'FECHA', 'CodigoCliente', 'RazonSocial', 'NumeroFactura', 'BRUTO', 'DTO', 'FACTURADO'));
} elseif ($queryType == 'ventas_por_ruta') {
    fputcsv($output, array('RUTA', 'COMERCIAL', 'FACTURADO'));
} else {
    fputcsv($output, array('RUTA', 'COMERCIAL', 'CodigoCliente', 'RazonSocial', 'FACTURADO'));
}

while ($row = sqlsrv_fetch_array($stmt_ruta, SQLSRV_FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
sqlsrv_free_stmt($stmt_ruta);
?>
