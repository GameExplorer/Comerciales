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
        <link rel="stylesheet" href="Login.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://getbootstrap.com/docs/5.3/assets/css/docs.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <title>Acceso</title>
        <link rel="stylesheet" href="Login.css">
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: #f8f9fa;
            }

            .login-form {
                max-width: 400px;
                width: 100%;
                padding: 30px;
                background-color: #ffffff;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                border-radius: 10px;
            }

            .password-toggle {
                cursor: pointer;
                margin-left: 10px;
            }

            h1 {
                font-weight: 600;
                text-transform: uppercase;
            }
        </style>
    </head>

    <body>
        <div class="">
            <form method="POST" class="login-form">
                <h1>Acceso</h1>
                <div class="mb-4">
                    <label for="username" class="form-label">Usuario:</label>
                    <input type="text" id="username" minlength="3" name="username" placeholder="Usuario"
                        class="form-control" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña:</label>
                    <div class="input-group">
                        <input type="password" id="password" minlength="6" name="password" placeholder="Contraseña"
                            class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
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