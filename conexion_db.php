<?php
date_default_timezone_set('Atlantic/Canary');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "comerciales";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>