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
            echo "este usuario está deshabilitado.";
        }
    } else {
        echo "Nombre de usuario y/o contraseña incorrecta(s).";
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
            <form method="POST" class="login-form">
                <div class="mb-4">
                    <label for="username" class="form-label">Usuario:</label>
                    <input type="text" id="username" minlegnth="3" name="username" placeholder="8suario"
                        class="form-control" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña:</label>
                    <input type="password" id="password" minlength="6" name="password" placeholder="contrasena"
                        class="form-control" required>
                </div>
                <div>
                    <input type="submit" class="btn btn-primary" value="Login">
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