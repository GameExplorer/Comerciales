<?php
session_start();
include 'conexion_db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['logout'])) {
    session_destroy();
    header("Location: loginPage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Requête SQL pour vérifier les identifiants de connexion
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($row['disabled'] == 1) {
            $_SESSION['username'] = $username;
            $_SESSION['codigo_ruta'] = $row['codigo_ruta'];
            $_SESSION['role'] = $row['role'];
            header("Location: main.php");
            exit();
        } else {
            echo "this user is disabled.";
        }
    } else {
        echo "Nom d'utilisateur et/ou mot de passe incorrect(s).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="Login.css">
        <style>
            #brrr {
                margin-top: 100px;
            }
        </style>
    </head>

    <body>
        <div class="container mt-5 pt-5" id="brrr">
            <form method="POST">
                <div>
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div>
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <input type="submit" value="Login">
                    <span class="password-toggle" onclick="togglePassword()">Mostrar contraseña</span>
                </div>
            </form>
        </div>

        <script>
            function togglePassword() {
                var passwordInput = document.getElementById("password");
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                } else {
                    passwordInput.type = "password";
                }
            }
        </script>
    </body>

</html>