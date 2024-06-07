<?php
    $serverName = "192.168.0.23\SQLEXIT,1433";
    $connectionOptions = array(
        "Database" => "ExitERP0415",
        "Uid" => "programacion",
        "PWD" => "CU_2023",
        "CharacterSet" => "UTF-8",
        "TrustServerCertificate" => true
    );
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Connexion réussie !";
    }
?>