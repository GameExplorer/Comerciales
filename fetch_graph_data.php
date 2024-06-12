<?php
session_start();
include 'conexion_exit_pr.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$userRole = $_SESSION['role'];
$userCodigoRuta = $_SESSION['codigo_ruta'];
$selectedUser = isset($_POST['selected_user']) ? intval($_POST['selected_user']) : $userCodigoRuta;

$sql = "
    SELECT MONTH(FechaAlbaran) as mes, COUNT(*) as total_dias
    FROM AlbaranVentaCabecera
    WHERE CodigoEmpresa = 1
      AND EjercicioAlbaran = YEAR(GETDATE())
      AND (('boss' = ? AND ? = 0) OR CodigoRuta = ?)
      AND CodigoRuta IN (91, 92, 93)
    GROUP BY MONTH(FechaAlbaran)
    ORDER BY mes
";

$params = array($userRole, $userCodigoRuta, $selectedUser);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

header('Content-Type: application/json');
echo json_encode($data);
