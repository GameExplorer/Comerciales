<?php
session_start();
session_destroy();
header("Location: loginPage.php");
exit();
?>