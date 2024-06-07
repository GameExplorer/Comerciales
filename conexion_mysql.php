<?php

$servername = "localhost";
$user = 'root';
$password = ''; 
$database = 'impresionticket';
$port = NULL; 
$mysqli = new mysqli($servername, $user, $password, $database, $port);

if ($mysqli->connect_error) {
    die ('Connect Error (' . $mysqli->connect_errno . ') '
        . $mysqli->connect_error);
}